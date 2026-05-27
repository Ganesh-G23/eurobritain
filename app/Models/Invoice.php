<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';

    protected $guarded = [];

    protected $casts = [
        'invoice_date' => 'date',
        'amount' => 'decimal:2',
        'certificate_ids' => 'array',
    ];

    public function associate(): BelongsTo
    {
        return $this->belongsTo(Associate::class, 'associate_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Resolve line-item certificates from stored JSON ids (not an Eloquent relation).
     */
    public function certificates(): Collection
    {
        $ids = array_values(array_filter(array_map('intval', $this->certificate_ids ?? [])));

        if ($ids === []) {
            return new Collection;
        }

        return Certificate::query()
            ->with('certificateType:id,description,code')
            ->whereIn('id', $ids)
            ->orderByRaw('FIELD(id, '.implode(',', $ids).')')
            ->get();
    }
}
