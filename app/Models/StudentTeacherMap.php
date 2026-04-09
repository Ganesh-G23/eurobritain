<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentTeacherMap extends Model
{
    protected $table = 'student_teacher_map';

    protected $fillable = [
        'student_id',
        'teacher_id',
    ];
}
