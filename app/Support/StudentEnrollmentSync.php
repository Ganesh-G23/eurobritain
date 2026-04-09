<?php

namespace App\Support;

use App\Models\Batch;
use App\Models\Classroom;
use App\Models\PortalUser;
use App\Models\StudentClassroomMap;
use App\Models\StudentTeacherMap;
use Illuminate\Support\Facades\DB;

class StudentEnrollmentSync
{
    /**
     * @param  array<int, array{classroom_id: int, batch_id: int}>  $pairs
     */
    public static function syncForTeacher(int $studentId, int $teacherId, array $pairs): void
    {
        DB::transaction(function () use ($studentId, $teacherId, $pairs) {
            StudentClassroomMap::where('student_id', $studentId)
                ->where('teacher_id', $teacherId)
                ->delete();

            foreach ($pairs as $p) {
                StudentClassroomMap::create([
                    'student_id' => $studentId,
                    'teacher_id' => $teacherId,
                    'classroom_id' => $p['classroom_id'],
                    'batch_id' => $p['batch_id'],
                ]);
            }

            if (count($pairs) > 0) {
                StudentTeacherMap::firstOrCreate(
                    [
                        'student_id' => $studentId,
                        'teacher_id' => $teacherId,
                    ]
                );
                $first = $pairs[0];
                PortalUser::where('id', $studentId)->where('role', 2)->update([
                    'classroom_id' => $first['classroom_id'],
                    'batch_id' => $first['batch_id'],
                ]);
            } else {
                StudentTeacherMap::where('student_id', $studentId)
                    ->where('teacher_id', $teacherId)
                    ->delete();
            }
        });
    }

    /**
     * @return array{0: bool, 1: string|null}
     */
    public static function validatePairsForTeacher(int $teacherId, array $pairs): array
    {
        foreach ($pairs as $p) {
            $cid = (int) ($p['classroom_id'] ?? 0);
            $bid = (int) ($p['batch_id'] ?? 0);
            if ($cid < 1 || $bid < 1) {
                return [false, 'Each row needs a valid classroom and batch.'];
            }
            $c = Classroom::where('teacher_id', $teacherId)->find($cid);
            $b = Batch::where('teacher_id', $teacherId)->find($bid);
            if (! $c || ! $b || (int) $b->classroom_id !== (int) $c->id) {
                return [false, 'Invalid classroom/batch combination for this teacher.'];
            }
        }

        return [true, null];
    }

    /**
     * @return array<int, array{classroom_id: int, batch_id: int}>
     */
    public static function pairsFromRequestArrays(array $classroomIds, array $batchIds): array
    {
        $classroomIds = array_values(array_filter($classroomIds, static fn ($v) => $v !== null && $v !== ''));
        $batchIds = array_values(array_filter($batchIds, static fn ($v) => $v !== null && $v !== ''));

        if (count($classroomIds) !== count($batchIds)) {
            return [];
        }

        $pairs = [];
        for ($i = 0; $i < count($classroomIds); $i++) {
            $cid = (int) $classroomIds[$i];
            $bid = (int) $batchIds[$i];
            if ($cid < 1 || $bid < 1) {
                continue;
            }
            $pairs[(string) $cid.'-'.(string) $bid] = ['classroom_id' => $cid, 'batch_id' => $bid];
        }

        return array_values($pairs);
    }
}
