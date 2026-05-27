<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->string('legal_proof')->nullable();
            $table->string('pan')->nullable();
            $table->string('msme_udyog_aadhar')->nullable();
            $table->string('gstn')->nullable();
            $table->string('factory_registration')->nullable();
            $table->string('purchase_bills')->nullable();
            $table->string('sales_bills')->nullable();
            $table->string('staff_biodata')->nullable();
            $table->string('electricity_bill')->nullable();
            $table->string('product_inspection')->nullable();
            $table->string('employee_competence_matrix')->nullable();
            $table->string('previous_iso_ce_certificate')->nullable();
            $table->string('suppliers_list')->nullable();
            $table->string('product_catalogue_brochure')->nullable();
            $table->string('pollution_clearance_certificate')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};
