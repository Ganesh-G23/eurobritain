<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Str;

class CertificateImageGenerator
{
    private const UPLOAD_DIR = 'app/uploads/temp';

    public function generate(Certificate $cert): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $certificateType = $cert->certificateType;
        $templateFile = $certificateType?->certificate_template;
        $coords = $certificateType?->template_coords ?? [];

        if (! $templateFile || empty($coords)) {
            return null;
        }

        $templatePath = storage_path(self::UPLOAD_DIR.'/'.$templateFile);
        if (! is_file($templatePath)) {
            return null;
        }

        $image = $this->loadImage($templatePath);
        if (! $image) {
            return null;
        }

        $values = $this->buildValuesMap($cert);

        foreach ($coords as $field => $config) {
            if (! is_array($config) || empty($values[$field])) {
                continue;
            }

            $this->drawField($image, (string) $values[$field], $config);
        }

        $filename = Str::random(40).'-cert-'.$cert->id.'.png';
        $outputPath = storage_path(self::UPLOAD_DIR.'/'.$filename);

        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        $saved = imagepng($image, $outputPath);
        imagedestroy($image);

        return $saved ? $filename : null;
    }

    /**
     * @return array<string, string>
     */
    private function buildValuesMap(Certificate $cert): array
    {
        $application = $cert->certificateApplication;

        return [
            'company_name' => (string) ($cert->client->company_name ?? ''),
            'company_address' => (string) ($cert->client->address ?? ''),
            'scope' => (string) ($cert->scope ?: ($application->scope ?? '')),
            'certificate_number' => (string) ($cert->certificate_number ?? ''),
            'issue_date' => $cert->issue_date?->format('d/m/Y') ?? '',
            'date_of_expiry' => $cert->date_of_expiry?->format('d/m/Y') ?? '',
            'next_audit_date' => $application?->audit_expiry_date?->format('d/m/Y') ?? '',
        ];
    }

    /**
     * @param  resource  $image
     * @param  array<string, mixed>  $config
     */
    private function drawField($image, string $text, array $config): void
    {
        $maxSize = max(6, (int) ($config['size'] ?? 12));
        $align = in_array($config['align'] ?? 'left', ['left', 'center', 'right'], true)
            ? $config['align']
            : 'left';
        $valign = in_array($config['valign'] ?? 'middle', ['top', 'middle', 'bottom'], true)
            ? $config['valign']
            : 'middle';
        $color = $this->parseColor($image, (string) ($config['color'] ?? '#000000'));
        $bold = ! empty($config['bold']);

        $fontPath = $this->resolveFontPath($bold);
        if (! $fontPath) {
            return;
        }

        $hasBox = isset($config['w'], $config['h']);
        $boxX = (int) ($config['x'] ?? 0);
        $boxY = (int) ($config['y'] ?? 0);
        $boxW = $hasBox ? (int) $config['w'] : 0;
        $boxH = $hasBox ? (int) $config['h'] : 0;

        if (! $hasBox) {
            $this->drawAnchored($image, $text, $fontPath, $maxSize, $boxX, $boxY, $align, $color);

            return;
        }

        $fit = $this->fitText($text, $fontPath, $maxSize, $boxW, $boxH);
        if ($fit === null) {
            return;
        }

        [$size, $lines, $ascent, $descent, $lineHeight] = $fit;

        $blockHeight = $lineHeight * (max(1, count($lines)) - 1) + ($ascent + $descent);

        $blockTop = match ($valign) {
            'top' => $boxY,
            'bottom' => $boxY + $boxH - $blockHeight,
            default => $boxY + ($boxH - $blockHeight) / 2,
        };

        $currentBaseline = $blockTop + $ascent;

        foreach ($lines as $line) {
            $box = imagettfbbox($size, 0, $fontPath, $line);
            $textWidth = abs($box[2] - $box[0]);

            $drawX = match ($align) {
                'center' => $boxX + ($boxW - $textWidth) / 2,
                'right' => $boxX + $boxW - $textWidth,
                default => $boxX,
            };

            imagettftext($image, $size, 0, (int) round($drawX), (int) round($currentBaseline), $color, $fontPath, $line);
            $currentBaseline += $lineHeight;
        }
    }

    /**
     * Iteratively shrink the font until both the wrapped width and total height fit the box.
     *
     * @return array{0:int,1:array<int,string>,2:int,3:int,4:int}|null
     */
    private function fitText(string $text, string $fontPath, int $maxSize, int $boxW, int $boxH): ?array
    {
        for ($size = $maxSize; $size >= 6; $size--) {
            $lines = $this->wrapText($text, $fontPath, $size, $boxW);

            $fits = true;
            foreach ($lines as $line) {
                $box = imagettfbbox($size, 0, $fontPath, $line !== '' ? $line : 'Ag');
                if (abs($box[2] - $box[0]) > $boxW) {
                    $fits = false;
                    break;
                }
            }
            if (! $fits) {
                continue;
            }

            $sampleBox = imagettfbbox($size, 0, $fontPath, $lines[0] !== '' ? $lines[0] : 'Ag');
            $ascent = -$sampleBox[7];
            $descent = $sampleBox[1];
            $lineHeight = (int) round($size * 1.25);

            $blockHeight = $lineHeight * (max(1, count($lines)) - 1) + ($ascent + $descent);
            if ($blockHeight <= $boxH) {
                return [$size, $lines, $ascent, $descent, $lineHeight];
            }
        }

        $size = 6;
        $lines = $this->wrapText($text, $fontPath, $size, $boxW);
        $sampleBox = imagettfbbox($size, 0, $fontPath, $lines[0] !== '' ? $lines[0] : 'Ag');
        $ascent = -$sampleBox[7];
        $descent = $sampleBox[1];
        $lineHeight = (int) round($size * 1.25);

        $maxLines = max(1, (int) floor(($boxH - $descent) / $lineHeight));
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
        }

        return [$size, $lines, $ascent, $descent, $lineHeight];
    }

    /**
     * Legacy point-anchor draw, used only when w/h are not configured.
     *
     * @param  resource  $image
     */
    private function drawAnchored($image, string $text, string $fontPath, int $size, int $x, int $y, string $align, int $color): void
    {
        $box = imagettfbbox($size, 0, $fontPath, $text !== '' ? $text : 'Ag');
        $ascent = -$box[7];
        $descent = $box[1];
        $textWidth = abs($box[2] - $box[0]);

        $drawX = match ($align) {
            'center' => $x - $textWidth / 2,
            'right' => $x - $textWidth,
            default => $x,
        };

        $blockHeight = $ascent + $descent;
        $baselineY = $y - ($blockHeight / 2) + $ascent;

        imagettftext($image, $size, 0, (int) round($drawX), (int) round($baselineY), $color, $fontPath, $text);
    }

    /**
     * @return array<int, string>
     */
    private function wrapText(string $text, string $fontPath, int $size, int $maxWidth): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        if ($words === []) {
            return [''];
        }

        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $test = $current === '' ? $word : $current.' '.$word;
            $box = imagettfbbox($size, 0, $fontPath, $test);
            $width = abs($box[2] - $box[0]);

            if ($width > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $test;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [''];
    }

    /**
     * @param  resource  $image
     */
    private function parseColor($image, string $hex): int
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return imagecolorallocate($image, 0, 0, 0);
        }

        return imagecolorallocate(
            $image,
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        );
    }

    private function resolveFontPath(bool $bold): ?string
    {
        $candidates = [
            base_path('vendor/mpdf/mpdf/ttfonts/'.($bold ? 'DejaVuSans-Bold.ttf' : 'DejaVuSans.ttf')),
            base_path('vendor/mpdf/mpdf/ttfonts/'.($bold ? 'dejavusans-bold.ttf' : 'dejavusans.ttf')),
            'C:/Windows/Fonts/'.($bold ? 'arialbd.ttf' : 'arial.ttf'),
            'C:/Windows/Fonts/'.($bold ? 'ARIALBD.TTF' : 'ARIAL.TTF'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return resource|null
     */
    private function loadImage(string $path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'png' => @imagecreatefrompng($path) ?: null,
            'jpg', 'jpeg' => @imagecreatefromjpeg($path) ?: null,
            'gif' => @imagecreatefromgif($path) ?: null,
            'webp' => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null,
            default => null,
        };
    }
}
