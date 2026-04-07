<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_teacher_map', function (Blueprint $table) {
            $table->unsignedBigInteger('classroom_id')->nullable()->after('teacher_id');
            $table->unsignedBigInteger('batch_id')->nullable()->after('classroom_id');

            $table->foreign('classroom_id')->references('id')->on('classrooms')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
        });

        $rows = DB::table('student_teacher_map')->get(['id', 'student_id']);
        foreach ($rows as $row) {
            $student = DB::table('portal_user')->where('id', $row->student_id)->first(['classroom_id', 'batch_id']);
            if ($student) {
                DB::table('student_teacher_map')->where('id', $row->id)->update([
                    'classroom_id' => $student->classroom_id,
                    'batch_id' => $student->batch_id,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('student_teacher_map', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->dropForeign(['batch_id']);
            $table->dropColumn(['classroom_id', 'batch_id']);
        });
    }
};
