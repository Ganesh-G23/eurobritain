<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PptConverterService
{
    /**
     * Convert PPT/PPTX file to PNG images using LibreOffice
     *
     * @param string $filePath Full path to the PPT file
     * @param string $outputDir Directory to store converted images
     * @return array Array of slide image paths or empty array on failure
     */
    public function convertToImages($filePath, $outputDir)
    {
        // Check if LibreOffice is available
        $libreOfficePath = $this->getLibreOfficePath();
        if (!$libreOfficePath) {
            Log::warning('LibreOffice is not installed or not accessible. PPT will be stored without slide conversion.');
            return [];
        }

        // Ensure output directory exists
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
                Log::error('Failed to create output directory: ' . $outputDir);
                return [];
            }
        }

        // Set environment variables for LibreOffice (prevents GUI from opening)
        // Only set these on Unix-like systems (Linux, macOS)
        $envString = '';
        if (PHP_OS !== 'WINNT') {
            $env = [
                'HOME' => sys_get_temp_dir(),
                'SHELL' => '/bin/bash',
            ];

            // Build environment string for command
            foreach ($env as $key => $value) {
                $envString .= sprintf('%s=%s ', $key, escapeshellarg($value));
            }
        }

        // Convert PPT to PNG using LibreOffice Impress
        // We need to use a macro or export each slide individually
        // First, try the standard conversion which should create separate PNG files
        // If that doesn't work, we'll need to use a different approach
        
        // Get base filename without extension for output naming
        $baseName = pathinfo($filePath, PATHINFO_FILENAME);
        
        // LibreOffice's --convert-to png for presentations creates separate PNG files
        // However, it may not work correctly for all PPT files
        // Try using unoconv if available (better for presentations)
        $unoconvPath = $this->findCommandInPath('unoconv');
        
        if ($unoconvPath) {
            // Use unoconv which handles presentations better
            $command = sprintf(
                '%s%s -f png -o %s %s 2>&1',
                $envString,
                escapeshellarg($unoconvPath),
                escapeshellarg($outputDir),
                escapeshellarg($filePath)
            );
        } else {
            // Use LibreOffice directly with Impress component
            // Try using --impress flag or convert each slide individually
            $command = sprintf(
                '%s%s --headless --nodefault --nolockcheck --invisible --impress --convert-to png --outdir %s %s 2>&1',
                $envString,
                escapeshellarg($libreOfficePath),
                escapeshellarg($outputDir),
                escapeshellarg($filePath)
            );
        }
        
        $output = [];
        $returnVar = 0;
        $execOutput = '';
        exec($command, $output, $returnVar);
        
        // Combine output for logging
        $execOutput = implode("\n", $output);
        
        // If that didn't work or only created one file, try alternative method
        // Convert to PDF first, then use a tool to extract pages
        $tempPngFiles = glob($outputDir . '/*.png');
        if (empty($tempPngFiles) || count($tempPngFiles) <= 1) {
            Log::info('Direct PNG conversion may have failed, trying PDF then image extraction');
            
            // Try PDF conversion then extract with available tools
            $pdfOutputDir = dirname($outputDir);
            $pdfPath = $pdfOutputDir . '/' . $baseName . '.pdf';
            
            $pdfCommand = sprintf(
                '%s%s --headless --nodefault --nolockcheck --invisible --convert-to pdf --outdir %s %s 2>&1',
                $envString,
                escapeshellarg($libreOfficePath),
                escapeshellarg($pdfOutputDir),
                escapeshellarg($filePath)
            );
            
            exec($pdfCommand, $pdfOutput, $pdfReturnVar);
            
            if ($pdfReturnVar === 0 && file_exists($pdfPath)) {
                // Try to extract PDF pages as images
                $this->extractPdfPagesToImages($pdfPath, $outputDir, $baseName, $envString);
                
                // Clean up PDF
                @unlink($pdfPath);
            }
        }

        // Wait for files to be written (LibreOffice might take time to write all slides)
        // Retry up to 5 times with increasing wait times
        $maxRetries = 5;
        $waitTime = 500000; // Start with 0.5 seconds (microseconds)
        $pngFiles = [];
        
        for ($retry = 0; $retry < $maxRetries; $retry++) {
            if ($retry > 0) {
                usleep($waitTime);
                $waitTime *= 2; // Double wait time for each retry
            }
            
            // Try multiple patterns to find all PNG files
            $baseName = pathinfo($filePath, PATHINFO_FILENAME);
            $pngPatterns = [
                $outputDir . '/*.png',                    // All PNG files
                $outputDir . '/' . $baseName . '_*.png',  // Pattern: filename_1.png, filename_2.png
                $outputDir . '/' . $baseName . '*.png',   // Pattern: filename1.png, filename2.png
                $outputDir . '/*_*.png',                   // Any file with underscore
            ];
            
            $foundFiles = [];
            foreach ($pngPatterns as $pattern) {
                $found = glob($pattern);
                if (!empty($found)) {
                    $foundFiles = array_merge($foundFiles, $found);
                }
            }
            // Remove duplicates
            $foundFiles = array_unique($foundFiles);
            
            // If we found files and the count is stable (not increasing), we're done
            if (!empty($foundFiles)) {
                if (count($foundFiles) === count($pngFiles) && $retry > 0) {
                    // File count is stable, we have all files
                    $pngFiles = $foundFiles;
                    break;
                }
                $pngFiles = $foundFiles;
            }
        }
        
        // Final check - get all PNG files one more time
        $finalCheck = glob($outputDir . '/*.png');
        if (!empty($finalCheck) && count($finalCheck) > count($pngFiles)) {
            $pngFiles = $finalCheck;
        }
        
        if ($returnVar !== 0 && empty($pngFiles)) {
            Log::error('LibreOffice conversion failed', [
                'command' => $command,
                'output' => $execOutput,
                'return_var' => $returnVar,
                'file_path' => $filePath,
                'output_dir' => $outputDir,
                'files_found' => count($pngFiles)
            ]);
            return [];
        }
        
        // Log warning if exit code is non-zero but files were created
        if ($returnVar !== 0 && !empty($pngFiles)) {
            Log::warning('LibreOffice returned non-zero exit code but files were created', [
                'return_var' => $returnVar,
                'files_created' => count($pngFiles)
            ]);
        }
        
        $slides = [];

        if (empty($pngFiles)) {
            // List all files in output directory for debugging
            $allFiles = glob($outputDir . '/*');
            Log::warning('No PNG files generated from PPT conversion', [
                'file_path' => $filePath,
                'output_dir' => $outputDir,
                'all_files_in_dir' => $allFiles,
                'libreoffice_output' => $execOutput
            ]);
            return [];
        }

        // Sort files to ensure correct order
        // Use natural sort to handle numbered files correctly (1, 2, 10 instead of 1, 10, 2)
        natsort($pngFiles);
        $pngFiles = array_values($pngFiles); // Re-index array

        foreach ($pngFiles as $index => $pngFile) {
            $fileName = basename($pngFile);
            $slides[] = [
                'slide_number' => $index + 1,
                'image' => $fileName,
                'path' => $pngFile
            ];
        }
        
        // Log successful conversion with slide count
        Log::info('PPT converted successfully', [
            'file_path' => $filePath,
            'slides_count' => count($slides),
            'output_files' => array_map('basename', $pngFiles)
        ]);

        return $slides;
    }

    /**
     * Check if LibreOffice is available on the system
     *
     * @return bool
     */
    private function isLibreOfficeAvailable()
    {
        return $this->getLibreOfficePath() !== null;
    }

    /**
     * Get LibreOffice executable path
     * Works on Linux servers and macOS development environments
     *
     * @return string|null
     */
    private function getLibreOfficePath()
    {
        // First, check if a custom path is set in config
        $customPath = config('services.libreoffice.path');
        if ($customPath && file_exists($customPath) && is_executable($customPath)) {
            return $customPath;
        }

        // Common command names (will be found via PATH)
        $commands = ['soffice', 'libreoffice'];
        
        // Standard Linux installation paths (most common on servers)
        $standardPaths = [
            '/usr/bin/soffice',           // Ubuntu/Debian standard
            '/usr/bin/libreoffice',       // Alternative Ubuntu/Debian
            '/usr/local/bin/soffice',     // Custom installation
            '/usr/local/bin/libreoffice', // Custom installation
            '/opt/libreoffice*/program/soffice', // Some distributions
        ];

        // macOS development paths (for local development)
        $macPaths = [
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
            '/opt/homebrew/bin/soffice',  // Homebrew Apple Silicon
            '/usr/local/bin/soffice',     // Homebrew Intel
        ];

        // Combine all paths based on OS
        $allPaths = $standardPaths;
        if (PHP_OS === 'Darwin') {
            $allPaths = array_merge($macPaths, $standardPaths);
        }

        // First, try to find commands in PATH (most reliable)
        foreach ($commands as $cmd) {
            $foundPath = $this->findCommandInPath($cmd);
            if ($foundPath) {
                return $foundPath;
            }
        }

        // Then try standard installation paths
        foreach ($allPaths as $path) {
            // Handle glob patterns
            if (strpos($path, '*') !== false) {
                $matches = glob($path);
                if (!empty($matches)) {
                    $path = $matches[0];
                } else {
                    continue;
                }
            }

            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Find command in system PATH
     *
     * @param string $command
     * @return string|null
     */
    private function findCommandInPath($command)
    {
        // Try 'which' command (Linux/macOS)
        $whichCommand = PHP_OS === 'WINNT' ? 'where' : 'which';
        $commandToRun = sprintf('%s %s 2>&1', $whichCommand, escapeshellarg($command));
        
        $output = [];
        $returnVar = 0;
        exec($commandToRun, $output, $returnVar);
        
        if ($returnVar === 0 && !empty($output[0])) {
            $foundPath = trim($output[0]);
            // Verify the path exists and is executable
            if (file_exists($foundPath) && is_executable($foundPath)) {
                return $foundPath;
            }
        }

        // Fallback: try common PATH locations
        $pathEnv = getenv('PATH');
        if ($pathEnv) {
            $paths = explode(PATH_SEPARATOR, $pathEnv);
            foreach ($paths as $path) {
                $fullPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $command;
                if (file_exists($fullPath) && is_executable($fullPath)) {
                    return $fullPath;
                }
            }
        }

        return null;
    }

    /**
     * Extract PDF pages to individual PNG images
     *
     * @param string $pdfPath
     * @param string $outputDir
     * @param string $baseName
     * @param string $envString
     * @return void
     */
    private function extractPdfPagesToImages($pdfPath, $outputDir, $baseName, $envString)
    {
        // Try ImageMagick first (check for both 'magick' and 'convert' commands)
        // ImageMagick 7 uses 'magick', older versions use 'convert'
        $magickPath = $this->findCommandInPath('magick');
        $convertPath = $this->findCommandInPath('convert');
        
        if ($magickPath) {
            // Use ImageMagick 7 'magick' command
            $command = sprintf(
                '%s%s convert -density 150 %s -quality 90 %s/%s_%%02d.png 2>&1',
                $envString,
                escapeshellarg($magickPath),
                escapeshellarg($pdfPath),
                escapeshellarg($outputDir),
                escapeshellarg($baseName)
            );
            exec($command);
            return;
        } elseif ($convertPath) {
            // Use ImageMagick 6 'convert' command
            $command = sprintf(
                '%s%s -density 150 %s -quality 90 %s/%s_%%02d.png 2>&1',
                $envString,
                escapeshellarg($convertPath),
                escapeshellarg($pdfPath),
                escapeshellarg($outputDir),
                escapeshellarg($baseName)
            );
            exec($command);
            return;
        }
        
        // Try Ghostscript
        $gsPath = $this->findCommandInPath('gs');
        if ($gsPath) {
            $command = sprintf(
                '%s%s -dNOPAUSE -dBATCH -sDEVICE=png16m -r150 -dGraphicsAlphaBits=4 -dTextAlphaBits=4 -sOutputFile=%s/%s_%%02d.png %s 2>&1',
                $envString,
                escapeshellarg($gsPath),
                escapeshellarg($outputDir),
                escapeshellarg($baseName),
                escapeshellarg($pdfPath)
            );
            exec($command);
            return;
        }
        
        Log::warning('Neither ImageMagick nor Ghostscript available for PDF page extraction');
    }

    /**
     * Get relative storage path from absolute path
     *
     * @param string $absolutePath
     * @return string
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

