<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreUploadedImageAsWebp
{
    public function __construct(private ConvertImageToWebp $convertImageToWebp) {}

    /**
     * Convert an uploaded image to WebP and store it on the public disk, returning its relative path.
     */
    public function __invoke(UploadedFile $file, string $directory, string $slug): string
    {
        Storage::disk('public')->makeDirectory($directory);

        $relativePath = "{$directory}/{$slug}.webp";

        ($this->convertImageToWebp)($file->getRealPath(), Storage::disk('public')->path($relativePath));

        return $relativePath;
    }
}
