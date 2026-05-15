<?php

namespace App\Support;

use App\Models\Batch;
use App\Models\Classroom;
use App\Models\PortalUser;
use App\Models\StudentBatchEnrollmentPeriod;
use App\Models\StudentClassroomMap;
use App\Models\StudentTeacherMap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentEnrollmentSync
{
    /**
     * @param  array<int, array{classroom_id: int, batch_id: int}>  $pairs
     */
    public static function syncForTeacher(int $studentId, int $teacherId, array $pairs): void
    {
        DB::transaction(function () use ($studentId, $teacherId, $pairs) {
            $oldByClassroom = StudentClassroomMap::query()
                ->where('student_id', $studentId)
                ->where('teacher_id', $teacherId)
                ->orderBy('classroom_id')
                ->get(['classroom_id', 'batch_id'])
                ->keyBy(fn ($row) => (int) $row->classroom_id)
                ->map(fn ($row) => (int) $row->batch_id)
                ->all();

            $newByClassroom = [];
            foreach ($pairs as $p) {
                $newByClassroom[(int) $p['classroom_id']] = (int) $p['batch_id'];
            }

            self::reconcileEnrollmentPeriods($studentId, $teacherId, $oldByClassroom, $newByClassroom);

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
                    'default_teacher_id' => $teacherId,
                ]);
            } else {
                StudentTeacherMap::where('student_id', $studentId)
                    ->where('teacher_id', $teacherId)
                    ->delete();
            }
        });
    }

    /**
     * @param  array<int, int>  $oldByClassroom  classroom_id => batch_id
     * @param  array<int, int>  $newByClassroom
     */
    private static function reconcileEnrollmentPeriods(
        int $studentId,
        int $teacherId,
        array $oldByClassroom,
        array $newByClassroom
    ): void {
        if (! Schema::hasTable('student_batch_enrollment_periods')) {
            return;
        }

        $now = now();
        $classroomIds = array_values(array_unique(array_merge(
            array_map('intval', array_keys($oldByClassroom)),
            array_map('intval', array_keys($newByClassroom)),
        )));

        foreach ($classroomIds as $cid) {
            $oldBid = $oldByClassroom[$cid] ?? null;
            $newBid = $newByClassroom[$cid] ?? null;

            if ($oldBid !== null && $newBid !== null && (int) $oldBid === (int) $newBid) {
                continue;
            }

            if ($oldBid !== null) {
                StudentBatchEnrollmentPeriod::query()
                    ->where('student_id', $studentId)
                    ->where('teacher_id', $teacherId)
                    ->where('classroom_id', $cid)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => $now]);
            }

            if ($newBid !== null) {
                StudentBatchEnrollmentPeriod::query()->create([
                    'student_id' => $studentId,
                    'teacher_id' => $teacherId,
                    'classroom_id' => $cid,
                    'batch_id' => $newBid,
                    'started_at' => $now,
                    'ended_at' => null,
                ]);
            }
        }
    }

    /**
     * @return array{0: bool, 1: string|null}
     */
    public static function validatePairsForTeacher(int $teacherId, array $pairs): array
    {
        $seenClassroomIds = [];
        foreach ($pairs as $p) {
            $cid = (int) ($p['classroom_id'] ?? 0);
            $bid = (int) ($p['batch_id'] ?? 0);
            if ($cid < 1 || $bid < 1) {
                return [false, 'Each row needs a valid classroom and batch.'];
            }
            if (isset($seenClassroomIds[$cid])) {
                return [false, 'A student can only be assigned to one batch per classroom. Remove duplicate classrooms or choose a single batch for each classroom.'];
            }
            $seenClassroomIds[$cid] = true;
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
