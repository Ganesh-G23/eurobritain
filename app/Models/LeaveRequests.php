<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequests extends Model
{
    use SoftDeletes;
    use Notifiable;

    /**
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'student_id',
        'teacher_id',
        'classroom_id',
        'batch_id',
        'from_date',
        'to_date',
        'reason',
        'status',
        'reject_reason',
    ];

    public function student()
    {
        return $this->belongsTo(PortalUser::class, 'student_id');
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
