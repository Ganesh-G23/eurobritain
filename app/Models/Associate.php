<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Associate extends Model
{
    use SoftDeletes;

    protected $table = 'associates';

    protected $guarded = [];

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'associate_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'associate_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'associate_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'associate_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}
