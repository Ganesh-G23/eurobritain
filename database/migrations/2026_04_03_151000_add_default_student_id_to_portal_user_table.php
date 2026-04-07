<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_user', function (Blueprint $table) {
            $table->unsignedBigInteger('default_student_id')->nullable()->after('default_teacher_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('portal_user', function (Blueprint $table) {
            $table->dropColumn('default_student_id');
        });
    }
};

