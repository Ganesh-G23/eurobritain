<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'teacher_id',
    ];

    public function teacher()
    {
        return $this->belongsTo(PortalUser::class, 'teacher_id');
    }

    public function batches()
    {
        return $this->hasMany(Batch::class, 'classroom_id');
    }

    public function students()
    {
        return $this->hasMany(PortalUser::class, 'classroom_id');
    }
}
