<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_batch_enrollment_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('batch_id');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'teacher_id', 'classroom_id'], 'sbep_student_teacher_classroom_idx');
            $table->foreign('student_id')->references('id')->on('portal_user')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('portal_user')->onDelete('cascade');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');
        });

        if (! Schema::hasTable('student_classroom_map')) {
            return;
        }

        $rows = DB::table('student_classroom_map')->get();
        $now = now();

        foreach ($rows as $row) {
            $started = isset($row->updated_at)
                ? (string) $row->updated_at
                : (($row->created_at ?? null) ? (string) $row->created_at : $now->toDateTimeString());

            DB::table('student_batch_enrollment_periods')->insert([
                'student_id' => $row->student_id,
                'teacher_id' => $row->teacher_id,
                'classroom_id' => $row->classroom_id,
                'batch_id' => $row->batch_id,
                'started_at' => $started,
                'ended_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_batch_enrollment_periods');
    }
};
