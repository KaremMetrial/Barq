<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

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
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $directory . '/' . $filename;

            if ($resize) {
                // Resize using Intervention
                $image = Image::make($file);
                $image->resize(
                    $resize[0] ?? null,
                    $resize[1] ?? null,
                    function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                );

                // Save the image to the disk
                Storage::disk($disk)->put($path, (string) $image->encode());
            } else {
                // Just store the original file
                $file->storeAs($directory, $filename, $disk);
            }

            return $path;
        }

        return null;
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
