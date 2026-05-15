<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_settings', function (Blueprint $table) {
            $table->boolean('leaderboard_visible')->default(true)->after('count_setting');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_settings', function (Blueprint $table) {
            $table->dropColumn('leaderboard_visible');
        });
    }
};
