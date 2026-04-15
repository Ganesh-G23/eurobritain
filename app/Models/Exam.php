<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use SoftDeletes;

    protected $table = 'exams';

    protected $fillable = [
        'batch_id',
        'exam_name',
        'max_marks',
        'exam_date',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'datetime',
        ];
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function marks()
    {
        return $this->hasMany(Mark::class, 'exam_id');
    }

    public function markAbsences()
    {
        return $this->hasMany(MarkAbsence::class, 'exam_id');
    }
}
