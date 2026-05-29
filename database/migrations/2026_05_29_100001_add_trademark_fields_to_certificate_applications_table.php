<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_applications', function (Blueprint $table) {
            $table->string('trademark_name', 255)->nullable()->after('in_design');
            $table->string('trademark_application_number', 255)->nullable()->after('trademark_name');
            $table->string('trademark_image', 255)->nullable()->after('trademark_application_number');
        });
    }

    public function down(): void
    {
        Schema::table('certificate_applications', function (Blueprint $table) {
            $table->dropColumn([
                'trademark_name',
                'trademark_application_number',
                'trademark_image',
            ]);
        });
    }
};
