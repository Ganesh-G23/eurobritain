<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = ['teacher_id', 'event_type_id', 'title', 'description', 'start_date', 'end_date', 'status'];


    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'event_classrooms');
    }

    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'event_batches');
    }
}
