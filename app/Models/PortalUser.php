<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\SoftDeletes;

class PortalUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'portal_user';
    protected $guarded = [];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
        'classroom_id',
        'batch_id',
        'created_by',
        'parent_id',
        'default_teacher_id',
        'default_student_id',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Role constants
    const ROLE_TEACHER = 'teacher';
    const ROLE_STUDENT = 'student';
    const ROLE_PARENTS = 'parents';

    public static function getRoles()
    {
        return [
            self::ROLE_TEACHER => 'Teacher',
            self::ROLE_STUDENT => 'Student',
            self::ROLE_PARENTS => 'Parents',
        ];
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(PortalUser::class, 'created_by');
    }

    public function teachers()
    {
        return $this->belongsToMany(PortalUser::class, 'student_teacher_map', 'student_id', 'teacher_id');
    }

    public function students()
    {
        return $this->belongsToMany(PortalUser::class, 'student_teacher_map', 'teacher_id', 'student_id');
    }

    public function parents()
    {
        return $this->belongsToMany(PortalUser::class, 'parent_student_map', 'student_id', 'parent_id');
    }

    public function children()
    {
        return $this->belongsToMany(PortalUser::class, 'parent_student_map', 'parent_id', 'student_id');
    }
    


    // Password is hashed in controller before saving
}
