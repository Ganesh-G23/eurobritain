<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 20)->unique();
            $table->unsignedBigInteger('associate_id');
            $table->unsignedBigInteger('client_id');
            $table->json('certificate_ids');
            $table->date('invoice_date');
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
