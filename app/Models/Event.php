<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'event_type_id',
        'batch_id',
        'classroom_id',
        'all_classrooms',
        'all_batches',
        'title',
        'description',
        'event_date',
        'event_time',
        'status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'status' => 'integer',
        'all_classrooms' => 'boolean',
        'all_batches' => 'boolean',
    ];

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
