<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_teacher_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('teacher_id');
            $table->timestamps();

            $table->unique(['student_id', 'teacher_id']);
            $table->foreign('student_id')->references('id')->on('portal_user')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('portal_user')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_teacher_map');
    }
};
