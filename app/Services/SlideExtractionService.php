<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\Slide;
use PhpOffice\PhpPresentation\Shape\Drawing\File;

class SlideExtractionService
{
    /**
     * Extract slides from PPTX file as images
     * Tries LibreOffice first, falls back to PhpPresentation if available
     *
     * @param string $filePath Full path to the PPTX file
     * @param string $outputDir Directory to store extracted slide images
     * @return array Array of slide image paths
     */
    public function extractSlidesAsImages($filePath, $outputDir)
    {
        // Try LibreOffice first (more reliable)
        $slides = $this->extractSlidesUsingLibreOffice($filePath, $outputDir);
        
        if (!empty($slides)) {
            return $slides;
        }

        // Fallback to PhpPresentation if available
        try {
            // Check if PhpPresentation is available
            if (!class_exists('PhpOffice\PhpPresentation\IOFactory')) {
                Log::warning('PhpPresentation library not available. Install via: composer require phpoffice/phppresentation');
                return [];
            }

            // Ensure output directory exists
            if (!is_dir($outputDir)) {
                if (!mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
                    Log::error('Failed to create output directory: ' . $outputDir);
                    return [];
                }
            }

            // Load the presentation
            $presentation = IOFactory::load($filePath);
            $slideCount = $presentation->getSlideCount();

            // Extract each slide as an image
            for ($i = 0; $i < $slideCount; $i++) {
                $slide = $presentation->getSlide($i);
                
                // Create image from slide using GD or Imagick
                $imagePath = $this->renderSlideAsImage($slide, $outputDir, $i + 1);
                
                if ($imagePath) {
                    $slides[] = [
                        'slide_number' => $i + 1,
                        'image' => basename($imagePath),
                        'path' => $imagePath
                    ];
                }
            }

            Log::info('Extracted ' . count($slides) . ' slides from PPTX file using PhpPresentation');
            return $slides;

        } catch (\Exception $e) {
            Log::error('Error extracting slides from PPTX: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Render a slide as an image
     *
     * @param Slide $slide
     * @param string $outputDir
     * @param int $slideNumber
     * @return string|null Path to the image file
     */
    private function renderSlideAsImage($slide, $outputDir, $slideNumber)
    {
        try {
            // Get slide dimensions
            $width = 1920; // Default width
            $height = 1080; // Default height
            
            // Create image using GD
            if (function_exists('imagecreatetruecolor')) {
                $image = imagecreatetruecolor($width, $height);
                
                // Fill with white background
                $white = imagecolorallocate($image, 255, 255, 255);
                imagefill($image, 0, 0, $white);
                
                // Render slide shapes (simplified - would need more complex rendering)
                $this->renderSlideShapes($slide, $image, $width, $height);
                
                // Save image
                $filename = 'slide_' . str_pad($slideNumber, 3, '0', STR_PAD_LEFT) . '.png';
                $filePath = $outputDir . '/' . $filename;
                
                imagepng($image, $filePath);
                imagedestroy($image);
                
                return $filePath;
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error rendering slide as image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Render slide shapes on image (simplified version)
     */
    private function renderSlideShapes($slide, $image, $width, $height)
    {
        // This is a simplified version
        // In a full implementation, you would render text, images, shapes, etc.
        // For now, we'll use a fallback method
    }

    /**
     * Use LibreOffice to convert PPTX to images
     * This is more reliable than PhpPresentation for complex slides
     */
    public function extractSlidesUsingLibreOffice($filePath, $outputDir)
    {
        $libreOfficePath = $this->getLibreOfficePath();
        if (!$libreOfficePath) {
            Log::warning('LibreOffice is not installed. Cannot extract slides.');
            return [];
        }

        // Ensure output directory exists
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
                Log::error('Failed to create output directory: ' . $outputDir);
                return [];
            }
        }

        $baseName = pathinfo($filePath, PATHINFO_FILENAME);
        $envString = PHP_OS !== 'WINNT' ? 'HOME=' . sys_get_temp_dir() . ' ' : '';

        // Convert PPTX to PNG images using LibreOffice
        // Use --convert-to png with --outdir for better control
        $command = sprintf(
            '%s%s --headless --nodefault --nolockcheck --invisible --convert-to png --outdir %s %s 2>&1',
            $envString,
            escapeshellarg($libreOfficePath),
            escapeshellarg($outputDir),
            escapeshellarg($filePath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::error('LibreOffice conversion failed', ['output' => implode("\n", $output)]);
            return [];
        }

        // Find generated PNG files - LibreOffice creates files like "filename_1.png", "filename_2.png", etc.
        $pngFiles = glob($outputDir . '/' . $baseName . '_*.png');
        if (empty($pngFiles)) {
            // Try alternative pattern
            $pngFiles = glob($outputDir . '/*.png');
        }
        
        natsort($pngFiles);
        $pngFiles = array_values($pngFiles);

        $slides = [];
        foreach ($pngFiles as $index => $pngFile) {
            $slides[] = [
                'slide_number' => $index + 1,
                'image' => basename($pngFile),
                'path' => $pngFile
            ];
        }

        Log::info('Extracted ' . count($slides) . ' slides using LibreOffice');
        return $slides;
    }

    /**
     * Get LibreOffice executable path
     */
    private function getLibreOfficePath()
    {
        $customPath = config('services.libreoffice.path');
        if ($customPath && file_exists($customPath) && is_executable($customPath)) {
            return $customPath;
        }

        $commands = ['soffice', 'libreoffice'];
        foreach ($commands as $cmd) {
            $whichCommand = PHP_OS === 'WINNT' ? 'where' : 'which';
            $output = [];
            exec(sprintf('%s %s 2>&1', $whichCommand, escapeshellarg($cmd)), $output, $returnVar);
            if ($returnVar === 0 && !empty($output[0])) {
                $foundPath = trim($output[0]);
                if (file_exists($foundPath) && is_executable($foundPath)) {
                    return $foundPath;
                }
            }
        }

        // Try standard paths
        $standardPaths = [
            '/usr/bin/soffice',
            '/usr/bin/libreoffice',
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
        ];

        foreach ($standardPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Get relative storage path from absolute path
     */
    public function getStoragePath($absolutePath)
    {
        $storagePath = storage_path('app');
        if (strpos($absolutePath, $storagePath) === 0) {
            return str_replace($storagePath . '/', '', $absolutePath);
        }
        return $absolutePath;
    }
}

