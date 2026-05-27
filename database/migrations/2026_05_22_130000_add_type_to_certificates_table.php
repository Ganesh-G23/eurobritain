<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('certificates')) {
            return;
        }

        Schema::table('certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('certificates', 'type')) {
                $table->string('type', 20)->default('certificate')->after('certificate_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('certificates')) {
            return;
        }

        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
