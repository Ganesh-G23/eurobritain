<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_types', function (Blueprint $table) {
            $table->json('template_coords')->nullable()->after('certificate_template');
        });
    }

    public function down(): void
    {
        Schema::table('certificate_types', function (Blueprint $table) {
            $table->dropColumn('template_coords');
        });
    }
};
