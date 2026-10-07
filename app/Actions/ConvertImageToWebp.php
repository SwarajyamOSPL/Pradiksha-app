<?php

namespace App\Actions;

use RuntimeException;

class ConvertImageToWebp
{
    /**
     * Convert an image on disk to WebP and write it to the given destination path.
     *
     * @param  string  $sourcePath  Absolute path to the source image (jpeg/png/gif/webp/bmp).
     * @param  string  $destinationPath  Absolute path the WebP file should be written to.
     */
    public function __invoke(string $sourcePath, string $destinationPath, int $quality = 82): void
    {
        $imageInfo = getimagesize($sourcePath);

        if ($imageInfo === false) {
            throw new RuntimeException("Unable to read image at [{$sourcePath}].");
        }

        $image = match ($imageInfo[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
            IMAGETYPE_BMP => imagecreatefrombmp($sourcePath),
            default => throw new RuntimeException("Unsupported image type for [{$sourcePath}]."),
        };

        if ($image === false) {
            throw new RuntimeException("Failed to decode image at [{$sourcePath}].");
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $written = $this->writeWithRetry($image, $destinationPath, $quality);

        imagedestroy($image);

        if (! $written) {
            throw new RuntimeException("Failed to write WebP image to [{$destinationPath}].");
        }
    }

    /**
     * Retry the write a few times: on Windows, antivirus/indexer scans can
     * transiently lock a just-written file for a few milliseconds.
     */
    private function writeWithRetry(\GdImage $image, string $destinationPath, int $quality): bool
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            if (@imagewebp($image, $destinationPath, $quality)) {
                return true;
            }

            if ($attempt < 3) {
                usleep(150_000);
            }
        }

        return false;
    }
}
