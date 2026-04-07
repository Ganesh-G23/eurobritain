<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_student_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('student_id');
            $table->timestamps();

            $table->unique(['parent_id', 'student_id']);
            $table->foreign('parent_id')->references('id')->on('portal_user')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('portal_user')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_student_map');
    }
};
