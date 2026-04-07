<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $students = DB::table('portal_user')->where('role', 2)->get(['id', 'batch_id', 'parent_id']);
        foreach ($students as $student) {
            $teacherId = null;
            if (!empty($student->batch_id)) {
                $teacherId = DB::table('batches')->where('id', $student->batch_id)->value('teacher_id');
            }
            if ($teacherId) {
                DB::table('student_teacher_map')->updateOrInsert(
                    ['student_id' => $student->id, 'teacher_id' => $teacherId],
                    ['updated_at' => now(), 'created_at' => now()]
                );
            }
            if (!empty($student->parent_id)) {
                DB::table('parent_student_map')->updateOrInsert(
                    ['parent_id' => $student->parent_id, 'student_id' => $student->id],
                    ['updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        // Keep mapping data on rollback to avoid accidental relationship loss.
    }
};
