<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class TeacherSetting extends Model
{
    public const COUNT_AS_ZERO = 1;

    public const EXCLUDE_FROM_OVERALL_PERCENTAGE = 2;

    protected $table = 'teacher_settings';

    protected $fillable = [
        'teacher_id',
        'count_setting',
        'leaderboard_visible',
    ];

    protected function casts(): array
    {
        return [
            'count_setting' => 'integer',
            'leaderboard_visible' => 'boolean',
        ];
    }

    public static function isLeaderboardVisibleForTeacher(int $teacherId): bool
    {
        if ($teacherId <= 0) {
            return false;
        }

        if (! Schema::hasTable('teacher_settings')) {
            return true;
        }

        if (! Schema::hasColumn('teacher_settings', 'leaderboard_visible')) {
            return true;
        }

        $row = static::query()->where('teacher_id', $teacherId)->first();

        if (! $row) {
            return true;
        }

        return (bool) ($row->leaderboard_visible ?? true);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'teacher_id');
    }
}
