<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_personal_events')) {
            return;
        }

        Schema::create('student_personal_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->boolean('all_day')->default(true);
            $table->boolean('reminder_eligible')->default(false);
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('portal_user')->onDelete('cascade');
            $table->index(['student_id', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_personal_events');
    }
};
