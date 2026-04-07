<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentStudentMap extends Model
{
    protected $table = 'parent_student_map';

    protected $fillable = [
        'parent_id',
        'student_id',
    ];
}
