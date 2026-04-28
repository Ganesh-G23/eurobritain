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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('teacher_id');
            $table->string('remark');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('student_id')->references('id')->on('portal_user')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('portal_user')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('portal_user')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
