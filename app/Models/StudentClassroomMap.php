<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentClassroomMap extends Model
{
    protected $table = 'student_classroom_map';

    protected $fillable = [
        'student_id',
        'teacher_id',
        'classroom_id',
        'batch_id',
    ];

    public function student()
    {
        return $this->belongsTo(PortalUser::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(PortalUser::class, 'teacher_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }
}
