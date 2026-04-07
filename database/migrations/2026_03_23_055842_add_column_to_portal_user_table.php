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
        Schema::table('portal_user', function (Blueprint $table) {
            $table->string('p')->after('password')->nullable();
            $table->integer('is_password_changed')->default(0)->after('p');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_user', function (Blueprint $table) {
            $table->dropColumn('p');
            $table->dropColumn('is_password_changed');
        });
    }
};
