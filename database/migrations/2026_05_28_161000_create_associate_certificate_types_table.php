<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('associate_certificate_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('associate_id');
            $table->unsignedBigInteger('certificate_type_id');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('associate_certificate_types');
    }
};
