<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PortalUser extends Model
{
    use CanResetPassword, HasFactory, Notifiable, SoftDeletes;

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

    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = route('web.password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        try {
            Mail::send('web.emails.password_reset', [
                'user' => $this,
                'resetUrl' => $resetUrl,
            ], function ($message) {
                $message->to((string) $this->getEmailForPasswordReset())
                    ->subject('Reset your password');
            });
        } catch (\Throwable $e) {
            Log::error('Portal password reset email failed: '.$e->getMessage());
            throw $e;
        }
    }
}
