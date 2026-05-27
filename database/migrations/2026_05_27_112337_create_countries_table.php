<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('continent', 10)->nullable();
            $table->string('currency', 100)->nullable();
            $table->string('iso_currency_code', 10)->nullable();
            $table->string('phone_prefix', 50)->nullable();
            $table->string('iso', 2)->nullable();
            $table->integer('status')->default(1);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
