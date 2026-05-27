<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientDocument extends Model
{
    use SoftDeletes;

    protected $table = 'client_documents';

    protected $guarded = [];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    protected function fileUrl(?string $name): ?string
    {
        return $name ? url('storage/app/uploads/temp/'.$name) : null;
    }

    public function getLegalProofUrlAttribute(): ?string
    {
        return $this->fileUrl($this->legal_proof);
    }

    public function getFactoryRegistrationUrlAttribute(): ?string
    {
        return $this->fileUrl($this->factory_registration);
    }

    public function getPurchaseBillsUrlAttribute(): ?string
    {
        return $this->fileUrl($this->purchase_bills);
    }

    public function getSalesBillsUrlAttribute(): ?string
    {
        return $this->fileUrl($this->sales_bills);
    }

    public function getStaffBiodataUrlAttribute(): ?string
    {
        return $this->fileUrl($this->staff_biodata);
    }

    public function getElectricityBillUrlAttribute(): ?string
    {
        return $this->fileUrl($this->electricity_bill);
    }

    public function getProductInspectionUrlAttribute(): ?string
    {
        return $this->fileUrl($this->product_inspection);
    }

    public function getEmployeeCompetenceMatrixUrlAttribute(): ?string
    {
        return $this->fileUrl($this->employee_competence_matrix);
    }

    public function getPreviousIsoCeCertificateUrlAttribute(): ?string
    {
        return $this->fileUrl($this->previous_iso_ce_certificate);
    }

    public function getSuppliersListUrlAttribute(): ?string
    {
        return $this->fileUrl($this->suppliers_list);
    }

    public function getProductCatalogueBrochureUrlAttribute(): ?string
    {
        return $this->fileUrl($this->product_catalogue_brochure);
    }

    public function getPollutionClearanceCertificateUrlAttribute(): ?string
    {
        return $this->fileUrl($this->pollution_clearance_certificate);
    }
}
