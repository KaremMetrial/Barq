<?php

namespace Modules\Couier\Services;

use Illuminate\Http\UploadedFile;
use Modules\Couier\Models\OrderReceipt;
use Modules\Couier\Models\CourierOrderAssignment;
use Spatie\Image\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderReceiptService
{
    /**
     * Upload a receipt file
     */
    public function uploadReceipt(int $assignmentId, UploadedFile $file, string $type, ?array $metadata = null): OrderReceipt
    {
        // Validate assignment
        $assignment = CourierOrderAssignment::findOrFail($assignmentId);

        // Validate file type and assignment status
        $this->validateReceiptUpload($type, $assignment);

        // Generate secure filename
        $filename = $this->generateSecureFilename($file);

        // Store file
        $path = $file->store('order-receipts', 'public');

        // Optimize image if it's an image file
        if ($this->isImageFile($file)) {
            $this->optimizeImage($path);
        }

        // Create receipt record
        $receipt = OrderReceipt::create([
            'assignment_id' => $assignmentId,
            'type' => $type,
            'file_path' => $path,
            'file_name' => $filename,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'metadata' => $metadata ?? [],
        ]);

        return $receipt;
    }

    /**
     * Validate receipt upload conditions
     */
    public function validateReceiptUpload(string $type, CourierOrderAssignment $assignment): void
    {
        // Check assignment ownership
        if (auth('sanctum')->id() !== $assignment->courier_id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized access to assignment');
        }

        // Validate receipt type based on assignment status
        $this->validateReceiptTypeForStatus($type, $assignment->status);

        // Check if receipt type already exists (for pickup and delivery types)
        if (in_array($type, ['pickup_receipt', 'delivery_proof'])) {
            $existing = $assignment->receipts()->ofType($type)->exists();
            if ($existing) {
                throw new \InvalidArgumentException("Receipt of type {$type} already exists for this assignment");
            }
        }
    }

    /**
     * Validate receipt type based on assignment status
     */
    protected function validateReceiptTypeForStatus(string $type, string $status): void
    {
        $allowedTypes = match($status) {
            'accepted' => ['pickup_product', 'pickup_receipt'],
            'in_transit' => ['pickup_product', 'pickup_receipt', 'delivery_proof', 'customer_signature'],
            'delivered', 'failed' => ['pickup_product', 'pickup_receipt', 'delivery_proof', 'customer_signature'],
            default => []
        };

        if (!in_array($type, $allowedTypes)) {
            throw new \InvalidArgumentException("Receipt type '{$type}' is not allowed for assignment status '{$status}'");
        }
    }

    /**
     * Generate secure filename
     */
    protected function generateSecureFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->timestamp;
        $random = Str::random(8);

        return "receipt_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Check if file is an image
     */
    protected function isImageFile(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Optimize image file
     */
    protected function optimizeImage(string $path): void
    {
        $fullPath = Storage::disk('public')->path($path);

        if (!file_exists($fullPath)) {
            return;
        }

        try {
            $image = Image::load($fullPath);
            $image->width(1200); // Max width
            $image->height(1200); // Max height
            $image->quality(85); // Compress quality
            $image->save($fullPath);
        } catch (\Exception $e) {
            // Log error but don't fail the upload
            \Illuminate\Support\Facades\Log::warning('Failed to optimize receipt image: ' . $e->getMessage());
        }
    }

    /**
     * Delete receipt file
     */
    public function deleteReceipt(OrderReceipt $receipt): bool
    {
        // Check ownership
        if (auth('sanctum')->id() !== $receipt->assignment->courier_id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized access to receipt');
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($receipt->file_path)) {
            Storage::disk('public')->delete($receipt->file_path);
        }

        // Delete record
        return $receipt->delete();
    }

    /**
     * Get receipts for assignment
     */
    public function getAssignmentReceipts(int $assignmentId): \Illuminate\Database\Eloquent\Collection
    {
        $assignment = CourierOrderAssignment::findOrFail($assignmentId);

        // Check ownership
        if (auth('sanctum')->id() !== $assignment->courier_id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized access to assignment');
        }

        return $assignment->receipts()->orderBy('created_at')->get();
    }

    /**
     * Validate uploaded file
     */
    public function validateFile(UploadedFile $file): void
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid file type. Only images are allowed.');
        }

        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('File size exceeds maximum limit of 5MB.');
        }
    }
}
