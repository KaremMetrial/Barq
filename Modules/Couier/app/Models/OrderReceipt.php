<?php

namespace Modules\Couier\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class OrderReceipt extends Model
{
    protected $fillable = [
        'assignment_id',
        'type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
    ];

    /**
     * Get the assignment this receipt belongs to
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(CourierOrderAssignment::class, 'assignment_id');
    }

    /**
     * Get the courier for this receipt
     */
    public function courier()
    {
        return $this->hasOneThrough(
            Couier::class,
            CourierOrderAssignment::class,
            'id', // Foreign key on courier_order_assignments table
            'id', // Foreign key on couiers table
            'assignment_id', // Local key on order_receipts table
            'courier_id' // Local key on courier_order_assignments table
        );
    }

    /**
     * Get full URL for the file
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get GPS coordinates from metadata
     */
    public function getCoordinatesAttribute(): ?array
    {
        return $this->metadata['coordinates'] ?? null;
    }

    /**
     * Scope for specific assignment
     */
    public function scopeForAssignment($query, $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

    /**
     * Scope for specific type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if receipt is image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if receipt is video
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }
}
