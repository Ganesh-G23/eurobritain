<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\ParentStudentMap;
use App\Models\PortalUser;
use App\Models\StudentAttendance;
use App\Models\StudentClassroomMap;
use App\Models\StudentBatchEnrollmentPeriod;
use App\Models\StudentTeacherMap;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentController extends Controller
{
    public function list(Request $request)
    {
        $mainUrl = url('admin/student');

        $search = trim((string) ($request->search ?? ''));
        $teacherId = (int) ($request->teacher_id ?? 0);
        $classroomId = (int) ($request->classroom_id ?? 0);
        $batchId = (int) ($request->batch_id ?? 0);

        if ($teacherId <= 0) {
            $classroomId = 0;
            $batchId = 0;
        }

        $page = max(1, (int) ($request->page ?? 1));
        $perPage = max(1, min(200, (int) ($request->per_page ?? 50)));

        $studentList = PortalUser::query()
            ->where('role', 2)
            ->with(['classroom', 'batch', 'teachers', 'studentClassroomMaps'])
            ->orderBy('id', 'desc');

        if ($search !== '') {
            $studentList->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        if ($teacherId > 0) {
            $studentList->whereHas('teachers', function ($query) use ($teacherId) {
                $query->whereKey($teacherId);
            });
        }

        if ($classroomId > 0) {
            $studentList->where(function ($query) use ($classroomId) {
                $query->where('classroom_id', $classroomId)->orWhereHas('studentClassroomMaps', function ($q) use ($classroomId) {
                    $q->where('classroom_id', $classroomId);
                });
            });
        }

        if ($batchId > 0) {
            $studentList->where(function ($query) use ($batchId) {
                $query->where('batch_id', $batchId)->orWhereHas('studentClassroomMaps', function ($q) use ($batchId) {
                    $q->where('batch_id', $batchId);
                });
            });
        }

        $queryParams = array_filter(
            [
                'search' => $search !== '' ? $search : null,
                'teacher_id' => $teacherId > 0 ? $teacherId : null,
                'classroom_id' => $classroomId > 0 ? $classroomId : null,
                'batch_id' => $batchId > 0 ? $batchId : null,
            ],
            static fn ($v) => $v !== null && $v !== '',
        );

        $numRows = (clone $studentList)->count();

        $studentList = $studentList
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $teachers = PortalUser::where('role', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($teacherId > 0) {
            $classrooms = Classroom::query()
                ->where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name', 'teacher_id']);

            $batchesQuery = Batch::query()->with('classroom')->orderBy('name');
            if ($classroomId > 0) {
                $batchesQuery->where('classroom_id', $classroomId);
            } else {
                $batchesQuery->where('teacher_id', $teacherId);
            }
            $batches = $batchesQuery->get(['id', 'name', 'classroom_id', 'teacher_id']);
        } else {
            $classrooms = collect();
            $batches = collect();
        }

        return view('admin.student.list', [
            'title' => 'Students',
            'active_tab' => 'student',
            'student_list' => $studentList,
            'num_rows' => $numRows,
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search,
            'teacher_id' => $teacherId,
            'classroom_id' => $classroomId,
            'batch_id' => $batchId,
            'query_params' => $queryParams,
            'teachers' => $teachers,
            'classrooms' => $classrooms,
            'batches' => $batches,
        ]);
    }

    public function deleteStudent(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;

        if ($id) {
            $student = PortalUser::where('role', 2)->find($id);
            if ($student) {
                $batch = Batch::find($student->batch_id);
                $teacherId = $batch ? $batch->teacher_id : null;
                StudentBatchEnrollmentPeriod::closeOpenPeriodsForStudent((int) $student->id);
                StudentClassroomMap::where('student_id', $student->id)->delete();
                StudentTeacherMap::where('student_id', $student->id)->delete();
                ParentStudentMap::where('student_id', $student->id)->delete();
                $student->delete();
                $this->response['status'] = 1;
                $this->response['msg'] = 'Student deleted successfully';
                if ($teacherId) {
                    $this->response['redirect_url'] = url('admin/student');
                }
            } else {
                $this->response['error'] = 'Student not found';
            }
        } else {
            $this->response['error'] = 'Invalid request';
        }

        echo json_encode($this->response);
    }

    public function add(Request $request)
    {
        $teachers = PortalUser::where('role', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.student.add', [
            'title' => 'Add student',
            'active_tab' => 'student',
            'teachers' => $teachers,
        ]);
    }

    public function edit(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;
        if (! $id) {
            return redirect('admin/student');
        }

        $student = PortalUser::where('role', 2)->with('teachers')->find($id);
        if (! $student) {
            return redirect('admin/student');
        }

        $teachers = PortalUser::where('role', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
        $defaultTeacherId = (int) ($student->teachers->first()->id ?? 0);

        $parentRow = null;
        if (! empty($student->parent_id)) {
            $parentRow = PortalUser::where('role', 3)->find((int) $student->parent_id);
        }

        return view('admin.student.edit', [
            'title' => 'Edit student',
            'active_tab' => 'student',
            'student' => $student,
            'teachers' => $teachers,
            'default_teacher_id' => $defaultTeacherId,
            'parent_row' => $parentRow,
        ]);
    }

    public function enrollmentOptions(Request $request)
    {
        $teacherId = (int) ($request->teacher_id ?? 0);
        if ($teacherId < 1) {
            return response()->json([
                'status' => 0,
                'error' => 'Invalid teacher.',
            ]);
        }

        if (! PortalUser::where('role', 1)->whereKey($teacherId)->exists()) {
            return response()->json([
                'status' => 0,
                'error' => 'Teacher not found.',
            ]);
        }

        $classrooms = Classroom::where('teacher_id', $teacherId)
            ->orderBy('name')
            ->get(['id', 'name']);
        $batches = Batch::where('teacher_id', $teacherId)
            ->orderBy('name')
            ->get(['id', 'name', 'classroom_id']);

        return response()->json([
            'status' => 1,
            'data' => [
                'classrooms' => $classrooms,
                'batches' => $batches,
            ],
        ]);
    }

    public function form(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;
        $student = $id ? PortalUser::where('role', 2)->find($id) : new PortalUser;

        return view('admin.student.form', [
            'title' => 'Student',
            'active_tab' => 'student',
            'student' => $student,
        ]);
    }

    public function view(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;
        $portalUser = PortalUser::with(['teachers', 'classroom', 'batch', 'studentClassroomMaps.classroom', 'studentClassroomMaps.batch'])
            ->where('role', 2)
            ->find($id);

        if (! $portalUser) {
            return redirect('admin/student');
        }

        [$viewClassrooms, $viewBatches] = $this->studentViewEnrollmentCollections($portalUser);
        $viewTeachers = $portalUser->teachers()->where('portal_user.role', 1)->whereNull('portal_user.deleted_at')->orderBy('portal_user.name')->get();
        $teacherCount = $viewTeachers->count();

        $currentBatchIdsForStudent = $viewBatches
            ->pluck('id')
            ->merge($portalUser->batch_id ? [(int) $portalUser->batch_id] : [])
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values();

        $historicBatchIds = $this->batchIdsFromStudentAcademicHistory((int) $portalUser->id);

        $batchIdsForTests = $currentBatchIdsForStudent->merge($historicBatchIds)->unique()->filter()->values();

        $viewTests = $batchIdsForTests->isEmpty()
            ? collect()
            : Exam::query()
                ->whereIn('batch_id', $batchIdsForTests->all())
                ->with([
                    'batch' => static function ($q) {
                        $q->withTrashed();
                    },
                    'marks' => static function ($q) use ($portalUser) {
                        $q->where('student_id', $portalUser->id);
                    },
                    'markAbsences' => static function ($q) use ($portalUser) {
                        $q->where('student_id', $portalUser->id);
                    },
                ])
                ->orderByDesc('exam_date')
                ->orderByDesc('id')
                ->get();

        $testCount = $currentBatchIdsForStudent->isEmpty() ? 0 : (int) Exam::query()->whereIn('batch_id', $currentBatchIdsForStudent->all())->count();

        $attendanceDateCards = StudentAttendance::with('batch')->where('student_id', $portalUser->id)->orderByDesc('date')->paginate(10);

        return view('admin.student.view', [
            'title' => 'View Student',
            'active_tab' => 'student',
            'portal_user' => $portalUser,
            'view_teachers' => $viewTeachers,
            'view_classrooms' => $viewClassrooms,
            'view_batches' => $viewBatches,
            'total_classrooms' => $viewClassrooms->count(),
            'total_batches' => $viewBatches->count(),
            'total_teachers' => $teacherCount,
            'view_tests' => $viewTests,
            'test_count' => $testCount,
            'current_batch_ids_for_student' => $currentBatchIdsForStudent->all(),
            'attendance_date_cards' => $attendanceDateCards,
        ]);
    }

    /**
     * @return array{0: Collection<int, Classroom>, 1: Collection<int, Batch>}
     */
    protected function studentViewEnrollmentCollections(PortalUser $student): array
    {
        $classroomsKeyed = collect();
        $batchesKeyed = collect();

        foreach ($student->studentClassroomMaps as $map) {
            if ($map->classroom) {
                $classroomsKeyed->put($map->classroom->id, $map->classroom);
            }
            if ($map->batch) {
                $map->batch->loadMissing('classroom');
                $batchesKeyed->put($map->batch->id, $map->batch);
            }
        }

        if ($student->classroom_id && $student->classroom && ! $classroomsKeyed->has($student->classroom_id)) {
            $classroomsKeyed->put($student->classroom->id, $student->classroom);
        }
        if ($student->batch_id && $student->batch && ! $batchesKeyed->has($student->batch_id)) {
            $student->batch->loadMissing('classroom');
            $batchesKeyed->put($student->batch->id, $student->batch);
        }

        return [$classroomsKeyed->values(), $batchesKeyed->values()];
    }

    /**
     * Batch IDs where this student already has marks, test absences, or attendance (so Tests tab still shows history after a batch change).
     *
     * @return Collection<int, int>
     */
    protected function batchIdsFromStudentAcademicHistory(int $studentId): Collection
    {
        $ids = collect();

        if (Schema::hasTable('marks')) {
            $ids = $ids->merge(Mark::query()->where('student_id', $studentId)->whereNotNull('batch_id')->pluck('batch_id'));
        }

        if (Schema::hasTable('mark_absences') && Schema::hasTable('exams')) {
            $ids = $ids->merge(DB::table('mark_absences as ma')->join('exams as e', 'e.id', '=', 'ma.exam_id')->where('ma.student_id', $studentId)->pluck('e.batch_id'));
        }

        if (Schema::hasTable('student_attendances')) {
            $ids = $ids->merge(StudentAttendance::query()->where('student_id', $studentId)->pluck('batch_id'));
        }

        return $ids->map(static fn ($id) => (int) $id)->filter(static fn ($id) => $id > 0)->unique()->values();
    }

    /**
     * Individual attendance rows for admin student Attendance tab (newest first).
     *
     * @return Collection<int, object>
     */
}
