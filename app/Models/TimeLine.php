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
        'batch_ids',
        'topic',
        'date',
    ];

    protected $casts = [
        'batch_ids' => 'array',
        'date' => 'date',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function teacher()
    {
        return $this->belongsTo(PortalUser::class, 'teacher_id');
    }

    /**
     * @return int[]
     */
    public function normalizedBatchIds(): array
    {
        return collect($this->batch_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function appliesToBatch(int $batchId): bool
    {
        return in_array($batchId, $this->normalizedBatchIds(), true);
    }
}
