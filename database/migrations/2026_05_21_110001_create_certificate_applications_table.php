<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('certificate_type_id');
            $table->string('company_name')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_mobile', 30)->nullable();
            $table->string('contact_email')->nullable();
            $table->text('scope')->nullable();
            $table->string('fax_number', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('communication_person')->nullable();
            $table->string('management_representative')->nullable();
            $table->string('top_manager')->nullable();
            $table->string('top_management_mobile', 30)->nullable();
            $table->json('director_details')->nullable();
            $table->json('employee_details')->nullable();
            $table->json('address_shift_details')->nullable();
            $table->string('subcontractor')->nullable();
            $table->string('in_main_process')->nullable();
            $table->string('executive_personnel')->nullable();
            $table->string('in_design')->nullable();
            $table->json('service_request_audit_type')->nullable();
            $table->string('application_document')->nullable();
            $table->date('date_of_expiry')->nullable();
            $table->date('audit_expiry_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_applications');
    }
};
