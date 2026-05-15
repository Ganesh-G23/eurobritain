<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class StudentBatchEnrollmentPeriod extends Model
{
    protected $table = 'student_batch_enrollment_periods';

    protected $fillable = [
        'student_id',
        'teacher_id',
        'classroom_id',
        'batch_id',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'teacher_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public static function closeOpenPeriodsForTeacherStudent(int $studentId, int $teacherId): void
    {
        if (! Schema::hasTable('student_batch_enrollment_periods')) {
            return;
        }
        static::query()->where('student_id', $studentId)->where('teacher_id', $teacherId)->whereNull('ended_at')->update([
            'ended_at' => now(),
        ]);
    }

    /**
     * When a student portal account is deleted globally from admin paths.
     */
    public static function closeOpenPeriodsForStudent(int $studentId): void
    {
        if (! Schema::hasTable('student_batch_enrollment_periods')) {
            return;
        }
        static::query()->where('student_id', $studentId)->whereNull('ended_at')->update([
            'ended_at' => now(),
        ]);
    }
}
