<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderBoardController extends Controller
{
    private const NUMERIC_MARKS_REGEX = '^[0-9]+(\\.[0-9]*)?$';

    public function leaderboard(Request $request)
    {
        $portal = session('portal_user');
        $teacherId = (int) ($portal['id'] ?? 0);
        $role = (int) ($portal['role'] ?? 0);
        if (!$teacherId || $role !== 1) {
            return redirect('login');
        }

        $data = [];
        $data['title'] = 'Leaderboard';

        $view = $request->query('view', 'test');
        if (! is_string($view) || ! in_array($view, ['batch', 'test'], true)) {
            $view = 'test';
        }
        $data['leaderboard_view'] = $view;
        $data['active_tab'] = $view === 'batch' ? 'leaderboard_batch' : 'leaderboard_test';

        $data['classrooms'] = Classroom::query()
            ->where('teacher_id', $teacherId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedClassroomId = (int) $request->query('classroom_id', 0);
        $selectedBatchId = (int) $request->query('batch_id', 0);
        $selectedExamId = $view === 'batch' ? 0 : (int) $request->query('exam_id', 0);

        $data['selected_classroom_id'] = $selectedClassroomId;
        $data['selected_batch_id'] = $selectedBatchId;
        $data['selected_exam_id'] = $selectedExamId;

        $data['batches'] = collect();
        if ($selectedClassroomId > 0) {
            $classOk = Classroom::where('teacher_id', $teacherId)->whereKey($selectedClassroomId)->exists();
            if ($classOk) {
                $data['batches'] = Batch::query()
                    ->where('teacher_id', $teacherId)
                    ->where('classroom_id', $selectedClassroomId)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            } else {
                $data['selected_classroom_id'] = 0;
                $data['selected_batch_id'] = 0;
                $data['selected_exam_id'] = 0;
                $selectedClassroomId = 0;
                $selectedBatchId = 0;
                $selectedExamId = 0;
            }
        }

        $data['exams'] = collect();
        if ($selectedBatchId > 0 && $selectedClassroomId > 0) {
            $batch = Batch::where('teacher_id', $teacherId)->find($selectedBatchId);
            if ($batch && (int) $batch->classroom_id === $selectedClassroomId) {
                $data['exams'] = Exam::query()
                    ->where('batch_id', $selectedBatchId)
                    ->orderBy('exam_date')
                    ->orderBy('id')
                    ->get(['id', 'exam_name', 'max_marks', 'exam_date']);
            } else {
                $data['selected_batch_id'] = 0;
                $data['selected_exam_id'] = 0;
                $selectedBatchId = 0;
                $selectedExamId = 0;
            }
        }

        $data['leaderboard_exam'] = null;
        $data['leaderboard_chart'] = null;
        $data['leaderboard_row_count'] = null;
        $data['leaderboard_scope'] = null;
        $data['leaderboard_classroom_name'] = null;
        $data['leaderboard_batch'] = null;
        $data['leaderboard_rows'] = collect();

        $rankType = $this->resolveRankType($request, $selectedClassroomId, $selectedBatchId, $selectedExamId, $view);

        if (! $request->filled('search')) {
            return view('web.user.teacher.leaderboard', $data);
        }

        if ($view === 'batch' && $selectedClassroomId <= 0) {
            return view('web.user.teacher.leaderboard', $data);
        }

        if ($view === 'test' && ($selectedClassroomId <= 0 || $selectedBatchId <= 0 || $selectedExamId <= 0)) {
            return view('web.user.teacher.leaderboard', $data);
        }

        $built = $this->buildLeaderboardPayload(
            $request,
            $teacherId,
            $rankType,
            $selectedClassroomId,
            $selectedBatchId,
            $selectedExamId
        );
        foreach ($built as $key => $value) {
            $data[$key] = $value;
        }

        return view('web.user.teacher.leaderboard', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLeaderboardPayload(
        Request $request,
        int $teacherId,
        string $rankType,
        int $selectedClassroomId,
        int $selectedBatchId,
        int $selectedExamId
    ): ?array {
        $out = [
            'leaderboard_exam' => null,
            'leaderboard_chart' => null,
            'leaderboard_row_count' => 0,
            'leaderboard_scope' => null,
            'leaderboard_classroom_name' => null,
            'leaderboard_batch' => null,
            'leaderboard_rows' => collect(),
            'leaderboard_overview_chart' => [],
        ];

        if ($rankType === 'exam') {
            if ($selectedExamId <= 0) {
                return $out;
            }

            $exam = Exam::query()->whereKey($selectedExamId)->first();
            if (!$exam) {
                return $out;
            }

            $batch = Batch::where('teacher_id', $teacherId)->whereKey($exam->batch_id)->first();
            if (!$batch) {
                return $out;
            }

            if ($selectedClassroomId > 0 && (int) $batch->classroom_id !== $selectedClassroomId) {
                return $out;
            }

            if ($selectedBatchId > 0 && (int) $batch->id !== $selectedBatchId) {
                return $out;
            }

            $classroom = Classroom::where('teacher_id', $teacherId)->find($batch->classroom_id);

            $rows = $this->queryExamRanking($teacherId, $exam->id);
            $maxM = (float) ($exam->max_marks ?? 0);

            $chart = [];
            foreach ($rows as $r) {
                $pct = $r->percentage !== null ? round((float) $r->percentage, 2) : null;
                if ($pct === null) {
                    continue;
                }
                $chart[] = [
                    'name' => $r->name ?? '—',
                    'percentage' => $pct,
                    'marks' => isset($r->raw_marks) ? round((float) $r->raw_marks, 2) : null,
                ];
            }

            $out['leaderboard_exam'] = $exam;
            $out['leaderboard_batch'] = $batch;
            $out['leaderboard_scope'] = 'exam';
            $out['leaderboard_classroom_name'] = $classroom?->name;
            $out['leaderboard_chart'] = $chart;
            $out['leaderboard_row_count'] = count($chart);
            $out['leaderboard_rows'] = collect($rows)->map(fn ($r) => (object) [
                'rank' => $r->rank_val ?? null,
                'marks' => $r->raw_marks ?? null,
                'student' => (object) [
                    'name' => $r->name ?? '',
                    'email' => $r->email ?? null,
                ],
            ]);

            $classroomAvg = $classroom
                ? $this->averageMarkPercentageForClassroom((int) $classroom->id, $teacherId)
                : null;
            $batchAvg = $this->averageMarkPercentageForBatch($batch->id, $teacherId);
            $examAvg = null;
            if ($maxM > 0 && count($chart) > 0) {
                $examAvg = round(array_sum(array_column($chart, 'percentage')) / count($chart), 2);
            }

            $overview = [];
            if ($classroomAvg !== null) {
                $overview[] = ['label' => 'Classroom', 'percentage' => $classroomAvg];
            }
            if ($batchAvg !== null) {
                $overview[] = ['label' => 'This batch', 'percentage' => $batchAvg];
            }
            if ($examAvg !== null) {
                $overview[] = ['label' => 'This test', 'percentage' => $examAvg];
            }
            $out['leaderboard_overview_chart'] = $overview;

            return $out;
        }

        if ($rankType === 'class') {
            if ($selectedClassroomId <= 0) {
                return $out;
            }

            $classOk = Classroom::where('teacher_id', $teacherId)->whereKey($selectedClassroomId)->exists();
            if (!$classOk) {
                return $out;
            }

            $classroom = Classroom::where('teacher_id', $teacherId)->find($selectedClassroomId);
            $batch = null;

            if ($selectedBatchId > 0) {
                $batch = Batch::where('teacher_id', $teacherId)
                    ->whereKey($selectedBatchId)
                    ->where('classroom_id', $selectedClassroomId)
                    ->first();
                if (!$batch) {
                    return $out;
                }
            }

            $rows = $this->queryOverallOrClassRanking(
                $teacherId,
                $selectedClassroomId,
                $selectedBatchId > 0 ? $selectedBatchId : null
            );

            $chart = [];
            foreach ($rows as $r) {
                $pct = $r->percentage !== null ? round((float) $r->percentage, 2) : null;
                if ($pct === null) {
                    continue;
                }
                $chart[] = [
                    'name' => $r->name ?? '—',
                    'percentage' => $pct,
                    'marks_obtained' => isset($r->sum_marks) ? round((float) $r->sum_marks, 2) : null,
                    'total_marks' => isset($r->sum_total) ? round((float) $r->sum_total, 2) : null,
                ];
            }

            $out['leaderboard_classroom_name'] = $classroom?->name;
            $out['leaderboard_batch'] = $batch;
            $out['leaderboard_scope'] = $batch ? 'batch' : 'classroom';
            $out['leaderboard_chart'] = $chart;
            $out['leaderboard_row_count'] = count($chart);
            $out['leaderboard_rows'] = collect($rows)->map(fn ($r) => (object) [
                'rank' => $r->rank_val ?? null,
                'percentage' => $r->percentage !== null ? round((float) $r->percentage, 2) : null,
                'student' => (object) [
                    'name' => $r->name ?? '',
                    'email' => $r->email ?? null,
                ],
            ]);

            return $out;
        }

        // overall — all marks for this teacher's batches
        $rows = $this->queryOverallOrClassRanking($teacherId, null, null);

        $chart = [];
        foreach ($rows as $r) {
            $pct = $r->percentage !== null ? round((float) $r->percentage, 2) : null;
            if ($pct === null) {
                continue;
            }
            $chart[] = [
                'name' => $r->name ?? '—',
                'percentage' => $pct,
                'marks_obtained' => isset($r->sum_marks) ? round((float) $r->sum_marks, 2) : null,
                'total_marks' => isset($r->sum_total) ? round((float) $r->sum_total, 2) : null,
            ];
        }

        $out['leaderboard_chart'] = $chart;
        $out['leaderboard_row_count'] = count($chart);
        $out['leaderboard_scope'] = null;
        $out['leaderboard_rows'] = collect($rows)->map(fn ($r) => (object) [
            'rank' => $r->rank_val ?? null,
            'percentage' => $r->percentage !== null ? round((float) $r->percentage, 2) : null,
            'student' => (object) [
                'name' => $r->name ?? '',
                'email' => $r->email ?? null,
            ],
        ]);

        return $out;
    }

    private function resolveRankType(
        Request $request,
        int $selectedClassroomId,
        int $selectedBatchId,
        int $selectedExamId,
        string $view = 'test'
    ): string {
        if ($view === 'batch') {
            if ($selectedClassroomId > 0) {
                return 'class';
            }

            return 'overall';
        }

        $t = $request->query('type');
        if (is_string($t) && in_array($t, ['overall', 'class', 'exam'], true)) {
            return $t;
        }

        if ($selectedExamId > 0) {
            return 'exam';
        }
        if ($selectedClassroomId > 0) {
            return 'class';
        }

        return 'overall';
    }

    /**
     * Overall (no classroom/batch) or class/batch filtered aggregate percentage per student:
     * (SUM(marks) / SUM(max_marks)) * 100, RANK() OVER (ORDER BY percentage DESC).
     *
     * @return array<int, object>
     */
    private function queryOverallOrClassRanking(int $teacherId, ?int $classroomId, ?int $batchId): array
    {
        $numeric = self::NUMERIC_MARKS_REGEX;
        $bindings = [$teacherId];

        $classFilter = '';
        if ($classroomId !== null && $classroomId > 0) {
            $classFilter .= ' AND b.classroom_id = ?';
            $bindings[] = $classroomId;
        }
        if ($batchId !== null && $batchId > 0) {
            $classFilter .= ' AND b.id = ?';
            $bindings[] = $batchId;
        }

        $sql = <<<SQL
SELECT
    r.student_id,
    r.sum_marks,
    r.sum_total,
    r.percentage,
    r.rank_val,
    pu.name,
    pu.email
FROM (
    SELECT
        agg.student_id,
        agg.sum_marks,
        agg.sum_total,
        (agg.sum_marks / agg.sum_total) * 100 AS percentage,
        RANK() OVER (ORDER BY (agg.sum_marks / agg.sum_total) * 100 DESC) AS rank_val
    FROM (
        SELECT
            m.student_id,
            SUM(CAST(m.marks AS DECIMAL(14,4))) AS sum_marks,
            SUM(CAST(e.max_marks AS DECIMAL(14,4))) AS sum_total
        FROM marks m
        INNER JOIN exams e ON m.exam_id = e.id AND e.deleted_at IS NULL
        INNER JOIN batches b ON e.batch_id = b.id AND b.teacher_id = ? AND b.deleted_at IS NULL
        INNER JOIN portal_user st ON m.student_id = st.id AND st.role = 2 AND st.deleted_at IS NULL
        WHERE m.deleted_at IS NULL
          AND e.max_marks > 0
          AND m.marks REGEXP '{$numeric}'
          {$classFilter}
        GROUP BY m.student_id
        HAVING sum_total > 0
    ) agg
) r
INNER JOIN portal_user pu ON r.student_id = pu.id
ORDER BY r.rank_val ASC, pu.name ASC
SQL;

        /** @var array<int, object> $rows */
        $rows = DB::select($sql, $bindings);

        return $rows;
    }

    /**
     * Single-exam percentage (marks / max_marks) * 100 with RANK() OVER (ORDER BY percentage DESC).
     *
     * @return array<int, object>
     */
    private function queryExamRanking(int $teacherId, int $examId): array
    {
        $numeric = self::NUMERIC_MARKS_REGEX;

        $sql = <<<SQL
SELECT
    inner_q.student_id,
    inner_q.raw_marks,
    inner_q.percentage,
    inner_q.rank_val,
    pu.name,
    pu.email
FROM (
    SELECT
        m.student_id,
        CAST(m.marks AS DECIMAL(14,4)) AS raw_marks,
        (CAST(m.marks AS DECIMAL(14,4)) / NULLIF(CAST(e.max_marks AS DECIMAL(14,4)), 0)) * 100 AS percentage,
        RANK() OVER (
            ORDER BY (CAST(m.marks AS DECIMAL(14,4)) / NULLIF(CAST(e.max_marks AS DECIMAL(14,4)), 0)) * 100 DESC
        ) AS rank_val
    FROM marks m
    INNER JOIN exams e ON m.exam_id = e.id AND e.deleted_at IS NULL
    INNER JOIN batches b ON e.batch_id = b.id AND b.teacher_id = ? AND b.deleted_at IS NULL
    INNER JOIN portal_user st ON m.student_id = st.id AND st.role = 2 AND st.deleted_at IS NULL
    WHERE m.deleted_at IS NULL
      AND m.exam_id = ?
      AND e.max_marks > 0
      AND m.marks REGEXP '{$numeric}'
) inner_q
INNER JOIN portal_user pu ON inner_q.student_id = pu.id
ORDER BY inner_q.rank_val ASC, pu.name ASC
SQL;

        /** @var array<int, object> */
        return DB::select($sql, [$teacherId, $examId]);
    }

    /**
     * Average (marks / max_marks * 100) across all numeric marks in the classroom's batches for this teacher.
     */
    private function averageMarkPercentageForClassroom(int $classroomId, int $teacherId): ?float
    {
        $row = DB::table('marks')
            ->join('exams', function ($join) {
                $join->on('marks.exam_id', '=', 'exams.id')
                    ->whereNull('exams.deleted_at');
            })
            ->join('batches', function ($join) use ($classroomId, $teacherId) {
                $join->on('exams.batch_id', '=', 'batches.id')
                    ->where('batches.classroom_id', $classroomId)
                    ->where('batches.teacher_id', $teacherId)
                    ->whereNull('batches.deleted_at');
            })
            ->join('portal_user as st', function ($join) {
                $join->on('marks.student_id', '=', 'st.id')
                    ->where('st.role', 2)
                    ->whereNull('st.deleted_at');
            })
            ->whereNull('marks.deleted_at')
            ->where('exams.max_marks', '>', 0)
            ->whereRaw('marks.marks REGEXP ?', ['^[0-9]+(\\.[0-9]*)?$'])
            ->selectRaw(
                'AVG((CAST(marks.marks AS DECIMAL(12,4)) / CAST(exams.max_marks AS DECIMAL(12,4))) * 100) as v'
            )
            ->first();

        return $row && $row->v !== null ? round((float) $row->v, 2) : null;
    }

    /**
     * Average percentage for all numeric marks in the given batch (all tests in that batch).
     */
    private function averageMarkPercentageForBatch(int $batchId, int $teacherId): ?float
    {
        $row = DB::table('marks')
            ->join('exams', function ($join) {
                $join->on('marks.exam_id', '=', 'exams.id')
                    ->whereNull('exams.deleted_at');
            })
            ->join('batches', function ($join) use ($batchId, $teacherId) {
                $join->on('exams.batch_id', '=', 'batches.id')
                    ->where('batches.id', $batchId)
                    ->where('batches.teacher_id', $teacherId)
                    ->whereNull('batches.deleted_at');
            })
            ->join('portal_user as st', function ($join) {
                $join->on('marks.student_id', '=', 'st.id')
                    ->where('st.role', 2)
                    ->whereNull('st.deleted_at');
            })
            ->whereNull('marks.deleted_at')
            ->where('exams.max_marks', '>', 0)
            ->whereRaw('marks.marks REGEXP ?', ['^[0-9]+(\\.[0-9]*)?$'])
            ->selectRaw(
                'AVG((CAST(marks.marks AS DECIMAL(12,4)) / CAST(exams.max_marks AS DECIMAL(12,4))) * 100) as v'
            )
            ->first();

        return $row && $row->v !== null ? round((float) $row->v, 2) : null;
    }
}
