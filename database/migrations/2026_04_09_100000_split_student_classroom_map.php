<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_classroom_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('batch_id');
            $table->timestamps();

            $table->unique(['student_id', 'teacher_id', 'classroom_id', 'batch_id'], 'scm_student_teacher_classroom_batch_unique');
            $table->foreign('student_id')->references('id')->on('portal_user')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('portal_user')->onDelete('cascade');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');
        });

        if (! Schema::hasColumn('student_teacher_map', 'classroom_id')) {
            return;
        }

        $rows = DB::table('student_teacher_map')->orderBy('id')->get();
        $linkKeys = [];

        foreach ($rows as $row) {
            $sk = (int) $row->student_id;
            $tk = (int) $row->teacher_id;
            $linkKeys[$sk.'-'.$tk] = ['student_id' => $sk, 'teacher_id' => $tk];

            $cid = isset($row->classroom_id) ? (int) $row->classroom_id : 0;
            $bid = isset($row->batch_id) ? (int) $row->batch_id : 0;
            if ($cid > 0 && $bid > 0) {
                $now = now();
                DB::table('student_classroom_map')->insertOrIgnore([
                    'student_id' => $sk,
                    'teacher_id' => $tk,
                    'classroom_id' => $cid,
                    'batch_id' => $bid,
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $row->updated_at ?? $now,
                ]);
            }
        }

        Schema::table('student_teacher_map', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->dropForeign(['batch_id']);
        });

        DB::table('student_teacher_map')->delete();

        $now = now();
        foreach ($linkKeys as $link) {
            DB::table('student_teacher_map')->insert([
                'student_id' => $link['student_id'],
                'teacher_id' => $link['teacher_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('student_teacher_map', function (Blueprint $table) {
            $table->dropColumn(['classroom_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('student_teacher_map', function (Blueprint $table) {
            $table->unsignedBigInteger('classroom_id')->nullable()->after('teacher_id');
            $table->unsignedBigInteger('batch_id')->nullable()->after('classroom_id');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
        });

        $enrollments = DB::table('student_classroom_map')->orderBy('id')->get();
        DB::table('student_teacher_map')->delete();

        foreach ($enrollments as $e) {
            DB::table('student_teacher_map')->insertOrIgnore([
                'student_id' => $e->student_id,
                'teacher_id' => $e->teacher_id,
                'classroom_id' => $e->classroom_id,
                'batch_id' => $e->batch_id,
                'created_at' => $e->created_at ?? now(),
                'updated_at' => $e->updated_at ?? now(),
            ]);
        }

        Schema::dropIfExists('student_classroom_map');
    }
};
