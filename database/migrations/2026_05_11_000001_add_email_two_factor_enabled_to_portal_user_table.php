<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_user', function (Blueprint $table) {
            $table->boolean('email_two_factor_enabled')->default(false)->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('portal_user', function (Blueprint $table) {
            $table->dropColumn('email_two_factor_enabled');
        });
    }
};
