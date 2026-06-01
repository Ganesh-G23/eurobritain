<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateType extends Model
{
    use SoftDeletes;

    protected $table = 'certificate_types';

    protected $guarded = [];

    protected $casts = [
        'types' => 'array',
        'template_coords' => 'array',
    ];

    public function associates(): BelongsToMany
    {
        return $this->belongsToMany(
            Associate::class,
            'associate_certificate_types',
            'certificate_type_id',
            'associate_id'
        )->withPivot('amount')->withTimestamps();
    }

    public function getCertificateTemplateUrlAttribute(): ?string
    {
        return $this->certificate_template
            ? url('storage/app/uploads/temp/'.$this->certificate_template)
            : null;
    }
}
