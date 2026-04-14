<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'classroom_id',
        'teacher_id',
        'status',
        'schedule',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function teacher()
    {
        return $this->belongsTo(PortalUser::class, 'teacher_id');
    }

    public function students()
    {
        return $this->hasMany(PortalUser::class, 'batch_id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'batch_id');
    }
}
