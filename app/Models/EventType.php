<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'title',
        'color_code',
        'status',
    ];

    public function teacher()
    {
        return $this->belongsTo(PortalUser::class, 'teacher_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
