<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('certificate_application_id');
            $table->string('certificate_number', 100)->unique();
            $table->unsignedBigInteger('associate_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('certificate_type_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->date('issue_date');
            $table->date('initial_certificate_granted_on')->nullable();
            $table->date('date_of_expiry')->nullable();
            $table->date('latest_audit_date')->nullable();
            $table->text('scope')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('certificate')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
