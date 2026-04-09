<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            /** 1 = Count as 0, 2 = Exclude from overall percentage */
            $table->unsignedTinyInteger('count_setting')->nullable();
            $table->timestamps();

            $table->unique('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('portal_user')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_settings');
    }
};
