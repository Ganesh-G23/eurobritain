<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class AmpWebStoryService
{
    /**
     * Generate AMP Web Story HTML from slide images
     *
     * @param array $slides Array of slide data with 'url' and 'image' keys
     * @param string $title Story title
     * @param string $publisher Publisher name (optional)
     * @param string $publisherLogo Publisher logo URL (optional)
     * @param string $posterImage Poster image URL (optional, uses first slide if not provided)
     * @return string AMP Web Story HTML
     */
    public function generateAmpWebStory($slides, $title, $publisher = 'elitegrade', $publisherLogo = null, $posterImage = null)
    {
        if (empty($slides)) {
            return '';
        }

        // Use first slide as poster if not provided
        if (!$posterImage && !empty($slides[0]['url'])) {
            $posterImage = $slides[0]['url'];
        }

        // Build AMP Web Story HTML
        $html = '<!DOCTYPE html>' . "\n";
        $html .= '<html ⚡>' . "\n";
        $html .= '<head>' . "\n";
        $html .= '    <meta charset="utf-8">' . "\n";
        $html .= '    <title>' . htmlspecialchars($title) . '</title>' . "\n";
        $html .= '    <link rel="canonical" href="">' . "\n";
        $html .= '    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">' . "\n";
        $html .= '    <script async src="https://cdn.ampproject.org/v0.js"></script>' . "\n";
        $html .= '    <script async custom-element="amp-story" src="https://cdn.ampproject.org/v0/amp-story-1.0.js"></script>' . "\n";
        $html .= '    <style amp-custom>' . "\n";
        $html .= '        body { margin: 0; padding: 0; }' . "\n";
        $html .= '    </style>' . "\n";
        $html .= '</head>' . "\n";
        $html .= '<body>' . "\n";
        $html .= '    <amp-story standalone' . "\n";
        $html .= '        title="' . htmlspecialchars($title) . '"' . "\n";
        $html .= '        publisher="' . htmlspecialchars($publisher) . '"' . "\n";
        
        if ($publisherLogo) {
            $html .= '        publisher-logo-src="' . htmlspecialchars($publisherLogo) . '"' . "\n";
        }
        
        if ($posterImage) {
            $html .= '        poster-portrait-src="' . htmlspecialchars($posterImage) . '"' . "\n";
        }
        
        $html .= '        >' . "\n";

        // Add each slide as a page
        foreach ($slides as $index => $slide) {
            $slideId = 'slide' . ($index + 1);
            $imageUrl = $slide['url'] ?? '';
            
            if (!$imageUrl) {
                continue;
            }

            $html .= '        <!-- Slide ' . ($index + 1) . ' -->' . "\n";
            $html .= '        <amp-story-page id="' . $slideId . '">' . "\n";
            $html .= '            <amp-story-grid-layer template="fill">' . "\n";
            $html .= '                <amp-img src="' . htmlspecialchars($imageUrl) . '" width="720" height="1280" layout="responsive" alt="Slide ' . ($index + 1) . '"></amp-img>' . "\n";
            $html .= '            </amp-story-grid-layer>' . "\n";
            $html .= '        </amp-story-page>' . "\n";
            $html .= '' . "\n";
        }

        $html .= '    </amp-story>' . "\n";
        $html .= '</body>' . "\n";
        $html .= '</html>';

        return $html;
    }

    /**
     * Save AMP Web Story HTML to file
     *
     * @param string $html AMP Web Story HTML
     * @param int $storyId Story ID
     * @return string Path to saved file
     */
    public function saveAmpWebStory($html, $storyId)
    {
        $filename = 'amp_story_' . $storyId . '.html';
        $path = 'uploads/web_stories/amp/' . $filename;
        
        // Ensure directory exists
        $fullPath = Storage::path($path);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Save HTML file
        Storage::put($path, $html);
        
        return $path;
    }

    /**
     * Get AMP Web Story URL
     *
     * @param int $storyId Story ID
     * @return string|null URL to AMP Web Story or null if not found
     */
    public function getAmpWebStoryUrl($storyId)
    {
        $filename = 'amp_story_' . $storyId . '.html';
        $path = 'uploads/web_stories/amp/' . $filename;
        
        if (Storage::exists($path)) {
            return url('storage/app/' . $path);
        }
        
        return null;
    }
}

