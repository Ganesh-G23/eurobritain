<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkAbsence extends Model
{
    protected $table = 'mark_absences';

    protected $fillable = [
        'exam_id',
        'student_id',
        'value',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'student_id');
    }
}
