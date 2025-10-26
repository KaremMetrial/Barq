<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait FileUploadTrait
{
    /**
     * Upload and optionally resize an image file from the request.
     *
     * @param Request $request
     * @param string $fieldName - name of the input file field
     * @param string $directory - directory to save the file in (default: 'uploads')
     * @param string $disk - storage disk (default: 'public')
     * @param array|null $resize - [width, height] to resize image (maintains aspect ratio if one is null)
     * @return string|null - the path of the saved file
     */
    public function upload(
        Request $request,
        string $fieldName,
        string $directory = 'uploads',
        string $disk = 'public',
        array $resize = null
    ): ?string {
        if ($request->hasFile($fieldName)) {
            $file = $request->file($fieldName);
            $filename = Str::uuid() . '.webp';  // Save as .webp format
            $path = $directory . '/' . $filename;

            // Handle resizing if dimensions are provided
            if ($resize) {
                $image = $this->resizeImage($file, $resize[0], $resize[1]);
                // Save the resized image to WebP format
                Storage::disk($disk)->put($path, (string) $image);
            } else {
                // Just store the original file as WebP (convert to WebP format)
                $image = $this->convertToWebP($file);
                Storage::disk($disk)->put($path, (string) $image);
            }

            return $path;
        }

        return null;
    }

    /**
     * Resize image and convert to WebP format without using Intervention Image.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int|null $width
     * @param int|null $height
     * @return resource
     */
    private function resizeImage($file, ?int $width, ?int $height)
    {
        $image = imagecreatefromstring(file_get_contents($file));

        // Get original dimensions
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // If one of the dimensions is null, maintain aspect ratio
        if ($width && !$height) {
            $height = ($width / $originalWidth) * $originalHeight;
        } elseif ($height && !$width) {
            $width = ($height / $originalHeight) * $originalWidth;
        }

        // Resize the image
        $resizedImage = imagescale($image, $width, $height);

        // Convert the resized image to WebP format
        ob_start();
        imagewebp($resizedImage, null, 80); // 80 is the quality of the WebP (scale 0-100)
        $imageData = ob_get_contents();
        ob_end_clean();

        // Free memory
        imagedestroy($image);
        imagedestroy($resizedImage);

        return $imageData;
    }

    /**
     * Convert an image to WebP format without using Intervention Image.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return resource
     */
    private function convertToWebP($file)
    {
        $image = imagecreatefromstring(file_get_contents($file));

        // Convert the image to WebP format
        ob_start();
        imagewebp($image, null, 80); // 80 is the quality of the WebP (scale 0-100)
        $imageData = ob_get_contents();
        ob_end_clean();

        // Free memory
        imagedestroy($image);

        return $imageData;
    }

    /**
     * Delete a file from storage.
     *
     * @param string $filePath - path of the file to delete (relative to the disk)
     * @param string $disk - storage disk (default: 'public')
     * @return bool
     */
    public function delete(string $filePath, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->exists($filePath)
            ? Storage::disk($disk)->delete($filePath)
            : false;
    }
}
