<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSetting extends Model
{
    public const COUNT_AS_ZERO = 1;

    public const EXCLUDE_FROM_OVERALL_PERCENTAGE = 2;

    protected $table = 'teacher_settings';

    protected $fillable = [
        'teacher_id',
        'count_setting',
    ];

    protected function casts(): array
    {
        return [
            'count_setting' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'teacher_id');
    }
}
