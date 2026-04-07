<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageCompressionService
{
    protected $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Compress and resize an image
     *
     * @param string $sourcePath Full path to source image
     * @param string $destinationPath Full path to destination image
     * @param int $quality JPEG quality (1-100), default 85
     * @param int $maxWidth Maximum width in pixels, default 1920
     * @param int $maxHeight Maximum height in pixels, default 1920
     * @return bool True on success, false on failure
     */
    public function compressAndResize($sourcePath, $destinationPath, $quality = 85, $maxWidth = 1920, $maxHeight = 1920)
    {
        try {
            // Check if source file exists
            if (!file_exists($sourcePath)) {
                Log::error("ImageCompressionService: Source file not found: {$sourcePath}");
                return false;
            }

            // Create destination directory if it doesn't exist
            $destinationDir = dirname($destinationPath);
            if (!is_dir($destinationDir)) {
                if (!mkdir($destinationDir, 0755, true) && !is_dir($destinationDir)) {
                    Log::error("ImageCompressionService: Failed to create directory: {$destinationDir}");
                    return false;
                }
            }

            // Read the image
            $image = $this->manager->read($sourcePath);

            // Get current dimensions
            $width = $image->width();
            $height = $image->height();

            // Resize if image exceeds max dimensions
            if ($width > $maxWidth || $height > $maxHeight) {
                $image->scaleDown($maxWidth, $maxHeight);
            }

            // Get file extension to determine format
            $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

            // Save with compression
            if (in_array($extension, ['jpg', 'jpeg'])) {
                // JPEG compression
                $image->toJpeg($quality)->save($destinationPath);
            } elseif ($extension === 'png') {
                // PNG compression (quality 0-9, where 9 is maximum compression)
                // Convert quality (0-100) to PNG compression level (0-9)
                $pngQuality = (int) round((100 - $quality) / 100 * 9);
                $image->toPng($pngQuality)->save($destinationPath);
            } elseif ($extension === 'webp') {
                // WebP compression
                $image->toWebp($quality)->save($destinationPath);
            } else {
                // For other formats, save as JPEG
                $image->toJpeg($quality)->save($destinationPath);
            }

            Log::info("ImageCompressionService: Successfully compressed image from {$sourcePath} to {$destinationPath}");
            return true;

        } catch (\Exception $e) {
            Log::error("ImageCompressionService: Error compressing image: " . $e->getMessage(), [
                'source' => $sourcePath,
                'destination' => $destinationPath,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Compress and resize image using Storage paths
     *
     * @param string $sourceStoragePath Storage path (e.g., 'uploads/temp/image.jpg')
     * @param string $destinationStoragePath Storage path (e.g., 'uploads/category/image.jpg')
     * @param int $quality JPEG quality (1-100), default 85
     * @param int $maxWidth Maximum width in pixels, default 1920
     * @param int $maxHeight Maximum height in pixels, default 1920
     * @return bool True on success, false on failure
     */
    public function compressAndResizeFromStorage($sourceStoragePath, $destinationStoragePath, $quality = 85, $maxWidth = 1920, $maxHeight = 1920)
    {
        $sourcePath = storage_path('app/' . $sourceStoragePath);
        $destinationPath = storage_path('app/' . $destinationStoragePath);

        return $this->compressAndResize($sourcePath, $destinationPath, $quality, $maxWidth, $maxHeight);
    }
}

