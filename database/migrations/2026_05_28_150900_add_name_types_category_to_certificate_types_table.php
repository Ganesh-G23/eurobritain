<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_types', function (Blueprint $table) {
            $table->string('name', 150)->nullable()->after('id');
            $table->json('types')->nullable()->after('description');
            $table->string('category', 100)->nullable()->after('types');
        });
    }

    public function down(): void
    {
        Schema::table('certificate_types', function (Blueprint $table) {
            $table->dropColumn(['name', 'types', 'category']);
        });
    }
};
