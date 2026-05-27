<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('certificate_applications')) {
            return;
        }

        Schema::table('certificate_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('certificate_applications', 'date_of_expiry')) {
                $table->date('date_of_expiry')->nullable()->after('application_document');
            }
            if (! Schema::hasColumn('certificate_applications', 'audit_expiry_date')) {
                $table->date('audit_expiry_date')->nullable()->after('date_of_expiry');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('certificate_applications')) {
            return;
        }

        Schema::table('certificate_applications', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_applications', 'audit_expiry_date')) {
                $table->dropColumn('audit_expiry_date');
            }
            if (Schema::hasColumn('certificate_applications', 'date_of_expiry')) {
                $table->dropColumn('date_of_expiry');
            }
        });
    }
};
