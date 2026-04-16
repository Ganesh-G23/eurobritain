<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'color_code',
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
