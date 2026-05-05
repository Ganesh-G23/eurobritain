<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPersonalEvent extends Model
{
    protected $fillable = [
        'student_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'all_day',
        'reminder_eligible',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'all_day' => 'boolean',
            'reminder_eligible' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'student_id');
    }
}
