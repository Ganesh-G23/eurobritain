<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateApplication extends Model
{
    use SoftDeletes;

    protected $table = 'certificate_applications';

    protected $guarded = [];

    protected $casts = [
        'director_details' => 'array',
        'employee_details' => 'array',
        'address_shift_details' => 'array',
        'service_request_audit_type' => 'array',
        'date_of_expiry' => 'date',
        'audit_expiry_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function certificateType(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class, 'certificate_type_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'certificate_application_id');
    }

    public function getApplicationDocumentUrlAttribute(): ?string
    {
        return $this->application_document
            ? url('storage/app/uploads/temp/'.$this->application_document)
            : null;
    }

    public function getTrademarkImageUrlAttribute(): ?string
    {
        return $this->trademark_image
            ? url('storage/app/uploads/temp/'.$this->trademark_image)
            : null;
    }
}
