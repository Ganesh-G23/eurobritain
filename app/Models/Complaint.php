<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'parent_id',
        'teacher_id',
        'remark',
    ];

    public function student()
    {
        return $this->belongsTo(PortalUser::class, 'student_id');
    }

    public function parent()
    {
        return $this->belongsTo(PortalUser::class, 'parent_id');
    }

    public function teacher()
    {
        return $this->belongsTo(PortalUser::class, 'teacher_id');
    }
}
