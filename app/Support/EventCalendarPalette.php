<?php

namespace App\Support;

use App\Models\EventType;

/**
 * Calendar colors: semantic palette for student/parent views; teacher view uses DB color_code only.
 */
final class EventCalendarPalette
{
    private const SEMANTIC = [
        'holiday' => '#6f42c1',
        'task' => '#198754',
        'chapter_test' => '#fd7e14',
        'exam' => '#dc3545',
    ];

    /**
     * @return array{backgroundColor: string, borderColor: string, textColor: string}
     */
    public static function colorsForStudentView(?EventType $eventType): array
    {
        $semantic = self::semanticHexFromTitle($eventType?->title);
        if ($semantic !== null) {
            return self::finalizeColors($semantic);
        }

        return self::colorsFromTeacherColorCode($eventType);
    }

    /**
     * Teacher calendar: honor event_types.color_code only (existing behavior).
     *
     * @return array{backgroundColor: string, borderColor: string, textColor: string}
     */
    public static function colorsForTeacherView(?EventType $eventType): array
    {
        return self::colorsFromTeacherColorCode($eventType);
    }

    public static function personalEventColors(): array
    {
        return self::finalizeColors('#0dcaf0');
    }

    public static function eventTypeTitleMatchesReminderEligible(?string $title): bool
    {
        if ($title === null || trim($title) === '') {
            return false;
        }
        $t = mb_strtolower(trim($title));
        if (str_contains($t, 'holiday')) {
            return false;
        }
        if (preg_match('/\b(hw|homework)\b/u', $t) || str_contains($t, 'task')) {
            return true;
        }
        if (str_contains($t, 'chapter') && str_contains($t, 'test')) {
            return true;
        }
        if (preg_match('/\b(big\s*test|exam|unit\s*test)\b/u', $t)) {
            return true;
        }

        return false;
    }

    private static function semanticHexFromTitle(?string $title): ?string
    {
        if ($title === null || trim($title) === '') {
            return null;
        }
        $t = mb_strtolower(trim($title));
        if (str_contains($t, 'holiday')) {
            return self::SEMANTIC['holiday'];
        }
        if (preg_match('/\b(hw|homework)\b/u', $t) || (str_contains($t, 'task') && ! str_contains($t, 'holiday'))) {
            return self::SEMANTIC['task'];
        }
        if (str_contains($t, 'chapter') && str_contains($t, 'test')) {
            return self::SEMANTIC['chapter_test'];
        }
        if (preg_match('/\b(big\s*test|exam|unit\s*test)\b/u', $t)) {
            return self::SEMANTIC['exam'];
        }

        return null;
    }

    /**
     * @return array{backgroundColor: string, borderColor: string, textColor: string}
     */
    private static function colorsFromTeacherColorCode(?EventType $eventType): array
    {
        $raw = $eventType && $eventType->color_code !== null ? trim((string) $eventType->color_code) : '';
        $raw = preg_replace('/\s+/', '', $raw) ?? '';

        if ($raw === '') {
            $bg = '#696cff';
        } elseif (preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/i', $raw)) {
            $hex = ltrim($raw, '#');
            if (strlen($hex) === 3) {
                $bg = sprintf('#%s%s%s%s%s%s', $hex[0], $hex[0], $hex[1], $hex[1], $hex[2], $hex[2]);
            } else {
                $bg = '#'.strtolower(substr($hex, 0, 6));
            }
        } elseif (preg_match('/^[a-z]+$/i', $raw)) {
            $bg = strtolower($raw);
        } else {
            $bg = '#696cff';
        }

        return self::finalizeColors($bg);
    }

    /**
     * @return array{backgroundColor: string, borderColor: string, textColor: string}
     */
    private static function finalizeColors(string $bg): array
    {
        $text = '#ffffff';
        if (preg_match('/^#([0-9a-fA-F]{6})$/', $bg)) {
            $r = hexdec(substr($bg, 1, 2));
            $g = hexdec(substr($bg, 3, 2));
            $b = hexdec(substr($bg, 5, 2));
            $lum = ($r * 0.299 + $g * 0.587 + $b * 0.114) / 255;
            $text = $lum > 0.65 ? '#212529' : '#ffffff';
        }

        return [
            'backgroundColor' => $bg,
            'borderColor' => $bg,
            'textColor' => $text,
        ];
    }
}
