<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificate extends Model
{
    use SoftDeletes;

    public const TYPE_CERTIFICATE = 'certificate';

    public const TYPE_AUDIT = 'audit';

    protected $table = 'certificates';

    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'initial_certificate_granted_on' => 'date',
        'date_of_expiry' => 'date',
        'latest_audit_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function certificateApplication(): BelongsTo
    {
        return $this->belongsTo(CertificateApplication::class, 'certificate_application_id');
    }

    public function associate(): BelongsTo
    {
        return $this->belongsTo(Associate::class, 'associate_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function certificateType(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class, 'certificate_type_id');
    }

    public function getCertificateUrlAttribute(): ?string
    {
        return $this->certificate
            ? url('storage/app/uploads/temp/'.$this->certificate)
            : null;
    }
}
