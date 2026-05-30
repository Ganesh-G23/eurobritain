<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_applications', function (Blueprint $table) {
            $table->string('type', 10)->nullable()->after('certificate_type_id');
        });

        DB::table('certificate_applications')->whereNull('type')->update(['type' => 'noiaf']);
    }

    public function down(): void
    {
        Schema::table('certificate_applications', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
