<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\MarkAbsence;
use App\Models\PortalUser;
use App\Models\StudentAttendance;
use App\Models\TeacherSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mpdf\Mpdf;

class AcademicReportService
{
    public const TYPE_TEST = 'test';

    public const TYPE_ATTENDANCE = 'attendance';

    public const TYPE_OVERALL = 'overall';

    /** @return list<string> */
    public static function validTypes(): array
    {
        return [self::TYPE_TEST, self::TYPE_ATTENDANCE, self::TYPE_OVERALL];
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_TEST => 'Test Report',
            self::TYPE_ATTENDANCE => 'Attendance Report',
            self::TYPE_OVERALL => 'Overall Report',
            default => 'Academic Report',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $type, int $studentId, ?int $teacherId = null): array
    {
        $type = strtolower(trim($type));
        if (! in_array($type, self::validTypes(), true)) {
            throw new \InvalidArgumentException('Invalid report type.');
        }

        $student = PortalUser::query()->where('role', 2)->whereKey($studentId)->first(['id', 'name', 'email', 'phone']);
        if (! $student) {
            throw new \RuntimeException('Student not found.');
        }

        $batchRows = $this->enrolledBatchRows($studentId, $teacherId);
        $batchIds = $batchRows->pluck('batch_id')->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();

        $teacher = null;
        if ($teacherId !== null && $teacherId > 0) {
            $teacher = PortalUser::query()->where('role', 1)->find($teacherId, ['id', 'name', 'email']);
        }

        $attendance = $this->buildAttendanceSection($studentId, $batchRows);
        $tests = $this->buildTestSection($studentId, $batchIds, $teacherId);

        return [
            'report_type' => $type,
            'report_title' => self::typeLabel($type),
            'generated_at' => Carbon::now(),
            'student' => [
                'id' => (int) $student->id,
                'name' => (string) $student->name,
                'email' => (string) ($student->email ?? ''),
                'phone' => (string) ($student->phone ?? ''),
            ],
            'teacher' => $teacher ? [
                'id' => (int) $teacher->id,
                'name' => (string) $teacher->name,
                'email' => (string) ($teacher->email ?? ''),
            ] : null,
            'scope_label' => $teacher
                ? 'Teacher: '.$teacher->name
                : 'All enrolled classrooms',
            'attendance' => $attendance,
            'tests' => $tests,
            'include_attendance' => in_array($type, [self::TYPE_ATTENDANCE, self::TYPE_OVERALL], true),
            'include_tests' => in_array($type, [self::TYPE_TEST, self::TYPE_OVERALL], true),
        ];
    }

    /**
     * @param  array<string, mixed>  $reportData
     */
    public function pdfResponse(array $reportData, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        $html = view('pdf.academic_report', $reportData)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 14,
            'margin_bottom' => 14,
        ]);
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function suggestedFilename(array $reportData): string
    {
        $studentName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) ($reportData['student']['name'] ?? 'student'));
        $type = (string) ($reportData['report_type'] ?? 'report');
        $date = Carbon::now()->format('Y-m-d');

        return strtolower($studentName.'_'.$type.'_report_'.$date.'.pdf');
    }

    /**
     * @return Collection<int, object{batch_id: int, batch_name: string, classroom_id: int, classroom_name: string}>
     */
    public function enrolledBatchRows(int $studentId, ?int $teacherId): Collection
    {
        if ($studentId <= 0) {
            return collect();
        }

        if ($teacherId !== null && $teacherId > 0) {
            return $this->enrolledBatchRowsForStudentTeacher($studentId, $teacherId);
        }

        $rows = DB::table('student_classroom_map as scm')
            ->join('classrooms as c', 'c.id', '=', 'scm.classroom_id')
            ->leftJoin('batches as b', 'b.id', '=', 'scm.batch_id')
            ->where('scm.student_id', $studentId)
            ->whereNotNull('scm.batch_id')
            ->whereNull('c.deleted_at')
            ->select([
                'b.id as batch_id',
                'b.name as batch_name',
                'c.id as classroom_id',
                'c.name as classroom_name',
            ])
            ->distinct()
            ->orderBy('c.name')
            ->orderBy('b.name')
            ->get();

        if ($rows->isNotEmpty()) {
            return $rows;
        }

        $student = PortalUser::query()->where('role', 2)->whereKey($studentId)->first(['batch_id', 'classroom_id']);
        if (! $student || (int) ($student->batch_id ?? 0) <= 0) {
            return collect();
        }

        return DB::table('batches as b')
            ->join('classrooms as c', 'c.id', '=', 'b.classroom_id')
            ->where('b.id', (int) $student->batch_id)
            ->whereNull('b.deleted_at')
            ->select([
                'b.id as batch_id',
                'b.name as batch_name',
                'c.id as classroom_id',
                'c.name as classroom_name',
            ])
            ->get();
    }

    /**
     * @param  Collection<int, object{batch_id: int, batch_name: string, classroom_id: int, classroom_name: string}>  $batchRows
     * @return array{summary: array{present: int, absent: int, late: int, total: int, rate_pct: float|null}, batches: list<array<string, mixed>>}
     */
    private function buildAttendanceSection(int $studentId, Collection $batchRows): array
    {
        $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0, 'rate_pct' => null];
        $batchesOut = [];

        if (! Schema::hasTable('student_attendances') || $batchRows->isEmpty()) {
            return ['summary' => $summary, 'batches' => $batchesOut];
        }

        $batchIds = $batchRows->pluck('batch_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $allRows = StudentAttendance::query()
            ->whereIn('batch_id', $batchIds)
            ->orderBy('date')
            ->get(['batch_id', 'student_id', 'date', 'attendance_status']);

        $dateKeysByBatch = [];
        $studentStatusByBatchDate = [];
        foreach ($allRows as $row) {
            $bid = (int) $row->batch_id;
            $d = $row->date->format('Y-m-d');
            $dateKeysByBatch[$bid][$d] = true;
            if ((int) $row->student_id === $studentId) {
                $studentStatusByBatchDate[$bid][$d] = (string) $row->attendance_status;
            }
        }

        foreach ($batchRows as $br) {
            $bid = (int) $br->batch_id;
            $keys = array_keys($dateKeysByBatch[$bid] ?? []);
            rsort($keys, SORT_STRING);
            $rows = [];
            foreach ($keys as $d) {
                $st = $studentStatusByBatchDate[$bid][$d] ?? '';
                $rows[] = [
                    'date' => $d,
                    'status' => $st,
                    'label' => $this->attendanceStatusLabel($st),
                ];
                if ($st === 'P') {
                    $summary['present']++;
                    $summary['total']++;
                } elseif ($st === 'A') {
                    $summary['absent']++;
                    $summary['total']++;
                } elseif ($st === 'L') {
                    $summary['late']++;
                    $summary['total']++;
                }
            }
            $batchesOut[] = [
                'batch_name' => (string) $br->batch_name,
                'classroom_name' => (string) $br->classroom_name,
                'rows' => $rows,
            ];
        }

        if ($summary['total'] > 0) {
            $summary['rate_pct'] = round(100 * ($summary['present'] + $summary['late']) / $summary['total'], 1);
        }

        return ['summary' => $summary, 'batches' => $batchesOut];
    }

    /**
     * @param  array<int, int>  $batchIds
     * @return array{summary: array{overall_pct: float|null, exam_count: int}, exams: list<array<string, mixed>>}
     */
    private function buildTestSection(int $studentId, array $batchIds, ?int $teacherId): array
    {
        $empty = [
            'summary' => ['overall_pct' => null, 'exam_count' => 0],
            'exams' => [],
        ];

        if ($studentId <= 0 || $batchIds === [] || ! Schema::hasTable('exams') || ! Schema::hasTable('marks')) {
            return $empty;
        }

        $batchIds = array_values(array_unique(array_filter($batchIds, fn ($id) => (int) $id > 0)));
        if ($batchIds === []) {
            return $empty;
        }

        $batchMeta = DB::table('batches as b')
            ->join('classrooms as c', 'c.id', '=', 'b.classroom_id')
            ->whereIn('b.id', $batchIds)
            ->whereNull('b.deleted_at')
            ->select(['b.id as batch_id', 'b.name as batch_name', 'c.name as classroom_name', 'c.teacher_id'])
            ->get()
            ->keyBy(fn ($r) => (int) $r->batch_id);

        $batchTeacherIdByBatchId = $batchMeta->mapWithKeys(fn ($r) => [(int) $r->batch_id => (int) $r->teacher_id]);

        $exams = Exam::query()
            ->whereIn('batch_id', $batchIds)
            ->orderByRaw('CASE WHEN exam_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('exam_date')
            ->orderBy('id')
            ->get(['id', 'batch_id', 'exam_name', 'max_marks', 'exam_date']);

        if ($exams->isEmpty()) {
            return $empty;
        }

        $examIds = $exams->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $marksByExamId = Mark::query()
            ->where('student_id', $studentId)
            ->whereIn('exam_id', $examIds)
            ->get()
            ->keyBy(fn ($r) => (int) $r->exam_id);

        $teacherMarkDisplayCache = [];
        if ($teacherId !== null && $teacherId > 0) {
            $teacherSettingRow = TeacherSetting::query()->where('teacher_id', $teacherId)->first();
            $teacherMarkDisplayCache[$teacherId] = $this->normalizeAbsentDisplaySetting(
                (int) ($teacherSettingRow?->count_setting ?? TeacherSetting::COUNT_AS_ZERO)
            );
        }

        $absenceByExamId = collect();
        if (Schema::hasTable('mark_absences')) {
            $absenceByExamId = MarkAbsence::query()
                ->where('student_id', $studentId)
                ->whereIn('exam_id', $examIds)
                ->get()
                ->keyBy(fn ($r) => (int) $r->exam_id);
        }

        $sumMax = 0.0;
        $sumGot = 0.0;
        $included = 0;
        $examRows = [];

        foreach ($exams as $exam) {
            $eid = (int) $exam->id;
            $max = (float) ($exam->max_marks ?? 0);
            if ($max <= 0) {
                continue;
            }

            $batchId = (int) $exam->batch_id;
            $meta = $batchMeta->get($batchId);
            $batchName = (string) ($meta->batch_name ?? '');
            $classroomName = (string) ($meta->classroom_name ?? '');
            $teacherIdForExam = (int) ($teacherId ?? 0);
            if ($teacherIdForExam <= 0) {
                $teacherIdForExam = (int) ($batchTeacherIdByBatchId[$batchId] ?? 0);
            }
            if ($teacherIdForExam > 0 && ! isset($teacherMarkDisplayCache[$teacherIdForExam])) {
                $teacherSettingRow = TeacherSetting::query()->where('teacher_id', $teacherIdForExam)->first();
                $teacherMarkDisplayCache[$teacherIdForExam] = $this->normalizeAbsentDisplaySetting(
                    (int) ($teacherSettingRow?->count_setting ?? TeacherSetting::COUNT_AS_ZERO)
                );
            }
            $teacherMarkDisplaySetting = $teacherMarkDisplayCache[$teacherIdForExam] ?? TeacherSetting::COUNT_AS_ZERO;

            $examDateStr = $exam->exam_date ? $exam->exam_date->format('Y-m-d') : null;
            $base = [
                'exam_name' => (string) ($exam->exam_name ?? ''),
                'exam_date' => $examDateStr,
                'batch_name' => $batchName,
                'classroom_name' => $classroomName,
                'max_marks' => $max,
            ];

            $isAbsent = $absenceByExamId->has($eid);
            if ($isAbsent) {
                $absRow = $absenceByExamId->get($eid);
                $storedMode = null;
                if ($absRow && Schema::hasColumn('mark_absences', 'value') && $absRow->value !== null && $absRow->value !== '') {
                    $storedMode = (int) $absRow->value;
                }
                $effectiveMode = $this->normalizeAbsentDisplaySetting($storedMode ?? $teacherMarkDisplaySetting);
                if ($effectiveMode === TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE) {
                    $examRows[] = array_merge($base, [
                        'marks_obtained' => null,
                        'pct' => null,
                        'display' => 'Absent (excluded)',
                    ]);

                    continue;
                }
                $sumMax += $max;
                $included++;
                $examRows[] = array_merge($base, [
                    'marks_obtained' => 0.0,
                    'pct' => 0.0,
                    'display' => 'Absent',
                ]);

                continue;
            }

            $markRow = $marksByExamId->get($eid);
            $raw = $markRow !== null ? trim((string) $markRow->marks) : '';
            // No marks row or non-numeric: omit from PDF (do not count as 0).
            if ($raw === '' || ! is_numeric($raw)) {
                continue;
            }

            $got = (float) $raw;
            if ($got < 0) {
                $got = 0;
            }
            if ($got > $max) {
                $got = $max;
            }
            $pct = round(100 * $got / $max, 1);
            $sumMax += $max;
            $sumGot += $got;
            $included++;
            $examRows[] = array_merge($base, [
                'marks_obtained' => $got,
                'pct' => $pct,
                'display' => $got.' / '.$max.' ('.$pct.'%)',
            ]);
        }

        $overallPct = $sumMax > 0 ? round(100 * $sumGot / $sumMax, 1) : null;

        return [
            'summary' => [
                'overall_pct' => $overallPct,
                'exam_count' => $included,
            ],
            'exams' => $examRows,
        ];
    }

    /**
     * @return Collection<int, object{batch_id: int, batch_name: string, classroom_id: int, classroom_name: string}>
     */
    private function enrolledBatchRowsForStudentTeacher(int $studentId, int $teacherId): Collection
    {
        $batchRows = DB::table('student_classroom_map as scm')
            ->join('batches as b', 'b.id', '=', 'scm.batch_id')
            ->join('classrooms as c', 'c.id', '=', 'b.classroom_id')
            ->where('scm.teacher_id', $teacherId)
            ->where('scm.student_id', $studentId)
            ->whereNotNull('scm.batch_id')
            ->where('c.teacher_id', $teacherId)
            ->select([
                'b.id as batch_id',
                'b.name as batch_name',
                'c.id as classroom_id',
                'c.name as classroom_name',
            ])
            ->distinct()
            ->orderBy('c.name')
            ->orderBy('b.name')
            ->get();

        if ($batchRows->isNotEmpty()) {
            return $batchRows;
        }

        $legacy = $this->legacyEnrollmentForStudentTeacher($studentId, $teacherId);
        if ($legacy === null) {
            return collect();
        }

        if ($legacy['batch_id'] !== null) {
            return DB::table('batches as b')
                ->join('classrooms as c', 'c.id', '=', 'b.classroom_id')
                ->where('b.id', $legacy['batch_id'])
                ->where('c.teacher_id', $teacherId)
                ->whereNull('b.deleted_at')
                ->select([
                    'b.id as batch_id',
                    'b.name as batch_name',
                    'c.id as classroom_id',
                    'c.name as classroom_name',
                ])
                ->get();
        }

        return DB::table('batches as b')
            ->join('classrooms as c', 'c.id', '=', 'b.classroom_id')
            ->where('b.classroom_id', $legacy['classroom_id'])
            ->where('c.teacher_id', $teacherId)
            ->whereNull('b.deleted_at')
            ->select([
                'b.id as batch_id',
                'b.name as batch_name',
                'c.id as classroom_id',
                'c.name as classroom_name',
            ])
            ->orderBy('c.name')
            ->orderBy('b.name')
            ->get();
    }

    /**
     * @return array{classroom_id: int, batch_id: int|null}|null
     */
    private function legacyEnrollmentForStudentTeacher(int $studentId, int $teacherId): ?array
    {
        if ($studentId <= 0 || $teacherId <= 0) {
            return null;
        }

        $student = PortalUser::query()->where('role', 2)->whereKey($studentId)->first(['classroom_id', 'batch_id']);
        if (! $student) {
            return null;
        }

        $classroomId = (int) ($student->classroom_id ?? 0);
        if ($classroomId <= 0) {
            return null;
        }

        $classroom = Classroom::query()->whereKey($classroomId)->first(['id', 'teacher_id']);
        if (! $classroom || (int) $classroom->teacher_id !== $teacherId) {
            return null;
        }

        $batchId = (int) ($student->batch_id ?? 0);
        if ($batchId > 0) {
            $batch = Batch::query()->whereKey($batchId)->first(['id', 'classroom_id', 'teacher_id']);
            if (! $batch || (int) $batch->classroom_id !== $classroomId || (int) $batch->teacher_id !== $teacherId) {
                $batchId = 0;
            }
        }

        return [
            'classroom_id' => $classroomId,
            'batch_id' => $batchId > 0 ? $batchId : null,
        ];
    }

    private function normalizeAbsentDisplaySetting(int $raw): int
    {
        return $raw === TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE
            ? TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE
            : TeacherSetting::COUNT_AS_ZERO;
    }

    private function attendanceStatusLabel(string $status): string
    {
        return match (strtoupper($status)) {
            'P' => 'Present',
            'A' => 'Absent',
            'L' => 'Late',
            default => '—',
        };
    }
}
