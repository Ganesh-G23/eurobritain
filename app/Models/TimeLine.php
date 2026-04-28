<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeLine extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'timelines';

    protected $fillable = [
        'teacher_id',
        'classroom_id',
        'batch_id',
        'topic',
        'start_date',
        'end_date',
        'status',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function teacher()
    {
        return $this->belongsTo(PortalUser::class, 'teacher_id');
    }
}
