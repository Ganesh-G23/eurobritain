<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('recovery_email')->nullable()->after('email');
            $table->boolean('email_two_factor_enabled')->default(false)->after('remember_token');
            $table->boolean('force_password_change')->default(false)->after('email_two_factor_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['recovery_email', 'email_two_factor_enabled', 'force_password_change']);
        });
    }
};
