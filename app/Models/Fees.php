<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fees extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'fees';

    protected $fillable = [
        'teacher_id',
        'classroom_id',
        'batch_id',
        'student_id',
        'amount',
        'payment_mode',
        'remark',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function student()
    {
        return $this->belongsTo(PortalUser::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(PortalUser::class, 'teacher_id');
    }
}
