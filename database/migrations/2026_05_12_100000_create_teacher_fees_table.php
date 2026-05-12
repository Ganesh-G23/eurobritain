<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('student_id');
            $table->decimal('amount', 12, 2);
            $table->string('payment_mode', 64);
            $table->text('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('teacher_id')->references('id')->on('portal_user')->cascadeOnDelete();
            $table->foreign('classroom_id')->references('id')->on('classrooms')->cascadeOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('portal_user')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
