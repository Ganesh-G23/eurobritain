<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\MarkAbsence;
use App\Models\ParentStudentMap;
use App\Models\PortalUser;
use App\Models\StudentAttendance;
use App\Models\StudentClassroomMap;
use App\Models\StudentTeacherMap;
use App\Models\TeacherSetting;
use App\Notifications\MarksUpdatedNotification;
use App\Notifications\PortalNotification;
use App\Support\StudentEnrollmentSync;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class UserTeacherController extends Controller
{
    protected function requireTeacher()
    {
        $portal = session('portal_user');
        $userId = (int) ($portal['id'] ?? 0);
        $role = (int) ($portal['role'] ?? 0);
        if (!$userId || $role !== 1) {
            return [null, redirect('login')];
        }
        return [$userId, null];
    }

    protected function requireTeacherOrStudent()
    {
        $portal = session('portal_user');

        $userId = (int) ($portal['id'] ?? 0);
        $role = (int) ($portal['role'] ?? 0);

        if (!$userId || !in_array($role, [1, 2])) {
            return [null, null, redirect('login')]; // ✅ FIXED
        }

        return [$userId, $role, null];
    }

    public function index()
    {
        return redirect('user/teacher/classrooms');
    }

    public function attendance(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $data = [];
        $data['title'] = 'Attendance';
        $data['active_tab'] = 'teacher_attendance';

        // Placeholder data; integrate with actual attendance tables if available
        $data['filters'] = [
            'classrooms' => \App\Models\Classroom::where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'batches' => \App\Models\Batch::where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
        $data['summary'] = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
        ];
        $data['records'] = collect([]);

        return view('web.user.teacher.attendance', $data);
    }

    public function classrooms()
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $data = [];
        $data['title'] = 'My Classrooms';
        $data['active_tab'] = 'teacher_classrooms';
        $data['teacher_id'] = $teacherId;
        $classrooms = Classroom::where('teacher_id', $teacherId)->orderBy('id', 'desc')->get();

        if ($classrooms->isNotEmpty()) {
            $classroomIds = $classrooms->pluck('id')->all();
            $studentCounts = DB::table('student_classroom_map as scm')
                ->join('batches as b', function ($join) {
                    $join->on('b.id', '=', 'scm.batch_id')->on('b.teacher_id', '=', 'scm.teacher_id');
                })
                ->join('portal_user as pu', 'pu.id', '=', 'scm.student_id')
                ->where('scm.teacher_id', $teacherId)
                ->whereNull('pu.deleted_at')
                ->where('pu.role', 2)
                ->whereIn('b.classroom_id', $classroomIds)
                ->whereNotNull('scm.batch_id')
                ->selectRaw('b.classroom_id as classroom_id, COUNT(DISTINCT scm.student_id) as cnt')
                ->groupBy('b.classroom_id')
                ->pluck('cnt', 'classroom_id');
            $classrooms->each(function (Classroom $c) use ($studentCounts) {
                $cid = (int) $c->id;
                $c->setAttribute('enrolled_student_count', (int) ($studentCounts[$cid] ?? ($studentCounts[(string) $cid] ?? 0)));
            });
        }

        $data['classrooms'] = $classrooms;

        return view('web.user.teacher.classrooms', $data);
    }

    public function batches()
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $data = [];
        $data['title'] = 'My Batches';
        $data['active_tab'] = 'teacher_batches';
        $data['teacher_id'] = $teacherId;
        $data['batches'] = Batch::where('teacher_id', $teacherId)->with('classroom')->orderBy('id', 'desc')->get();
        return view('web.user.teacher.batches', $data);
    }

    public function students(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $data = [];
        $data['title'] = 'My Students';
        $data['active_tab'] = 'teacher_students';
        $data['teacher_id'] = $teacherId;
        $portal = session('portal_user', []);
        $data['teacher_name'] = !empty($portal['name']) ? (string) $portal['name'] : (string) (PortalUser::whereKey($teacherId)->value('name') ?? 'Teacher');

        $mainUrl = url('user/teacher/students');
        $url = [];

        $students = PortalUser::query()
            ->where('role', 2)
            ->whereHas('teachers', static function ($q) use ($teacherId) {
                $q->whereKey($teacherId);
            })
            ->with([
                'studentClassroomMaps' => static function ($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId)->with(['classroom', 'batch']);
                },
            ]);

        $data['search'] = $search = trim((string) ($request->search ?? ''));
        if ($search !== '') {
            $students->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
            $url[] = 'search=' . urlencode($search);
        }

        $data['classroom_id'] = $classroomId = (int) ($request->classroom_id ?? 0);
        if ($classroomId > 0) {
            $students->whereHas('studentClassroomMaps', static function ($q) use ($teacherId, $classroomId) {
                $q->where('teacher_id', $teacherId)->where('classroom_id', $classroomId);
            });
            $url[] = 'classroom_id=' . $classroomId;
        }

        $data['batch_id'] = $batchId = (int) ($request->batch_id ?? 0);
        if ($batchId > 0) {
            $students->whereHas('studentClassroomMaps', static function ($q) use ($teacherId, $batchId) {
                $q->where('teacher_id', $teacherId)->where('batch_id', $batchId);
            });
            $url[] = 'batch_id=' . $batchId;
        }

        $data['page'] = $page = (int) ($request->page ?? 1);
        if ($page < 1) {
            $page = 1;
        }
        $data['per_page'] = $perPage = (int) ($request->per_page ?? 50);
        if ($perPage < 1) {
            $perPage = 50;
        }

        $data['url'] = $mainUrl . (count($url) ? '?' . implode('&', $url) : '');
        $data['num_rows'] = (clone $students)->count('portal_user.id');
        $data['students'] = $students
            ->orderBy('id', 'desc')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();
        $this->hydrateParentFieldsForStudentCollection($data['students']);

        $data['classrooms'] = Classroom::where('teacher_id', $teacherId)->orderBy('name')->get();
        $data['batches'] = Batch::where('teacher_id', $teacherId)->orderBy('name')->get();

        return view('web.user.teacher.students', $data);
    }

    // CRUD: Classrooms
    public function saveClassroom(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'id' => 'nullable',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error_array' => $validator->errors()->toArray()]);
        }
        $classroom = $request->id ? Classroom::where('teacher_id', $teacherId)->find($request->id) : new Classroom();
        if (!$classroom) {
            return response()->json(['status' => 0, 'error' => 'Classroom not found']);
        }
        $classroom->name = $request->name;
        $classroom->teacher_id = $teacherId;
        $classroom->save();
        return response()->json(['status' => 1, 'msg' => 'Saved', 'redirect_url' => url('user/teacher/classrooms')]);
    }

    public function deleteClassroom(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $id = $request->id;
        if (!$id) {
            return response()->json(['status' => 0, 'error' => 'Invalid request']);
        }
        $classroom = Classroom::where('teacher_id', $teacherId)->find($id);
        if (!$classroom) {
            return response()->json(['status' => 0, 'error' => 'Classroom not found']);
        }
        $classroom->delete();
        return response()->json(['status' => 1, 'msg' => 'Deleted', 'redirect_url' => url('user/teacher/classrooms')]);
    }

    // CRUD: Batches
    public function saveBatch(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id',
            'status' => 'required|in:active,pending,inactive',
            'id' => 'nullable',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error_array' => $validator->errors()->toArray()]);
        }
        $classroom = Classroom::where('teacher_id', $teacherId)->find($request->classroom_id);
        if (!$classroom) {
            return response()->json(['status' => 0, 'error' => 'Invalid classroom']);
        }
        $batch = $request->id ? Batch::where('teacher_id', $teacherId)->find($request->id) : new Batch();
        if (!$batch) {
            return response()->json(['status' => 0, 'error' => 'Batch not found']);
        }
        $batch->name = $request->name;
        $batch->classroom_id = $request->classroom_id;
        $batch->teacher_id = $teacherId;
        $batch->status = $request->status;
        $batch->save();
        return response()->json(['status' => 1, 'msg' => 'Saved', 'redirect_url' => url('user/teacher/batches')]);
    }

    public function deleteBatch(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $id = $request->id;
        if (!$id) {
            return response()->json(['status' => 0, 'error' => 'Invalid request']);
        }
        $batch = Batch::where('teacher_id', $teacherId)->find($id);
        if (!$batch) {
            return response()->json(['status' => 0, 'error' => 'Batch not found']);
        }
        $batch->delete();
        return response()->json(['status' => 1, 'msg' => 'Deleted', 'redirect_url' => url('user/teacher/batches')]);
    }

    // CRUD: Students (profile/parent only; classrooms & batches via syncStudentEnrollments — same as admin)
    public function saveStudent(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $idInput = (string) ($request->id ?? '');
        $decodedId = base64_decode($idInput, true);
        $id = is_string($decodedId) && ctype_digit($decodedId) ? (int) $decodedId : (int) $idInput;

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email'],
            'phone' => ['required', 'string'],
            'parent_name' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email',
            'parent_phone' => 'nullable|string|max:30',
        ];
        if ($id) {
            $rules['email'] = ['required', 'email', Rule::unique('portal_user', 'email')->ignore($id)];
            $rules['phone'] = ['required', 'string', Rule::unique('portal_user', 'phone')->ignore($id)];
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error_array' => $validator->errors()->toArray()]);
        }

        if ($id) {
            $owned = PortalUser::where('role', 2)->whereHas('teachers', static fn($q) => $q->whereKey($teacherId))->whereKey($id)->exists();
            if (!$owned) {
                return response()->json(['status' => 0, 'error' => 'Student not found.']);
            }
        }

        DB::beginTransaction();
        try {
            if ($id) {
                $student = PortalUser::where('role', 2)->find($id);
                if (!$student) {
                    DB::rollBack();

                    return response()->json(['status' => 0, 'error' => 'Student not found']);
                }
            } else {
                $studentByEmail = PortalUser::where('role', 2)->where('email', $request->email)->first();
                $studentByPhone = PortalUser::where('role', 2)->where('phone', $request->phone)->first();
                if ($studentByEmail && $studentByPhone && (int) $studentByEmail->id !== (int) $studentByPhone->id) {
                    DB::rollBack();

                    return response()->json(['status' => 0, 'error' => 'Email and phone belong to different students.']);
                }
                $student = $studentByEmail ?: $studentByPhone;
                if (!$student) {
                    if (PortalUser::where('email', $request->email)->where('role', '!=', 2)->exists()) {
                        DB::rollBack();

                        return response()->json(['status' => 0, 'error' => 'Email already used by another user type.']);
                    }
                    if (PortalUser::where('phone', $request->phone)->where('role', '!=', 2)->exists()) {
                        DB::rollBack();

                        return response()->json(['status' => 0, 'error' => 'Phone already used by another user type.']);
                    }
                    $student = new PortalUser();
                    $plain = Str::random(8);
                    $student->password = Hash::make($plain);
                    $student->p = $plain;
                    $student->is_password_changed = 0;
                    $student->role = 2;
                    $student->created_by = $teacherId;
                }
            }

            $student->name = $request->name;
            $student->email = $request->email;
            $student->phone = $request->phone;
            $student->save();

            StudentTeacherMap::firstOrCreate([
                'student_id' => $student->id,
                'teacher_id' => $teacherId,
            ]);

            $parentName = trim((string) $request->parent_name);
            $parentEmail = trim((string) $request->parent_email);
            $parentPhone = trim((string) $request->parent_phone);
            if ($parentName !== '' || $parentEmail !== '' || $parentPhone !== '') {
                $parent = null;
                if ($parentEmail !== '') {
                    $match = PortalUser::where('email', $parentEmail)->first();
                    if ($match && (int) ($match->role ?? 0) !== 3) {
                        DB::rollBack();

                        return response()->json(['status' => 0, 'error' => 'Parent email already used by another user type.']);
                    }
                    if ($match) {
                        $parent = $match;
                    }
                }
                if (!$parent && $parentPhone !== '') {
                    $match = PortalUser::where('phone', $parentPhone)->first();
                    if ($match && (int) ($match->role ?? 0) !== 3) {
                        DB::rollBack();

                        return response()->json(['status' => 0, 'error' => 'Parent phone already used by another user type.']);
                    }
                    if ($match) {
                        $parent = $match;
                    }
                }
                if (!$parent && !empty($student->parent_id)) {
                    $parent = PortalUser::where('role', 3)->find((int) $student->parent_id);
                }
                if (!$parent) {
                    $parent = new PortalUser();
                    $parentPlain = Str::random(8);
                    $parent->password = Hash::make($parentPlain);
                    $parent->p = $parentPlain;
                    $parent->is_password_changed = 0;
                    $parent->role = 3;
                    $parent->created_by = $teacherId;
                } else {
                    if ($parentEmail !== '' && $parentEmail !== (string) $parent->email) {
                        if (PortalUser::where('email', $parentEmail)->where('id', '!=', $parent->id)->exists()) {
                            DB::rollBack();

                            return response()->json(['status' => 0, 'error' => 'Parent email already exists.']);
                        }
                    }
                    if ($parentPhone !== '' && $parentPhone !== (string) $parent->phone) {
                        if (PortalUser::where('phone', $parentPhone)->where('id', '!=', $parent->id)->exists()) {
                            DB::rollBack();

                            return response()->json(['status' => 0, 'error' => 'Parent phone already exists.']);
                        }
                    }
                }
                if ($parentName !== '') {
                    $parent->name = $parentName;
                }
                if ($parentEmail !== '') {
                    $parent->email = $parentEmail;
                }
                if ($parentPhone !== '') {
                    $parent->phone = $parentPhone;
                }
                if ($parent->name === null || $parent->name === '') {
                    $parent->name = 'Parent of ' . $student->name;
                }
                $parent->save();

                ParentStudentMap::firstOrCreate([
                    'parent_id' => $parent->id,
                    'student_id' => $student->id,
                ]);
                if ((int) ($student->parent_id ?? 0) !== (int) $parent->id) {
                    $student->parent_id = $parent->id;
                    $student->save();
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['status' => 0, 'error' => 'Unable to save student. ' . $e->getMessage()]);
        }

        return response()->json(['status' => 1, 'msg' => 'Saved', 'redirect_url' => url('user/teacher/students')]);
    }

    public function syncStudentEnrollments(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $studentId = $request->student_id ? (int) base64_decode((string) $request->student_id) : 0;
        if ($studentId < 1) {
            return response()->json(['status' => 0, 'error' => 'Invalid student.']);
        }

        $student = PortalUser::where('role', 2)->find($studentId);
        if (!$student || !$student->teachers()->whereKey($teacherId)->exists()) {
            return response()->json(['status' => 0, 'error' => 'Student not found.']);
        }

        $pairs = StudentEnrollmentSync::pairsFromRequestArrays((array) $request->input('map_classroom_id', []), (array) $request->input('map_batch_id', []));

        if (count($pairs) < 1) {
            return response()->json(['status' => 0, 'error' => 'Add at least one classroom and batch.']);
        }

        [$ok, $err] = StudentEnrollmentSync::validatePairsForTeacher($teacherId, $pairs);
        if (!$ok) {
            return response()->json(['status' => 0, 'error' => $err]);
        }

        try {
            StudentEnrollmentSync::syncForTeacher($studentId, $teacherId, $pairs);
        } catch (\Throwable $e) {
            return response()->json(['status' => 0, 'error' => 'Unable to save enrollments. ' . $e->getMessage()]);
        }

        return response()->json([
            'status' => 1,
            'msg' => 'Classrooms saved successfully',
            'redirect_url' => url('user/teacher/students'),
        ]);
    }

    public function getBatchesByClassroom(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $classroomId = (int) ($request->classroom_id ?? 0);
        if ($classroomId < 1) {
            return response()->json(['status' => 0, 'error' => 'Classroom ID is required']);
        }

        $classroom = Classroom::where('teacher_id', $teacherId)->find($classroomId);
        if (!$classroom) {
            return response()->json(['status' => 0, 'error' => 'Invalid classroom']);
        }

        $batches = Batch::where('teacher_id', $teacherId)
            ->where('classroom_id', $classroomId)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        return response()->json(['status' => 1, 'data' => $batches]);
    }

    public function deleteStudent(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $idInput = (string) ($request->id ?? '');
        $decodedId = base64_decode($idInput, true);
        $id = is_string($decodedId) && ctype_digit($decodedId) ? (int) $decodedId : (int) $idInput;
        if (!$id) {
            return response()->json(['status' => 0, 'error' => 'Invalid request']);
        }
        $student = PortalUser::where('role', 2)->find($id);
        if (!$student) {
            return response()->json(['status' => 0, 'error' => 'Student not found']);
        }
        StudentClassroomMap::where('student_id', $student->id)->where('teacher_id', $teacherId)->delete();
        StudentTeacherMap::where('student_id', $student->id)->where('teacher_id', $teacherId)->delete();
        $stillMapped = StudentTeacherMap::where('student_id', $student->id)->exists();
        if (!$stillMapped) {
            ParentStudentMap::where('student_id', $student->id)->delete();
            $student->delete();
        }
        return response()->json(['status' => 1, 'msg' => 'Deleted', 'redirect_url' => url('user/teacher/students')]);
    }

    /**
     * @return array{skip?: true, error?: string, pair?: array{classroom_id: int, batch_id: int}}
     */
    private function resolveBulkClassroomBatchPair(int $teacherId, string $classroomRaw, string $batchRaw): array
    {
        $classroomRaw = trim($classroomRaw);
        $batchRaw = trim($batchRaw);
        if ($classroomRaw === '' && $batchRaw === '') {
            return ['skip' => true];
        }
        if ($classroomRaw === '' || $batchRaw === '') {
            return ['error' => 'Classroom and batch are both required for each pair.'];
        }

        $classroom = ctype_digit($classroomRaw)
            ? Classroom::where('teacher_id', $teacherId)->find((int) $classroomRaw)
            : Classroom::where('teacher_id', $teacherId)
                ->whereRaw('LOWER(name) = ?', [strtolower($classroomRaw)])
                ->first();
        $batch = ctype_digit($batchRaw)
            ? Batch::where('teacher_id', $teacherId)->find((int) $batchRaw)
            : Batch::where('teacher_id', $teacherId)
                ->whereRaw('LOWER(name) = ?', [strtolower($batchRaw)])
                ->first();

        if (!$classroom && $batch) {
            $classroom = Classroom::where('teacher_id', $teacherId)->find((int) $batch->classroom_id);
        }
        $classroomId = $classroom ? (int) $classroom->id : 0;
        $batchId = $batch ? (int) $batch->id : 0;

        if ($classroomId < 1 || $batchId < 1) {
            return ['error' => 'Unknown classroom or batch for your account.'];
        }

        $classroom = Classroom::where('teacher_id', $teacherId)->find($classroomId);
        $batch = Batch::where('teacher_id', $teacherId)->find($batchId);
        if (!$classroom) {
            return ['error' => 'Classroom does not belong to your account.'];
        }
        if (!$batch) {
            return ['error' => 'Batch does not belong to your account.'];
        }
        if ((int) $batch->classroom_id !== (int) $classroom->id) {
            $classroom = Classroom::where('teacher_id', $teacherId)->find((int) $batch->classroom_id);
            if (!$classroom) {
                return ['error' => 'Batch is not linked to a valid classroom for your account.'];
            }
            $classroomId = (int) $classroom->id;
        }

        return ['pair' => ['classroom_id' => $classroomId, 'batch_id' => $batchId]];
    }

    public function downloadStudentBulkSample()
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $firstBatch = Batch::where('teacher_id', $teacherId)->orderBy('id')->first();
        $firstClassroom = $firstBatch ? Classroom::where('teacher_id', $teacherId)->find((int) $firstBatch->classroom_id) : Classroom::where('teacher_id', $teacherId)->orderBy('id')->first();
        $sampleClassroomName = $firstClassroom ? (string) $firstClassroom->name : '8th Class';
        $sampleBatchName = $firstBatch ? (string) $firstBatch->name : 'Batch A';

        $secondClassroom = Classroom::where('teacher_id', $teacherId)->orderBy('id')->skip(1)->first();
        $secondBatch = $secondClassroom ? Batch::where('teacher_id', $teacherId)->where('classroom_id', $secondClassroom->id)->orderBy('id')->first() : null;
        $sampleClassroom2 = $secondClassroom ? (string) $secondClassroom->name : '';
        $sampleBatch2 = $secondBatch ? (string) $secondBatch->name : '';

        $rows = [['name', 'email', 'phone', 'classroom', 'batch', 'classroom_2', 'batch_2', 'classroom_3', 'batch_3', 'parent_name', 'parent_email', 'parent_phone'], ['John Doe', 'john.doe@example.com', '9876543210', $sampleClassroomName, $sampleBatchName, '', '', '', '', 'Parent One', 'parent.one@example.com', '9000000001'], ['Jane Smith', 'jane.smith@example.com', '9876543211', $sampleClassroomName, $sampleBatchName, $sampleClassroom2, $sampleBatch2, '', '', 'Parent Two', 'parent.two@example.com', '9000000002']];

        $filename = 'student_bulk_sample.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(
            function () use ($rows) {
                $out = fopen('php://output', 'w');
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            },
            200,
            $headers,
        );
    }

    public function bulkUploadStudents(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error_array' => $validator->errors()->toArray()]);
        }

        $realPath = $request->file('file')->getRealPath();
        $handle = @fopen($realPath, 'r');
        if (!$handle) {
            return response()->json(['status' => 0, 'error' => 'Unable to read uploaded file']);
        }

        $header = fgetcsv($handle);
        if (!$header || !is_array($header)) {
            fclose($handle);
            return response()->json(['status' => 0, 'error' => 'CSV file is empty']);
        }

        $normalize = static function ($value) {
            $normalized = strtolower(trim((string) $value));
            return preg_replace('/^\xEF\xBB\xBF/', '', $normalized) ?? $normalized;
        };
        $headerMap = [];
        foreach ($header as $index => $column) {
            $headerMap[$normalize($column)] = $index;
        }

        if (isset($headerMap['classroom']) && !isset($headerMap['classroom_id'])) {
            $headerMap['classroom_id'] = $headerMap['classroom'];
        }
        if (isset($headerMap['batch']) && !isset($headerMap['batch_id'])) {
            $headerMap['batch_id'] = $headerMap['batch'];
        }

        $requiredColumns = ['name', 'email', 'phone', 'classroom_id', 'batch_id'];
        $missing = [];
        foreach ($requiredColumns as $column) {
            if (!array_key_exists($column, $headerMap)) {
                $missing[] = $column;
            }
        }
        if (!empty($missing)) {
            fclose($handle);
            return response()->json(['status' => 0, 'error' => 'Missing required columns: ' . implode(', ', $missing)]);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (!is_array($row)) {
                continue;
            }

            $name = trim((string) ($row[$headerMap['name']] ?? ''));
            $email = trim((string) ($row[$headerMap['email']] ?? ''));
            $phone = trim((string) ($row[$headerMap['phone']] ?? ''));
            $classroomRaw = trim((string) ($row[$headerMap['classroom_id']] ?? ''));
            $batchRaw = trim((string) ($row[$headerMap['batch_id']] ?? ''));
            $parentName = array_key_exists('parent_name', $headerMap) ? trim((string) ($row[$headerMap['parent_name']] ?? '')) : '';
            $parentEmail = array_key_exists('parent_email', $headerMap) ? trim((string) ($row[$headerMap['parent_email']] ?? '')) : '';
            $parentPhone = array_key_exists('parent_phone', $headerMap) ? trim((string) ($row[$headerMap['parent_phone']] ?? '')) : '';

            if ($name === '' && $email === '' && $phone === '' && $classroomRaw === '' && $batchRaw === '') {
                continue;
            }

            $r1 = $this->resolveBulkClassroomBatchPair($teacherId, $classroomRaw, $batchRaw);
            if (isset($r1['error'])) {
                $errors[] = 'Row ' . $line . ': ' . $r1['error'];
                $skipped++;
                continue;
            }
            if (isset($r1['skip'])) {
                $errors[] = 'Row ' . $line . ': classroom and batch are required (first pair).';
                $skipped++;
                continue;
            }

            $pairsAssoc = [];
            $p1 = $r1['pair'];
            $pairsAssoc[$p1['classroom_id'] . '-' . $p1['batch_id']] = $p1;

            foreach ([['classroom_2', 'batch_2'], ['classroom_3', 'batch_3']] as $cols) {
                [$ck, $bk] = $cols;
                if (!isset($headerMap[$ck]) || !isset($headerMap[$bk])) {
                    continue;
                }
                $cRaw = trim((string) ($row[$headerMap[$ck]] ?? ''));
                $bRaw = trim((string) ($row[$headerMap[$bk]] ?? ''));
                $rx = $this->resolveBulkClassroomBatchPair($teacherId, $cRaw, $bRaw);
                if (isset($rx['skip'])) {
                    continue;
                }
                if (isset($rx['error'])) {
                    $errors[] = 'Row ' . $line . ' (' . $ck . '/' . $bk . '): ' . $rx['error'];
                    $skipped++;
                    continue 2;
                }
                $px = $rx['pair'];
                $pairsAssoc[$px['classroom_id'] . '-' . $px['batch_id']] = $px;
            }

            $pairs = array_values($pairsAssoc);

            [$pairsOk, $pairsErr] = StudentEnrollmentSync::validatePairsForTeacher($teacherId, $pairs);
            if (!$pairsOk) {
                $errors[] = 'Row ' . $line . ': ' . $pairsErr;
                $skipped++;
                continue;
            }

            $firstPair = $pairs[0];
            $classroomId = $firstPair['classroom_id'];
            $batchId = $firstPair['batch_id'];

            $rowPayload = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'classroom_id' => $classroomId,
                'batch_id' => $batchId,
            ];
            $rowValidator = Validator::make($rowPayload, [
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string|max:30',
                'classroom_id' => 'required|integer',
                'batch_id' => 'required|integer',
            ]);
            if ($rowValidator->fails()) {
                $errors[] =
                    'Row ' .
                    $line .
                    ': ' .
                    implode(
                        ' ',
                        array_map(static function ($msg) {
                            return is_array($msg) ? $msg[0] ?? '' : (string) $msg;
                        }, $rowValidator->errors()->toArray()),
                    );
                $skipped++;
                continue;
            }

            $conflictUser = PortalUser::withTrashed()->where('email', $email)->first();
            if ($conflictUser && (int) ($conflictUser->role ?? 0) !== 2) {
                $errors[] = 'Row ' . $line . ': email already used by a non-student account.';
                $skipped++;
                continue;
            }

            $studentByEmail = PortalUser::withTrashed()->where('role', 2)->where('email', $email)->first();
            if ($studentByEmail) {
                $phoneTakenByOther = PortalUser::where('role', 2)->where('phone', $phone)->where('id', '!=', $studentByEmail->id)->exists();
                if ($phoneTakenByOther) {
                    $errors[] = 'Row ' . $line . ': phone already registered to another student.';
                    $skipped++;
                    continue;
                }

                if ($studentByEmail->trashed()) {
                    $studentByEmail->restore();
                    $plain = Str::random(8);
                    $studentByEmail->password = Hash::make($plain);
                    $studentByEmail->p = $plain;
                    $studentByEmail->is_password_changed = 0;
                }

                $studentByEmail->name = $name;
                $studentByEmail->phone = $phone;
                $studentByEmail->classroom_id = $classroomId;
                $studentByEmail->batch_id = $batchId;
                $studentByEmail->created_by = $teacherId;

                try {
                    $studentByEmail->save();
                    StudentTeacherMap::firstOrCreate([
                        'student_id' => $studentByEmail->id,
                        'teacher_id' => $teacherId,
                    ]);
                    StudentEnrollmentSync::syncForTeacher($studentByEmail->id, $teacherId, $pairs);
                    $updated++;
                } catch (\Throwable $e) {
                    $errors[] = 'Row ' . $line . ': failed to update student (' . $e->getMessage() . ')';
                    $skipped++;
                    continue;
                }
                $student = $studentByEmail;
            } else {
                if (PortalUser::where('role', 2)->where('phone', $phone)->exists()) {
                    $errors[] = 'Row ' . $line . ': phone already registered to another student.';
                    $skipped++;
                    continue;
                }

                $student = new PortalUser();
                $plain = Str::random(8);
                $student->password = Hash::make($plain);
                $student->p = $plain;
                $student->is_password_changed = 0;
                $student->role = 2;
                $student->created_by = $teacherId;

                $student->name = $name;
                $student->email = $email;
                $student->phone = $phone;
                $student->classroom_id = $classroomId;
                $student->batch_id = $batchId;
                try {
                    $student->save();
                    StudentTeacherMap::firstOrCreate([
                        'student_id' => $student->id,
                        'teacher_id' => $teacherId,
                    ]);
                    StudentEnrollmentSync::syncForTeacher($student->id, $teacherId, $pairs);
                    $created++;
                } catch (\Throwable $e) {
                    $errors[] = 'Row ' . $line . ': failed to insert (' . $e->getMessage() . ')';
                    $skipped++;
                    continue;
                }
            }

            // Optional parent creation/link (role=3)
            if ($parentName !== '' || $parentEmail !== '' || $parentPhone !== '') {
                if ($parentEmail !== '' && PortalUser::where('email', $parentEmail)->exists()) {
                    $errors[] = 'Row ' . $line . ': parent email already exists.';
                    $skipped++;
                    continue;
                }
                if ($parentPhone !== '' && PortalUser::where('phone', $parentPhone)->exists()) {
                    $errors[] = 'Row ' . $line . ': parent phone already exists.';
                    $skipped++;
                    continue;
                }
                $parent = new PortalUser();
                $pPlain = Str::random(8);
                $parent->password = Hash::make($pPlain);
                $parent->p = $pPlain;
                $parent->is_password_changed = 0;
                $parent->role = 3;
                $parent->created_by = $teacherId;
                $parent->name = $parentName !== '' ? $parentName : 'Parent of ' . $student->name;
                if ($parentEmail !== '') {
                    $parent->email = $parentEmail;
                }
                if ($parentPhone !== '') {
                    $parent->phone = $parentPhone;
                }
                try {
                    $parent->save();
                    $student->parent_id = $parent->id;
                    $student->save();
                } catch (\Throwable $e) {
                    $errors[] = 'Row ' . $line . ': failed to create parent (' . $e->getMessage() . ')';
                }
            }
        }

        fclose($handle);

        $message = "Bulk upload completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.";
        $status = $created + $updated > 0 ? 1 : 0;
        return response()->json([
            'status' => $status,
            'msg' => $message,
            'summary' => [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
            ],
            'errors' => $errors,
            'error' => $status === 0 ? 'No rows were inserted/updated. Please check row issues.' : null,
            'redirect_url' => url('user/teacher/students'),
        ]);
    }

    public function viewStudent($id)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $decodedId = base64_decode((string) $id, true);
        if ($decodedId === false) {
            $decodedId = $id;
        }

        $student = PortalUser::where('role', 2)
            ->whereHas('teachers', static fn($q) => $q->whereKey($teacherId))
            ->with(['classroom', 'batch.classroom', 'teachers'])
            ->find($decodedId);

        if (!$student) {
            return redirect('user/teacher/students');
        }

        $data = [];
        $data['title'] = 'View Student';
        $data['active_tab'] = 'teacher_students';
        $maps = StudentClassroomMap::where('student_id', $student->id)
            ->where('teacher_id', $teacherId)
            ->with(['classroom', 'batch'])
            ->orderBy('id')
            ->get();
        $data['student_classroom_maps'] = $maps;
        $first = $maps->first();
        if ($first) {
            $student->classroom_id = $first->classroom_id;
            $student->batch_id = $first->batch_id;
            $student->setRelation('classroom', $first->classroom);
            if ($first->batch) {
                $first->batch->loadMissing('classroom');
            }
            $student->setRelation('batch', $first->batch);
        }
        $data['student'] = $student;
        $data['student_teachers'] = $student->teachers;

        return view('web.user.teacher.student_show', $data);
    }

    public function classroomsDetails($id)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $classroom = Classroom::with(['batches' => fn($q) => $q->orderBy('name')])->find($id);

        if (!$classroom || (int) $classroom->teacher_id !== $teacherId) {
            return redirect('user/teacher/classrooms');
        }

        $mapRows = StudentClassroomMap::query()->where('teacher_id', $teacherId)->where('classroom_id', $classroom->id)->whereNotNull('batch_id')->get();

        $studentIds = $mapRows->pluck('student_id')->unique()->filter()->values();
        $studentsById = $studentIds->isEmpty() ? collect() : PortalUser::query()->whereIn('id', $studentIds)->where('role', 2)->whereNull('deleted_at')->get()->keyBy('id');

        $this->hydrateParentFieldsForStudentCollection($studentsById->values());

        $mapsByBatch = $mapRows->groupBy('batch_id');
        $studentsByBatch = [];
        foreach ($classroom->batches as $batch) {
            $seen = [];
            $list = collect();
            foreach ($mapsByBatch->get($batch->id, collect()) as $map) {
                $student = $studentsById->get($map->student_id);
                if (!$student || isset($seen[$student->id])) {
                    continue;
                }
                $seen[$student->id] = true;
                $list->push($student);
            }
            $studentsByBatch[$batch->id] = $list->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }

        $totalStudentsInClassroom = $mapRows->pluck('student_id')->unique()->filter(fn($sid) => $studentsById->has($sid))->count();

        $batchIds = $classroom->batches->pluck('id')->map(fn($id) => (int) $id)->values();
        $examsGrouped = $batchIds->isEmpty() ? collect() : Exam::query()->whereIn('batch_id', $batchIds)->orderBy('exam_date')->orderBy('id')->get()->groupBy(fn(Exam $e) => (int) $e->batch_id);

        $examsByBatch = [];
        foreach ($classroom->batches as $batch) {
            $examsByBatch[$batch->id] = $examsGrouped->get((int) $batch->id, collect());
        }

        $marksByExamStudent = [];
        if (Schema::hasTable('marks') && $examsGrouped->isNotEmpty()) {
            $examIds = $examsGrouped->flatten()->pluck('id')->unique()->values();
            foreach (Mark::query()->whereIn('exam_id', $examIds)->get() as $markRow) {
                $marksByExamStudent[(int) $markRow->exam_id][(int) $markRow->student_id] = $markRow->marks;
            }
        }

        $examIdsForAbsences = $examsGrouped->isNotEmpty() ? $examsGrouped->flatten()->pluck('id')->unique()->values() : collect();
        $markAbsencesByExamStudent = self::markAbsenceModeMapForExamIds($examIdsForAbsences);

        $teacherSetting = TeacherSetting::query()->where('teacher_id', $classroom->teacher_id)->first();
        $teacherMarkDisplaySetting = (int) ($teacherSetting?->count_setting ?? TeacherSetting::COUNT_AS_ZERO);
        if ($teacherMarkDisplaySetting !== TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE) {
            $teacherMarkDisplaySetting = TeacherSetting::COUNT_AS_ZERO;
        }

        $data = [];
        $data['title'] = 'Classroom: ' . $classroom->name;
        $data['active_tab'] = 'teacher_classrooms';
        $data['classroom'] = $classroom;
        $data['students_by_batch'] = $studentsByBatch;
        $data['total_students_in_classroom'] = $totalStudentsInClassroom;
        $data['exams_by_batch'] = $examsByBatch;
        $data['marks_by_exam_student'] = $marksByExamStudent;
        $data['mark_absences_by_exam_student'] = $markAbsencesByExamStudent;
        $data['teacher_mark_display_setting'] = $teacherMarkDisplaySetting;
        $data['marks_table_ready'] = Schema::hasTable('marks');

        $attendanceDatesByBatch = [];
        $attendanceStatusByBatchDateStudent = [];
        $attendanceModalStudentsByBatch = [];
        $data['attendance_table_ready'] = Schema::hasTable('student_attendances');

        foreach ($classroom->batches as $batch) {
            $bid = (int) $batch->id;
            $list = $studentsByBatch[$bid] ?? collect();
            $attendanceModalStudentsByBatch[$bid] = $list->map(static fn(PortalUser $s) => [
                'id' => (int) $s->id,
                'name' => (string) $s->name,
            ])->values()->all();
        }

        if ($data['attendance_table_ready'] && $batchIds->isNotEmpty()) {
            $attRows = StudentAttendance::query()
                ->whereIn('batch_id', $batchIds)
                ->get(['batch_id', 'student_id', 'date', 'attendance_status']);

            $dateKeysByBatch = [];
            foreach ($attRows as $row) {
                $bid = (int) $row->batch_id;
                $d = $row->date->format('Y-m-d');
                $dateKeysByBatch[$bid][$d] = true;
                $sid = (int) $row->student_id;
                $attendanceStatusByBatchDateStudent[$bid][$d][$sid] = (string) $row->attendance_status;
            }
            foreach ($classroom->batches as $batch) {
                $bid = (int) $batch->id;
                $keys = array_keys($dateKeysByBatch[$bid] ?? []);
                sort($keys, SORT_STRING);
                $attendanceDatesByBatch[$bid] = $keys;
            }
        }

        $data['attendance_dates_by_batch'] = $attendanceDatesByBatch;
        $data['attendance_status_by_batch_date_student'] = $attendanceStatusByBatchDateStudent;
        $data['attendance_modal_students_by_batch'] = $attendanceModalStudentsByBatch;

        return view('web.user.teacher.classroom_details', $data);
    }

    public function saveBatchAttendanceDay(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            $this->response['error'] = 'Unauthorized request';
        } elseif (!Schema::hasTable('student_attendances')) {
            $this->response['error'] = 'Attendance is not available.';
        } else {
            $validation = Validator::make($request->all(), [
                'batch_id' => 'required|integer',
                'date' => 'required',
                'student_id' => 'required|integer',
                'attendance_status' => ['required', Rule::in(['P', 'A', 'L'])],
                'return_classroom_id' => 'nullable|integer',
            ]);

            if (!$validation->fails()) {
                $batch = Batch::query()->where('teacher_id', $teacherId)->whereKey((int) $request->batch_id)->first();

                if (!$batch) {
                    $this->response['error_array'] = formatErrors([
                        'batch_id' => ['Batch not found.'],
                    ]);
                } else {
                    $classroomId = (int) $request->input('return_classroom_id', 0);
                    if ($classroomId > 0 && (int) $batch->classroom_id !== $classroomId) {
                        $this->response['error_array'] = formatErrors([
                            'batch_id' => ['This batch does not belong to that classroom.'],
                        ]);
                    } else {
                        $allowedStudentIds = StudentClassroomMap::query()
                            ->where('teacher_id', $teacherId)
                            ->where('batch_id', $batch->id)
                            ->pluck('student_id')
                            ->unique()
                            ->filter()
                            ->map(static fn($id) => (int) $id)
                            ->all();

                        $allowedSet = array_fill_keys($allowedStudentIds, true);
                        $date = \Carbon\Carbon::parse($request->date)->format('Y-m-d');
                        $sid = (int) $request->student_id;
                        $status = (string) $request->attendance_status;

                        if (!isset($allowedSet[$sid])) {
                            $this->response['error_array'] = formatErrors([
                                'student_id' => ['This student is not in the selected batch.'],
                            ]);
                        } else {
                            DB::transaction(function () use ($batch, $date, $sid, $status) {
                                StudentAttendance::query()->updateOrCreate(
                                    [
                                        'batch_id' => $batch->id,
                                        'student_id' => $sid,
                                        'date' => $date,
                                    ],
                                    [
                                        'attendance_status' => $status,
                                    ],
                                );
                            });

                            $this->response['status'] = 1;
                            $this->response['msg'] = 'Attendance saved.';
                            $this->response['redirect_url'] = url('user/teacher/classrooms/details/' . $batch->classroom_id);
                        }
                    }
                }
            } else {
                $this->response['error_array'] = formatErrors($validation->errors()->toArray());
            }
        }

        echo json_encode($this->response);
    }

    /**
     * Save one date column for all students in a batch (same idea as saveExamMarksColumn).
     */
    public function saveAttendanceColumn(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        if (!Schema::hasTable('student_attendances')) {
            return response()->json(['status' => 0, 'error' => 'Attendance is not available']);
        }

        $validator = Validator::make($request->all(), [
            'batch_id' => 'required|integer',
            'attendance_date' => 'required|date',
            'statuses' => 'required|array',
            'statuses.*' => 'nullable|in:P,A,L',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error_array' => $validator->errors()->toArray()]);
        }

        $batch = Batch::query()->where('teacher_id', $teacherId)->whereKey((int) $request->batch_id)->first();

        if (!$batch) {
            return response()->json(['status' => 0, 'error' => 'Batch not found']);
        }

        $allowedStudentIds = StudentClassroomMap::query()
            ->where('teacher_id', $teacherId)
            ->where('batch_id', $batch->id)
            ->pluck('student_id')
            ->unique()
            ->filter()
            ->map(static fn($id) => (int) $id)
            ->all();

        $allowedSet = array_fill_keys($allowedStudentIds, true);
        $date = \Carbon\Carbon::parse($request->attendance_date)->format('Y-m-d');
        $statuses = $request->input('statuses', []);

        DB::transaction(function () use ($batch, $date, $statuses, $allowedSet) {
            foreach ($statuses as $studentId => $raw) {
                $sid = (int) $studentId;
                if (!isset($allowedSet[$sid])) {
                    continue;
                }
                $v = $raw === null ? '' : trim((string) $raw);
                if ($v === '') {
                    StudentAttendance::query()
                        ->where('batch_id', $batch->id)
                        ->where('student_id', $sid)
                        ->whereDate('date', $date)
                        ->delete();

                    continue;
                }

                StudentAttendance::query()->updateOrCreate(
                    [
                        'batch_id' => $batch->id,
                        'student_id' => $sid,
                        'date' => $date,
                    ],
                    [
                        'attendance_status' => $v,
                    ],
                );
            }
        });

        return response()->json([
            'status' => 1,
            'msg' => 'Attendance saved for this date.',
        ]);
    }

    /**
     * GET: CSV template for one batch + date (import / template download).
     * POST: Multi-date export from the grid (same idea as marks export — selected columns + students).
     */
    public function exportClassroomAttendance(Request $request, $id)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        if (!Schema::hasTable('student_attendances')) {
            abort(404, 'Attendance is not available.');
        }

        $classroom = Classroom::with(['batches' => fn($q) => $q->orderBy('name')])->find($id);

        if (!$classroom || (int) $classroom->teacher_id !== $teacherId) {
            abort(404);
        }

        $batchId = (int) $request->input('batch_id', $request->query('batch_id', 0));
        if ($batchId <= 0) {
            abort(422, 'batch_id is required.');
        }

        $batch = Batch::query()->where('classroom_id', $classroom->id)->where('teacher_id', $teacherId)->whereKey($batchId)->first();

        if (!$batch) {
            abort(404, 'Batch not found.');
        }

        $mapRows = StudentClassroomMap::query()->where('teacher_id', $teacherId)->where('classroom_id', $classroom->id)->where('batch_id', $batch->id)->get();

        $studentIds = $mapRows->pluck('student_id')->unique()->filter()->values();
        $studentsById = $studentIds->isEmpty() ? collect() : PortalUser::query()->whereIn('id', $studentIds)->where('role', 2)->whereNull('deleted_at')->get()->keyBy('id');

        $seen = [];
        $students = collect();
        foreach ($mapRows as $map) {
            $student = $studentsById->get($map->student_id);
            if (!$student || isset($seen[$student->id])) {
                continue;
            }
            $seen[$student->id] = true;
            $students->push($student);
        }
        $students = $students->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $slugClass = Str::slug($classroom->name, '-') ?: 'classroom';
        $slugBatch = Str::slug($batch->name, '-') ?: 'batch';

        if ($request->isMethod('post')) {
            $allowedDatesOrdered = StudentAttendance::query()
                ->where('batch_id', $batch->id)
                ->orderBy('date')
                ->get()
                ->pluck('date')
                ->map(static fn($dt) => \Carbon\Carbon::parse($dt)->format('Y-m-d'))
                ->unique()
                ->values()
                ->all();

            if ($allowedDatesOrdered === []) {
                abort(422, 'No attendance dates for this batch yet.');
            }

            $allowedDateSet = array_fill_keys($allowedDatesOrdered, true);

            $datesInput = $request->input('attendance_dates', []);
            if (!is_array($datesInput)) {
                $datesInput = $datesInput !== null && $datesInput !== '' ? [$datesInput] : [];
            }

            $selectedDates = [];
            $picked = [];
            foreach ($datesInput as $raw) {
                try {
                    $d = \Carbon\Carbon::parse($raw)->format('Y-m-d');
                } catch (\Throwable $e) {
                    continue;
                }
                if (isset($allowedDateSet[$d]) && !isset($picked[$d])) {
                    $picked[$d] = true;
                    $selectedDates[] = $d;
                }
            }
            if ($selectedDates === []) {
                $selectedDates = $allowedDatesOrdered;
            }

            $allowedStudentIdSet = array_fill_keys($students->pluck('id')->map(static fn($i) => (int) $i)->values()->all(), true);

            $studentIdsInput = $request->input('student_ids', []);
            if (!is_array($studentIdsInput)) {
                $studentIdsInput = [];
            }
            $studentIdsOrdered = array_values(array_unique(array_map('intval', array_filter($studentIdsInput))));
            if ($studentIdsOrdered === []) {
                abort(422, 'No students to export.');
            }
            foreach ($studentIdsOrdered as $sid) {
                if (!isset($allowedStudentIdSet[$sid])) {
                    abort(422, 'Invalid student in export.');
                }
            }
            $studentsKeyed = $students->keyBy('id');
            $studentsExport = collect();
            foreach ($studentIdsOrdered as $sid) {
                $s = $studentsKeyed->get($sid);
                if ($s) {
                    $studentsExport->push($s);
                }
            }

            $byDateSid = [];
            foreach ($selectedDates as $d) {
                $byDateSid[$d] = [];
            }
            $attQuery = StudentAttendance::query()->where('batch_id', $batch->id);
            $attQuery->where(function ($q) use ($selectedDates) {
                foreach ($selectedDates as $d) {
                    $q->orWhereDate('date', $d);
                }
            });
            foreach ($attQuery->get(['student_id', 'date', 'attendance_status']) as $row) {
                $d = $row->date->format('Y-m-d');
                if (!isset($byDateSid[$d])) {
                    continue;
                }
                $byDateSid[$d][(int) $row->student_id] = (string) $row->attendance_status;
            }

            $filename = $slugClass . '-' . $slugBatch . '-attendance-' . now()->format('Y-m-d') . '.csv';

            return response()->streamDownload(
                function () use ($classroom, $batch, $studentsExport, $selectedDates, $byDateSid) {
                    $out = fopen('php://output', 'w');
                    fwrite($out, "\xEF\xBB\xBF");

                    $header = ['#', 'Student name', 'Classroom', 'Batch'];
                    foreach ($selectedDates as $d) {
                        $header[] = \Carbon\Carbon::parse($d)->format('j F Y');
                    }
                    fputcsv($out, $header);

                    $rowNum = 0;
                    foreach ($studentsExport as $student) {
                        $rowNum++;
                        $sid = (int) $student->id;
                        $row = [$rowNum, $student->name, $classroom->name, $batch->name];
                        foreach ($selectedDates as $d) {
                            $row[] = $byDateSid[$d][$sid] ?? '';
                        }
                        fputcsv($out, $row);
                    }

                    fclose($out);
                },
                $filename,
                [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                ],
            );
        }

        $dateRaw = $request->query('attendance_date', '');
        if (trim((string) $dateRaw) === '') {
            abort(422, 'batch_id and attendance_date are required.');
        }

        try {
            $dateYmd = \Carbon\Carbon::parse($dateRaw)->format('Y-m-d');
        } catch (\Throwable $e) {
            abort(422, 'Invalid attendance_date.');
        }

        $statusBySid = StudentAttendance::query()
            ->where('batch_id', $batch->id)
            ->whereDate('date', $dateYmd)
            ->get()
            ->keyBy('student_id');

        $filename = $slugClass . '-' . $slugBatch . '-attendance-' . $dateYmd . '.csv';

        return response()->streamDownload(
            function () use ($classroom, $batch, $students, $dateYmd, $statusBySid) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");

                fputcsv($out, ['#', 'Student name', 'Classroom', 'Batch', 'Status']);

                $rowNum = 0;
                foreach ($students as $student) {
                    $rowNum++;
                    $sid = (int) $student->id;
                    $st = $statusBySid->get($sid);
                    $cell = $st ? (string) $st->attendance_status : '';
                    fputcsv($out, [$rowNum, $student->name, $classroom->name, $batch->name, $cell]);
                }

                fclose($out);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ],
        );
    }

    public function importAttendanceFromCsv(Request $request, $id)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        if (!Schema::hasTable('student_attendances')) {
            return redirect()->back()->with('import_attendance_error', 'Attendance storage is not available.');
        }

        $validated = $request->validate([
            'batch_id' => 'required|integer',
            'attendance_date' => 'required|date',
            'csv_file' => 'required|file|max:5120',
        ]);

        $classroom = Classroom::query()->find($id);

        if (!$classroom || (int) $classroom->teacher_id !== $teacherId) {
            return redirect()->back()->with('import_attendance_error', 'Classroom not found.');
        }

        $batch = Batch::query()->where('classroom_id', $classroom->id)->where('teacher_id', $teacherId)->whereKey((int) $validated['batch_id'])->first();

        if (!$batch) {
            return redirect()->back()->with('import_attendance_error', 'Batch not found.');
        }

        $file = $request->file('csv_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('import_attendance_error', 'Invalid upload file.');
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return redirect()->back()->with('import_attendance_error', 'File must be a .csv or .txt file.');
        }

        $dateYmd = \Carbon\Carbon::parse($validated['attendance_date'])->format('Y-m-d');

        $path = $file->getRealPath();
        if (!$path || !is_readable($path)) {
            return redirect()->back()->with('import_attendance_error', 'Could not read the uploaded file.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return redirect()->back()->with('import_attendance_error', 'Could not open the uploaded file.');
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $line1 = fgetcsv($handle);
        if ($line1 === false || $line1 === [null] || $line1 === []) {
            fclose($handle);

            return redirect()->back()->with('import_attendance_error', 'The CSV file is empty.');
        }

        $h1First = strtolower(trim((string) ($line1[1] ?? '')));
        $h4First = strtolower(trim((string) ($line1[4] ?? '')));
        $header = null;
        $headerLineNum = 1;

        if ($h1First === 'student name' && $h4First === 'status') {
            $header = $line1;
        } else {
            $meta = trim((string) ($line1[4] ?? ''));
            if (!preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $meta, $m) || $m[1] !== $dateYmd) {
                fclose($handle);

                return redirect()->back()->with('import_attendance_error', 'This file does not match the selected date. Choose the same date you used for Download template, or download the template again.');
            }
            fgetcsv($handle);
            $header = fgetcsv($handle);
            $headerLineNum = 3;
            if ($header === false || $header === [null] || $header === []) {
                fclose($handle);

                return redirect()->back()->with('import_attendance_error', 'The CSV file is missing the header row.');
            }
        }

        $h1 = strtolower(trim((string) ($header[1] ?? '')));
        $h4 = strtolower(trim((string) ($header[4] ?? '')));
        if ($h1 !== 'student name' || $h4 !== 'status') {
            fclose($handle);

            return redirect()->back()->with('import_attendance_error', 'Unexpected header row. Download the template again (first row must be: #, Student name, Classroom, Batch, Status).');
        }

        $allowedStudentIdSet = array_fill_keys(
            StudentClassroomMap::query()
                ->where('teacher_id', $teacherId)
                ->where('classroom_id', $classroom->id)
                ->where('batch_id', $batch->id)
                ->pluck('student_id')
                ->map(static fn($sid) => (int) $sid)
                ->unique()
                ->values()
                ->all(),
            true,
        );

        $studentsByLowerName = [];
        $allowedIds = array_keys($allowedStudentIdSet);
        foreach (
            PortalUser::query()
                ->whereIn('id', $allowedIds)
                ->where('role', 2)
                ->whereNull('deleted_at')
                ->get(['id', 'name']) as $pu
        ) {
            $nk = strtolower(trim((string) ($pu->name ?? '')));
            if ($nk === '') {
                continue;
            }
            if (!isset($studentsByLowerName[$nk])) {
                $studentsByLowerName[$nk] = [];
            }
            $studentsByLowerName[$nk][] = (int) $pu->id;
        }

        $importedRows = 0;
        $lineNum = $headerLineNum;

        try {
            DB::transaction(function () use ($handle, $batch, $dateYmd, $studentsByLowerName, $allowedStudentIdSet, &$importedRows, &$lineNum) {
                while (($row = fgetcsv($handle)) !== false) {
                    $lineNum++;
                    if ($row === [null] || $row === []) {
                        continue;
                    }
                    $row = array_pad($row, 5, '');
                    $studentName = trim((string) ($row[1] ?? ''));
                    if ($studentName === '') {
                        continue;
                    }

                    $nameKey = strtolower($studentName);
                    $matches = $studentsByLowerName[$nameKey] ?? [];
                    if ($matches === []) {
                        throw new \RuntimeException('Row ' . $lineNum . ': no student named "' . $studentName . '" in this batch.');
                    }
                    if (count($matches) > 1) {
                        throw new \RuntimeException('Row ' . $lineNum . ': more than one student named "' . $studentName . '" in this batch; names must be unique for this import format.');
                    }
                    $studentId = (int) $matches[0];
                    if (!isset($allowedStudentIdSet[$studentId])) {
                        throw new \RuntimeException('Row ' . $lineNum . ': student is not in this batch.');
                    }

                    $raw = trim((string) ($row[4] ?? ''));
                    $normalized = $this->normalizeAttendanceImportStatus($raw);
                    if ($normalized === false) {
                        throw new \RuntimeException('Row ' . $lineNum . ': Status must be P, A, or L (or leave blank to clear).');
                    }

                    if ($normalized === '') {
                        StudentAttendance::query()
                            ->where('batch_id', $batch->id)
                            ->where('student_id', $studentId)
                            ->whereDate('date', $dateYmd)
                            ->delete();
                    } else {
                        StudentAttendance::query()->updateOrCreate(
                            [
                                'batch_id' => $batch->id,
                                'student_id' => $studentId,
                                'date' => $dateYmd,
                            ],
                            [
                                'attendance_status' => $normalized,
                            ],
                        );
                    }

                    $importedRows++;
                }
            });
        } catch (\RuntimeException $e) {
            fclose($handle);

            return redirect()->back()->with('import_attendance_error', $e->getMessage());
        }

        fclose($handle);

        return redirect()
            ->back()
            ->with('import_attendance_success', 'Attendance import finished. ' . $importedRows . ' student row(s) saved for ' . $dateYmd . ' (' . $batch->name . ').');
    }

    /**
     * @return string Normalized P/A/L, '' to clear, false if invalid non-empty
     */
    protected function normalizeAttendanceImportStatus(string $raw): string|false
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        $u = strtoupper($raw);
        if ($u === 'P' || $u === 'A' || $u === 'L') {
            return $u;
        }

        return false;
    }

    public function exportClassroomMarks(Request $request, $id)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $classroom = Classroom::with(['batches' => fn($q) => $q->orderBy('name')])->find($id);

        if (!$classroom || (int) $classroom->teacher_id !== $teacherId) {
            abort(404);
        }

        $batchId = (int) $request->input('batch_id', $request->query('batch_id', 0));
        if ($batchId <= 0) {
            abort(404, 'Batch is required for export');
        }

        $batch = Batch::query()->where('classroom_id', $classroom->id)->where('teacher_id', $teacherId)->whereKey($batchId)->first();

        if (!$batch) {
            abort(404);
        }

        $mapRows = StudentClassroomMap::query()->where('teacher_id', $teacherId)->where('classroom_id', $classroom->id)->where('batch_id', $batch->id)->get();

        $studentIds = $mapRows->pluck('student_id')->unique()->filter()->values();
        $studentsById = $studentIds->isEmpty() ? collect() : PortalUser::query()->whereIn('id', $studentIds)->where('role', 2)->whereNull('deleted_at')->get()->keyBy('id');

        $seen = [];
        $students = collect();
        foreach ($mapRows as $map) {
            $student = $studentsById->get($map->student_id);
            if (!$student || isset($seen[$student->id])) {
                continue;
            }
            $seen[$student->id] = true;
            $students->push($student);
        }
        $students = $students->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $allowedStudentIdSet = array_fill_keys($students->pluck('id')->map(fn($i) => (int) $i)->values()->all(), true);

        $allExams = Exam::query()->where('batch_id', $batch->id)->orderBy('exam_date')->orderBy('id')->get();

        $examsById = $allExams->keyBy('id');

        if ($request->isMethod('post')) {
            $examIdsInput = $request->input('exam_ids', []);
            if (!is_array($examIdsInput)) {
                $examIdsInput = [];
            }
            $examIdsOrdered = array_values(array_unique(array_map('intval', array_filter($examIdsInput))));
            if ($examIdsOrdered === []) {
                abort(422, 'Select at least one test to export.');
            }
            foreach ($examIdsOrdered as $eid) {
                if (!$examsById->has($eid)) {
                    abort(422, 'Invalid test selection.');
                }
            }
            $exams = collect();
            foreach ($examIdsOrdered as $eid) {
                $exams->push($examsById->get($eid));
            }

            $studentIdsInput = $request->input('student_ids', []);
            if (!is_array($studentIdsInput)) {
                $studentIdsInput = [];
            }
            $studentIdsOrdered = array_values(array_unique(array_map('intval', array_filter($studentIdsInput))));
            if ($studentIdsOrdered === []) {
                $students = collect();
            } else {
                foreach ($studentIdsOrdered as $sid) {
                    if (!isset($allowedStudentIdSet[$sid])) {
                        abort(422, 'Invalid student in export.');
                    }
                }
                $studentsKeyed = $students->keyBy('id');
                $students = collect();
                foreach ($studentIdsOrdered as $sid) {
                    $s = $studentsKeyed->get($sid);
                    if ($s) {
                        $students->push($s);
                    }
                }
            }
        } else {
            $examIdsQuery = $request->query('exam_ids', []);
            if (!is_array($examIdsQuery)) {
                $examIdsQuery = $examIdsQuery !== null && $examIdsQuery !== '' ? [$examIdsQuery] : [];
            }
            $examIdsOrderedGet = array_values(array_unique(array_map('intval', array_filter($examIdsQuery))));
            if ($examIdsOrderedGet !== []) {
                foreach ($examIdsOrderedGet as $eid) {
                    if (!$examsById->has($eid)) {
                        abort(422, 'Invalid test selection for export.');
                    }
                }
                $exams = collect();
                foreach ($examIdsOrderedGet as $eid) {
                    $exams->push($examsById->get($eid));
                }
            } else {
                $exams = $allExams;
            }
        }

        $marksByExamStudent = [];
        if (Schema::hasTable('marks') && $exams->isNotEmpty()) {
            $examIdsForMarks = $exams->pluck('id')->unique()->values();
            foreach (Mark::query()->whereIn('exam_id', $examIdsForMarks)->get() as $markRow) {
                $marksByExamStudent[(int) $markRow->exam_id][(int) $markRow->student_id] = $markRow->marks;
            }
        }

        $markAbsencesByExamStudent = self::markAbsenceModeMapForExamIds($exams->isNotEmpty() ? $exams->pluck('id')->unique()->values() : collect());

        $slugClass = Str::slug($classroom->name, '-') ?: 'classroom';
        $slugBatch = Str::slug($batch->name, '-') ?: 'batch';
        $filename = $slugClass . '-' . $slugBatch . '-marks-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(
            function () use ($classroom, $batch, $students, $exams, $marksByExamStudent, $markAbsencesByExamStudent) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");

                $fixedCols = 4;
                $examCount = $exams->count();

                $metaTitle = array_pad([], $fixedCols, '');
                $metaMax = array_pad([], $fixedCols, '');
                $totalMaxMarks = 0;
                foreach ($exams as $exam) {
                    $dateStr = optional($exam->exam_date)->format('j F Y') ?? '';
                    $titlePart = trim($exam->exam_name . ($dateStr !== '' ? ' - ' . $dateStr : ''));

                    $examMax = is_numeric($exam->max_marks) ? $exam->max_marks : 0;

                    $metaTitle[] = $titlePart;
                    $metaMax[] = 'maximum marks: ' . $examMax;

                    $totalMaxMarks += $examMax;
                }

                // ✅ ADD EMPTY CELL FOR TOTAL COLUMN IN TITLE ROW
                $metaTitle[] = '';

                // ✅ ADD TOTAL MAX IN SAME LINE
                $metaMax[] = 'Total Max Marks: ' . $totalMaxMarks;

                fputcsv($out, $metaTitle);
                fputcsv($out, $metaMax);

                $header = ['#', 'Student name', 'Classroom', 'Batch'];

                foreach ($exams as $_exam) {
                    $header[] = 'marks';
                }

                // ✅ ADD TOTAL COLUMN
                $header[] = 'Total';

                fputcsv($out, $header);

                $rowNum = 0;

                foreach ($students as $student) {
                    $rowNum++;

                    $row = [$rowNum, $student->name, $classroom->name, $batch->name];

                    $totalMarks = 0; // ✅ initialize total

                    foreach ($exams as $exam) {
                        $eid = (int) $exam->id;
                        $sid = (int) $student->id;

                        $marksForExam = $marksByExamStudent[$eid] ?? [];
                        $absForExam = $markAbsencesByExamStudent[$eid] ?? [];

                        if (array_key_exists($sid, $absForExam)) {
                            $row[] = 'A';
                            // ❌ skip from total
                        } elseif (array_key_exists($sid, $marksForExam)) {
                            $mark = $marksForExam[$sid];
                            $row[] = $mark;

                            // ✅ add only numeric marks
                            if (is_numeric($mark)) {
                                $totalMarks += $mark;
                            }
                        } else {
                            $row[] = '';
                        }
                    }

                    // ✅ ADD TOTAL IN LAST COLUMN
                    $row[] = $totalMarks > 0 ? $totalMarks : '';

                    fputcsv($out, $row);
                }

                fclose($out);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ],
        );
    }

    public function importMarksFromCsv(Request $request, $id)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        if (!Schema::hasTable('marks')) {
            return redirect()->back()->with('import_marks_error', 'Marks table is not available.');
        }

        $validated = $request->validate([
            'batch_id' => 'required|integer',
            'csv_file' => 'required|file|max:5120',
            'exam_ids' => 'required|array|min:1',
            'exam_ids.*' => 'integer',
        ]);

        $classroom = Classroom::query()->find($id);

        if (!$classroom || (int) $classroom->teacher_id !== $teacherId) {
            return redirect()->back()->with('import_marks_error', 'Classroom not found.');
        }

        $batch = Batch::query()->where('classroom_id', $classroom->id)->where('teacher_id', $teacherId)->whereKey((int) $validated['batch_id'])->first();

        if (!$batch) {
            return redirect()->back()->with('import_marks_error', 'Batch not found.');
        }

        $file = $request->file('csv_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('import_marks_error', 'Invalid upload file.');
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return redirect()->back()->with('import_marks_error', 'File must be a .csv or .txt file.');
        }

        $allowedStudentIdSet = array_fill_keys(StudentClassroomMap::query()->where('teacher_id', $teacherId)->where('classroom_id', $classroom->id)->where('batch_id', $batch->id)->pluck('student_id')->map(fn($sid) => (int) $sid)->unique()->values()->all(), true);

        $allExamsForBatch = Exam::query()->where('batch_id', $batch->id)->orderBy('exam_date')->orderBy('id')->get()->keyBy('id');

        $examIdsOrdered = array_values(array_unique(array_map('intval', array_filter($validated['exam_ids']))));
        if ($examIdsOrdered === []) {
            return redirect()->back()->with('import_marks_error', 'Select at least one test to import marks for.');
        }
        foreach ($examIdsOrdered as $eid) {
            if (!$allExamsForBatch->has($eid)) {
                return redirect()->back()->with('import_marks_error', 'One or more selected tests are not in this batch.');
            }
        }
        $exams = collect();
        foreach ($examIdsOrdered as $eid) {
            $exams->push($allExamsForBatch->get($eid));
        }

        $path = $file->getRealPath();
        if (!$path || !is_readable($path)) {
            return redirect()->back()->with('import_marks_error', 'Could not read the uploaded file.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return redirect()->back()->with('import_marks_error', 'Could not open the uploaded file.');
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $firstLine = fgetcsv($handle);
        if ($firstLine === false || $firstLine === [null] || $firstLine === []) {
            fclose($handle);

            return redirect()->back()->with('import_marks_error', 'The CSV file is empty.');
        }

        $maybeCompactExport = trim((string) ($firstLine[0] ?? '')) === '' && trim((string) ($firstLine[1] ?? '')) === '' && trim((string) ($firstLine[2] ?? '')) === '' && trim((string) ($firstLine[3] ?? '')) === '';

        $compactTemplate = false;
        $legacyTemplate = false;
        if ($maybeCompactExport) {
            fgetcsv($handle);
            $headerRow = fgetcsv($handle);
            if ($headerRow === false || $headerRow === [null] || $headerRow === []) {
                fclose($handle);

                return redirect()->back()->with('import_marks_error', 'The CSV file is missing the header row.');
            }
            $compactTemplate = true;
            $firstMarkColIndex = 4;
            $lineNum = 3;
        } else {
            $headerRow = $firstLine;
            $header1 = strtolower(trim((string) ($headerRow[1] ?? '')));
            $legacyTemplate = in_array($header1, ['student name', 'name'], true);
            $firstMarkColIndex = $legacyTemplate ? 6 : 7;
            $lineNum = 1;
        }

        $expectedCols = $firstMarkColIndex + $exams->count();

        if (count($headerRow) < $firstMarkColIndex) {
            fclose($handle);

            return redirect()
                ->back()
                ->with('import_marks_error', 'This file does not match the template (expected at least ' . $firstMarkColIndex . ' fixed columns before marks). Download the template again.');
        }

        if (count($headerRow) > $expectedCols) {
            $headerRow = array_slice($headerRow, 0, $expectedCols);
        }

        $headerRow = array_pad($headerRow, $expectedCols, '');

        $studentsByEmail = [];
        $studentsByLowerName = [];
        if (!$compactTemplate && $legacyTemplate) {
            $legacyIds = array_keys($allowedStudentIdSet);
            foreach (
                PortalUser::query()
                    ->whereIn('id', $legacyIds)
                    ->where('role', 2)
                    ->whereNull('deleted_at')
                    ->get(['id', 'email', 'name'])
                as $pu
            ) {
                $em = strtolower(trim((string) ($pu->email ?? '')));
                if ($em !== '') {
                    $studentsByEmail[$em] = (int) $pu->id;
                }
            }
        }
        if ($compactTemplate) {
            $allowedIds = array_keys($allowedStudentIdSet);
            foreach (
                PortalUser::query()
                    ->whereIn('id', $allowedIds)
                    ->where('role', 2)
                    ->whereNull('deleted_at')
                    ->get(['id', 'name'])
                as $pu
            ) {
                $nk = strtolower(trim((string) ($pu->name ?? '')));
                if ($nk === '') {
                    continue;
                }
                if (!isset($studentsByLowerName[$nk])) {
                    $studentsByLowerName[$nk] = [];
                }
                $studentsByLowerName[$nk][] = (int) $pu->id;
            }
        }

        $importedRows = 0;
        $absentSettingForImport = $this->absentDisplaySettingForBatchTeacher($batch);
        $marksNotifyQueue = [];
        $marksStateCache = [];

        try {
            DB::transaction(function () use ($handle, $exams, $batch, $allowedStudentIdSet, $firstMarkColIndex, $expectedCols, $compactTemplate, $legacyTemplate, $studentsByEmail, $studentsByLowerName, $absentSettingForImport, &$importedRows, &$lineNum, &$marksNotifyQueue, &$marksStateCache) {
                while (($row = fgetcsv($handle)) !== false) {
                    $lineNum++;
                    if ($row === [null] || $row === []) {
                        continue;
                    }
                    if (count($row) > $expectedCols) {
                        $row = array_slice($row, 0, $expectedCols);
                    }
                    $row = array_pad($row, $expectedCols, '');
                    if (count($row) < $firstMarkColIndex + 1) {
                        continue;
                    }

                    if ($compactTemplate) {
                        $studentName = trim((string) ($row[1] ?? ''));
                        if ($studentName === '') {
                            continue;
                        }
                        $nameKey = strtolower($studentName);
                        $matches = $studentsByLowerName[$nameKey] ?? [];
                        if ($matches === []) {
                            throw new \RuntimeException('Row ' . $lineNum . ': no student named "' . $studentName . '" in this batch.');
                        }
                        if (count($matches) > 1) {
                            throw new \RuntimeException('Row ' . $lineNum . ': more than one student named "' . $studentName . '" in this batch; names must be unique for this import format.');
                        }
                        $studentId = (int) $matches[0];
                    } elseif ($legacyTemplate) {
                        $studentId = (int) ($studentsByEmail[strtolower(trim((string) ($row[2] ?? '')))] ?? 0);
                        if ($studentId <= 0) {
                            continue;
                        }
                    } else {
                        $studentId = (int) trim((string) ($row[1] ?? ''));
                        if ($studentId <= 0) {
                            continue;
                        }
                    }
                    if (!isset($allowedStudentIdSet[$studentId])) {
                        throw new \RuntimeException('Row ' . $lineNum . ': student ID ' . $studentId . ' is not in this batch.');
                    }

                    $absencesReady = Schema::hasTable('mark_absences');
                    $absencesValueCol = $absencesReady && Schema::hasColumn('mark_absences', 'value');

                    foreach ($exams as $k => $exam) {
                        $colIndex = $firstMarkColIndex + $k;
                        $raw = array_key_exists($colIndex, $row) ? trim((string) $row[$colIndex]) : '';
                        $maxMarksNumeric = is_numeric($exam->max_marks) ? (float) $exam->max_marks : null;
                        if ($raw !== '' && $maxMarksNumeric !== null && is_numeric($raw) && (float) $raw > $maxMarksNumeric) {
                            throw new \RuntimeException('Row ' . $lineNum . ': marks for "' . $exam->exam_name . '" cannot exceed ' . $exam->max_marks . '.');
                        }

                        $stateCacheKey = (int) $exam->id . ':' . $studentId;
                        if (!array_key_exists($stateCacheKey, $marksStateCache)) {
                            $marksStateCache[$stateCacheKey] = $this->readMarksNotificationStateForExamStudent((int) $exam->id, $studentId);
                        }
                        $oldState = $marksStateCache[$stateCacheKey];

                        if ($raw === '') {
                            Mark::query()->where('exam_id', $exam->id)->where('student_id', $studentId)->delete();
                            if ($absencesReady) {
                                MarkAbsence::query()->where('exam_id', $exam->id)->where('student_id', $studentId)->delete();
                            }
                            $marksStateCache[$stateCacheKey] = '';
                        } elseif (self::isAbsentMarkInput($raw)) {
                            $newState = 'absent';
                            Mark::query()->updateOrCreate(
                                [
                                    'exam_id' => $exam->id,
                                    'student_id' => $studentId,
                                ],
                                [
                                    'marks' => '0',
                                    'batch_id' => $batch->id,
                                ],
                            );
                            if ($absencesReady) {
                                MarkAbsence::query()->updateOrCreate(
                                    [
                                        'exam_id' => $exam->id,
                                        'student_id' => $studentId,
                                    ],
                                    $absencesValueCol ? ['value' => (string) $absentSettingForImport] : [],
                                );
                            }
                            if ($this->shouldQueueMarksNotification($oldState, $newState)) {
                                $marksNotifyQueue[] = [
                                    'student_id' => $studentId,
                                    'exam_name' => (string) $exam->exam_name,
                                    'marks_label' => 'Absent',
                                    'is_new_entry' => $oldState === '',
                                    'classroom_id' => (int) $batch->classroom_id,
                                    'batch_id' => (int) $batch->id,
                                    'exam_id' => (int) $exam->id,
                                ];
                            }
                            $marksStateCache[$stateCacheKey] = $newState;
                        } else {
                            if ($absencesReady) {
                                MarkAbsence::query()->where('exam_id', $exam->id)->where('student_id', $studentId)->delete();
                            }
                            $newState = 'n:' . $raw;
                            Mark::query()->updateOrCreate(
                                [
                                    'exam_id' => $exam->id,
                                    'student_id' => $studentId,
                                ],
                                [
                                    'marks' => $raw,
                                    'batch_id' => $batch->id,
                                ],
                            );
                            if ($this->shouldQueueMarksNotification($oldState, $newState)) {
                                $marksNotifyQueue[] = [
                                    'student_id' => $studentId,
                                    'exam_name' => (string) $exam->exam_name,
                                    'marks_label' => $raw,
                                    'is_new_entry' => $oldState === '',
                                    'classroom_id' => (int) $batch->classroom_id,
                                    'batch_id' => (int) $batch->id,
                                    'exam_id' => (int) $exam->id,
                                ];
                            }
                            $marksStateCache[$stateCacheKey] = $newState;
                        }
                    }

                    $importedRows++;
                }
            });
        } catch (\RuntimeException $e) {
            fclose($handle);

            return redirect()->back()->with('import_marks_error', $e->getMessage());
        }

        fclose($handle);

        $teacherName = (string) (optional(PortalUser::find($teacherId))->name ?? '');
        $this->deliverMarksUpdatedNotifications($marksNotifyQueue, $teacherName !== '' ? $teacherName : null);

        return redirect()
            ->back()
            ->with('import_marks_success', 'Import finished. ' . $importedRows . ' student row(s) processed for batch: ' . $batch->name . '.');
    }

    public function saveExam(Request $request)
    {
        [$teacherId, $role, $redirect] = $this->requireTeacherOrStudent();
        if ($redirect) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'batch_id' => 'required|integer',
            'exam_name' => 'required|string|max:255',
            'max_marks' => 'required|string|max:50',
            'exam_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error_array' => $validator->errors()->toArray()]);
        }

        $batch = Batch::query()->where('teacher_id', $teacherId)->whereKey((int) $request->batch_id)->first();

        if (!$batch) {
            return response()->json(['status' => 0, 'error' => 'Batch not found']);
        }

        Exam::query()->create([
            'batch_id' => $batch->id,
            'exam_name' => $request->exam_name,
            'max_marks' => $request->max_marks,
            'exam_date' => $request->exam_date,
        ]);

        return response()->json([
            'status' => 1,
            'msg' => 'Test saved',
            'redirect_url' => url('user/teacher/classrooms/details/' . $batch->classroom_id),
        ]);
    }

    public function saveExamMarksColumn(Request $request)
    {
        [$teacherId, $role, $redirect] = $this->requireTeacherOrStudent();
        // dd($teacherId, $redirect);
        if ($redirect) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }

        if (!Schema::hasTable('marks')) {
            return response()->json(['status' => 0, 'error' => 'Marks table is not available']);
        }

        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|integer',
            'batch_id' => 'required|integer',
            'marks' => 'required|array',
            'marks.*' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error_array' => $validator->errors()->toArray()]);
        }

        $batch = Batch::query()->where('teacher_id', $teacherId)->whereKey((int) $request->batch_id)->first();

        if (!$batch) {
            return response()->json(['status' => 0, 'error' => 'Batch not found']);
        }

        $exam = Exam::query()->whereKey((int) $request->exam_id)->where('batch_id', $batch->id)->first();

        if (!$exam) {
            return response()->json(['status' => 0, 'error' => 'Test not found']);
        }

        $allowedStudentIds = StudentClassroomMap::query()->where('teacher_id', $teacherId)->where('batch_id', $batch->id)->pluck('student_id')->unique()->filter()->map(fn($id) => (int) $id)->all();

        $allowedSet = array_fill_keys($allowedStudentIds, true);

        $maxMarksNumeric = is_numeric($exam->max_marks) ? (float) $exam->max_marks : null;
        $absencesReady = Schema::hasTable('mark_absences');
        $absencesValueCol = $absencesReady && Schema::hasColumn('mark_absences', 'value');
        $absentSetting = $this->absentDisplaySettingForBatchTeacher($batch);

        $marksInput = $request->input('marks', []);
        $marksNotifyQueue = [];
        $teacherName = (string) (optional(PortalUser::find($teacherId))->name ?? '');

        $studentIdsInPayload = [];
        foreach (array_keys($marksInput) as $sid) {
            $studentIdsInPayload[] = (int) $sid;
        }
        $previousStatesByStudent = $this->readMarksNotificationStatesForExamStudents((int) $exam->id, $studentIdsInPayload);

        foreach ($marksInput as $studentId => $raw) {
            $studentId = (int) $studentId;
            if (!isset($allowedSet[$studentId])) {
                return response()->json(['status' => 0, 'error' => 'Invalid student for this batch']);
            }

            $value = $raw === null ? '' : trim((string) $raw);
            $oldState = $previousStatesByStudent[$studentId] ?? '';

            if ($value === '') {
                Mark::query()->where('exam_id', $exam->id)->where('student_id', $studentId)->delete();
                if ($absencesReady) {
                    MarkAbsence::query()->where('exam_id', $exam->id)->where('student_id', $studentId)->delete();
                }
                $previousStatesByStudent[$studentId] = '';

                continue;
            }

            if (self::isAbsentMarkInput($value)) {
                $newState = 'absent';
                Mark::query()->updateOrCreate(
                    [
                        'exam_id' => $exam->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'marks' => '0',
                        'batch_id' => $batch->id,
                    ],
                );
                if ($absencesReady) {
                    MarkAbsence::query()->updateOrCreate(
                        [
                            'exam_id' => $exam->id,
                            'student_id' => $studentId,
                        ],
                        $absencesValueCol ? ['value' => (string) $absentSetting] : [],
                    );
                }
                if ($this->shouldQueueMarksNotification($oldState, $newState)) {
                    $marksNotifyQueue[] = [
                        'student_id' => $studentId,
                        'exam_name' => (string) $exam->exam_name,
                        'marks_label' => 'Absent',
                        'is_new_entry' => $oldState === '',
                        'classroom_id' => (int) $batch->classroom_id,
                        'batch_id' => (int) $batch->id,
                        'exam_id' => (int) $exam->id,
                    ];
                }
                $previousStatesByStudent[$studentId] = $newState;

                continue;
            }

            if ($absencesReady) {
                MarkAbsence::query()->where('exam_id', $exam->id)->where('student_id', $studentId)->delete();
            }

            if ($maxMarksNumeric !== null && is_numeric($value) && (float) $value > $maxMarksNumeric) {
                return response()->json([
                    'status' => 0,
                    'error' => 'Marks cannot exceed maximum (' . $exam->max_marks . ') for ' . $exam->exam_name,
                ]);
            }

            $newState = 'n:' . $value;
            Mark::query()->updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'student_id' => $studentId,
                ],
                [
                    'marks' => $value,
                    'batch_id' => $batch->id,
                ],
            );
            if ($this->shouldQueueMarksNotification($oldState, $newState)) {
                $marksNotifyQueue[] = [
                    'student_id' => $studentId,
                    'exam_name' => (string) $exam->exam_name,
                    'marks_label' => $value,
                    'is_new_entry' => $oldState === '',
                    'classroom_id' => (int) $batch->classroom_id,
                    'batch_id' => (int) $batch->id,
                    'exam_id' => (int) $exam->id,
                ];
            }
            $previousStatesByStudent[$studentId] = $newState;
        }

        $this->deliverMarksUpdatedNotifications($marksNotifyQueue, $teacherName !== '' ? $teacherName : null);

        return response()->json(['status' => 1, 'msg' => 'Marks saved']);
    }

    /**
     * Stored display mode per absence row (null = legacy row, use current teacher setting on screen).
     *
     * @return array<int, array<int, int|null>>
     */
    protected static function markAbsenceModeMapForExamIds(\Illuminate\Support\Collection $examIds): array
    {
        if (!Schema::hasTable('mark_absences') || $examIds->isEmpty()) {
            return [];
        }

        $ids = $examIds->unique()->values();
        $valueCol = Schema::hasColumn('mark_absences', 'value');
        $map = [];
        foreach (MarkAbsence::query()->whereIn('exam_id', $ids)->get() as $absRow) {
            $eid = (int) $absRow->exam_id;
            $sid = (int) $absRow->student_id;
            $stored = null;
            if ($valueCol && $absRow->value !== null && $absRow->value !== '') {
                $stored = self::normalizeAbsentDisplaySetting((int) $absRow->value);
            }
            $map[$eid][$sid] = $stored;
        }

        return $map;
    }

    protected static function normalizeAbsentDisplaySetting(int $raw): int
    {
        return $raw === TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE ? TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE : TeacherSetting::COUNT_AS_ZERO;
    }

    protected function absentDisplaySettingForBatchTeacher(Batch $batch): int
    {
        $batch->loadMissing('classroom');
        $tid = (int) (optional($batch->classroom)->teacher_id ?? ($batch->teacher_id ?? 0));
        $row = $tid > 0 ? TeacherSetting::query()->where('teacher_id', $tid)->first() : null;

        return self::normalizeAbsentDisplaySetting((int) ($row?->count_setting ?? TeacherSetting::COUNT_AS_ZERO));
    }

    protected static function isAbsentMarkInput(string $value): bool
    {
        $v = strtoupper(trim($value));

        return $v === 'A' || $v === 'ABSENT';
    }

    /**
     * Set parent_name, parent_email, parent_phone on each student from parent_id or parent_student_map.
     *
     * @param  \Illuminate\Support\Collection<int, PortalUser>  $students
     */
    protected function hydrateParentFieldsForStudentCollection(\Illuminate\Support\Collection $students): void
    {
        if ($students->isEmpty()) {
            return;
        }

        $parentIds = $students->pluck('parent_id')->map(fn($id) => (int) $id)->filter()->unique()->values();
        $parentsById = $parentIds->isEmpty() ? collect() : PortalUser::query()->whereIn('id', $parentIds)->where('role', 3)->whereNull('deleted_at')->get()->keyBy('id');

        $studentIds = $students->pluck('id');
        $mapFirstParent = collect();
        if (Schema::hasTable('parent_student_map')) {
            $mapRowsParent = DB::table('parent_student_map as psm')
                ->join('portal_user as pu', 'pu.id', '=', 'psm.parent_id')
                ->whereIn('psm.student_id', $studentIds)
                ->where('pu.role', 3)
                ->whereNull('pu.deleted_at')
                ->orderBy('psm.id')
                ->select(['psm.student_id', 'pu.name as parent_name', 'pu.email as parent_email', 'pu.phone as parent_phone'])
                ->get();
            $mapFirstParent = $mapRowsParent->unique('student_id')->keyBy('student_id');
        }

        foreach ($students as $student) {
            $pid = (int) ($student->parent_id ?? 0);
            if ($pid > 0 && $parentsById->has($pid)) {
                $p = $parentsById->get($pid);
                $student->setAttribute('parent_name', $p->name);
                $student->setAttribute('parent_email', $p->email);
                $student->setAttribute('parent_phone', $p->phone);
            } elseif ($mapFirstParent->has($student->id)) {
                $row = $mapFirstParent->get($student->id);
                $student->setAttribute('parent_name', $row->parent_name);
                $student->setAttribute('parent_email', $row->parent_email);
                $student->setAttribute('parent_phone', $row->parent_phone);
            } else {
                $student->setAttribute('parent_name', null);
                $student->setAttribute('parent_email', null);
                $student->setAttribute('parent_phone', null);
            }
        }
    }

    /**
     * Canonical state for comparing before/after marks (exam + student).
     * '' = no mark, 'absent' = absence row, 'n:VALUE' = numeric/text mark stored.
     */
    protected function readMarksNotificationStateForExamStudent(int $examId, int $studentId): string
    {
        $mark = Mark::query()
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->first(['marks']);
        if (!$mark) {
            return '';
        }
        if (Schema::hasTable('mark_absences')) {
            $hasAbsence = MarkAbsence::query()->where('exam_id', $examId)->where('student_id', $studentId)->exists();
            if ($hasAbsence) {
                return 'absent';
            }
        }

        return 'n:' . trim((string) ($mark->marks ?? ''));
    }

    /**
     * @param  array<int, int>  $studentIds
     * @return array<int, string>
     */
    protected function readMarksNotificationStatesForExamStudents(int $examId, array $studentIds): array
    {
        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));
        if ($studentIds === []) {
            return [];
        }
        $marks = Mark::query()
            ->where('exam_id', $examId)
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'marks']);
        $byStudent = $marks->keyBy('student_id');
        $absentStudentIds = [];
        if (Schema::hasTable('mark_absences')) {
            foreach (MarkAbsence::query()->where('exam_id', $examId)->whereIn('student_id', $studentIds)->pluck('student_id') as $sid) {
                $absentStudentIds[(int) $sid] = true;
            }
        }
        $out = [];
        foreach ($studentIds as $sid) {
            if (!$byStudent->has($sid)) {
                $out[$sid] = '';

                continue;
            }
            if (!empty($absentStudentIds[$sid])) {
                $out[$sid] = 'absent';
            } else {
                $out[$sid] = 'n:' . trim((string) ($byStudent->get($sid)->marks ?? ''));
            }
        }

        return $out;
    }

    protected function shouldQueueMarksNotification(string $oldState, string $newState): bool
    {
        if ($newState === '') {
            return false;
        }

        return $oldState !== $newState;
    }

    /**
     * @return array<int, int>
     */
    protected function parentPortalUserIdsForStudent(int $studentId): array
    {
        $ids = [];
        $student = PortalUser::query()
            ->whereKey($studentId)
            ->where('role', 2)
            ->whereNull('deleted_at')
            ->first(['id', 'parent_id']);
        if (!$student) {
            return [];
        }
        $legacyParentId = (int) ($student->parent_id ?? 0);
        if ($legacyParentId > 0) {
            $ids[] = $legacyParentId;
        }
        if (Schema::hasTable('parent_student_map')) {
            foreach (DB::table('parent_student_map')->where('student_id', $studentId)->pluck('parent_id') as $pid) {
                $pid = (int) $pid;
                if ($pid > 0) {
                    $ids[] = $pid;
                }
            }
        }
        $ids = array_values(array_unique($ids));
        $valid = [];
        foreach ($ids as $pid) {
            if (PortalUser::query()->whereKey($pid)->where('role', 3)->whereNull('deleted_at')->exists()) {
                $valid[] = $pid;
            }
        }

        return $valid;
    }

    protected function deliverMarksUpdatedNotifications(array $items, ?string $teacherName): void
    {
        if (!Schema::hasTable('notifications') || $items === []) {
            return;
        }

        $items = collect($items)->unique(fn($item) => (int) $item['student_id'] . ':' . (int) $item['exam_id'])->values()->all();

        foreach ($items as $item) {
            $studentId = (int) ($item['student_id'] ?? 0);
            $examName = trim((string) ($item['exam_name'] ?? ''));
            $marksLabel = trim((string) ($item['marks_label'] ?? ''));
            $isNewEntry = (bool) ($item['is_new_entry'] ?? false);

            $cid = (int) ($item['classroom_id'] ?? 0) ?: null;
            $bid = (int) ($item['batch_id'] ?? 0) ?: null;
            $eid = (int) ($item['exam_id'] ?? 0) ?: null;

            if ($studentId <= 0 || !$eid) {
                continue;
            }

            $student = PortalUser::whereKey($studentId)->where('role', 2)->whereNull('deleted_at')->first();

            if (!$student) {
                continue;
            }

            $tn = $teacherName ?: 'Your teacher';

            // =========================
            // ✅ STUDENT NOTIFICATION
            // =========================
            $title = $isNewEntry ? 'New marks posted' : 'Marks updated';

            $message = $tn . ' ' . ($isNewEntry ? 'posted' : 'updated') . ' your marks for "' . $examName . '": ' . $marksLabel . '.';

            $student->notify(
                new PortalNotification(
                    $title,
                    $message,
                    'marks', // 👈 type
                    [
                        'exam_id' => $eid,
                        'classroom_id' => $cid,
                        'batch_id' => $bid,
                    ],
                ),
            );

            // =========================
            // ✅ PARENT NOTIFICATIONS
            // =========================
            $parentIds = $this->parentPortalUserIdsForStudent($studentId);

            foreach ($parentIds as $parentId) {
                $parent = PortalUser::whereKey($parentId)->where('role', 3)->whereNull('deleted_at')->first();

                if (!$parent) {
                    continue;
                }

                $childName = $student->name ?? 'Your child';

                $title = $isNewEntry ? 'New marks for your child' : 'Marks updated for your child';

                $message = $tn . ' ' . ($isNewEntry ? 'posted' : 'updated') . ' ' . $childName . '\'s marks for "' . $examName . '": ' . $marksLabel . '.';

                $parent->notify(
                    new PortalNotification($title, $message, 'marks', [
                        'student_id' => $studentId,
                        'exam_id' => $eid,
                        'classroom_id' => $cid,
                        'batch_id' => $bid,
                    ]),
                );
            }
        }
    }

    public function events()
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $data = [];
        $data['title'] = 'Events';
        $data['active_tab'] = 'events';
        $data['classrooms'] = collect();
        $data['batches'] = collect();
        $data['event_types'] = collect();

        if (Schema::hasTable('classrooms')) {
            $data['classrooms'] = Classroom::query()
                ->where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name']);
        }
        if (Schema::hasTable('batches')) {
            $data['batches'] = Batch::query()
                ->where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name', 'classroom_id']);
        }
        if (Schema::hasTable('event_types')) {
            $data['event_types'] = EventType::query()->orderBy('title')->get(['id', 'title', 'color_code']);
        }

        $data['calendar_events'] = [];
        if (Schema::hasTable('events')) {
            $calendarQuery = Event::query()->orderBy('event_date')->orderBy('event_time');

            if (Schema::hasColumn('events', 'teacher_id')) {
                $calendarQuery->where(function ($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId)
                        ->orWhere(function ($legacy) use ($teacherId) {
                            $legacy->whereNull('teacher_id')
                                ->where(function ($scope) use ($teacherId) {
                                    $scope->whereHas('classroom', function ($c) use ($teacherId) {
                                        $c->where('teacher_id', $teacherId);
                                    })->orWhereHas('batch', function ($b) use ($teacherId) {
                                        $b->where('teacher_id', $teacherId);
                                    });
                                });
                        });
                });
            } else {
                $calendarQuery->where(function ($scope) use ($teacherId) {
                    $scope->whereHas('classroom', function ($c) use ($teacherId) {
                        $c->where('teacher_id', $teacherId);
                    })->orWhereHas('batch', function ($b) use ($teacherId) {
                        $b->where('teacher_id', $teacherId);
                    });
                });
            }

            $data['calendar_events'] = $calendarQuery
                ->with('eventType')
                ->get()
                ->map(function (Event $event) {
                    $dateStr = $event->event_date instanceof \Carbon\CarbonInterface
                        ? $event->event_date->format('Y-m-d')
                        : \Carbon\Carbon::parse($event->event_date)->format('Y-m-d');
                    if ($event->event_time instanceof \Carbon\CarbonInterface) {
                        $timeStr = $event->event_time->format('H:i:s');
                    } elseif (is_string($event->event_time) && $event->event_time !== '') {
                        $timeStr = strlen($event->event_time) >= 8
                            ? substr($event->event_time, 0, 8)
                            : \Carbon\Carbon::parse($event->event_time)->format('H:i:s');
                    } else {
                        $timeStr = '00:00:00';
                    }
                    $start = \Carbon\Carbon::parse($dateStr . ' ' . $timeStr)->format('Y-m-d\TH:i:s');

                    $payload = [
                        'id' => 'db-' . $event->id,
                        'url' => '',
                        'title' => $event->title,
                        'start' => $start,
                        'allDay' => false,
                        'extendedProps' => array_merge([
                            'calendar' => 'et' . (int) $event->event_type_id,
                            'event_type_id' => $event->event_type_id,
                            'classroom_id' => $event->classroom_id,
                            'batch_id' => $event->batch_id,
                            'status' => (int) $event->status,
                            'description' => $event->description,
                            'color_code' => $event->eventType?->color_code,
                        ], Schema::hasColumn('events', 'all_classrooms') ? [
                            'all_classrooms' => (bool) $event->all_classrooms,
                        ] : [], Schema::hasColumn('events', 'all_batches') ? [
                            'all_batches' => (bool) $event->all_batches,
                        ] : []),
                    ];

                    return array_merge($payload, $this->calendarColorsForEventType($event->eventType));
                })
                ->values()
                ->all();
        }

        return view('web.user.teacher.events', $data);
    }

    public function eventTypes()
    {
        [, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $data = [
            'title' => 'Event Types',
            'active_tab' => 'event_types',
            'event_types' => collect(),
        ];
        if (Schema::hasTable('event_types')) {
            $data['event_types'] = EventType::query()->orderBy('title')->get(['id', 'title', 'color_code', 'created_at']);
        }

        return view('web.user.teacher.event_types', $data);
    }

    protected function teacherCanEditEvent(Event $event, int $teacherId): bool
    {
        if (Schema::hasColumn('events', 'teacher_id') && $event->teacher_id !== null) {
            return (int) $event->teacher_id === (int) $teacherId;
        }
        if ($event->classroom_id) {
            $classroom = Classroom::query()->whereKey($event->classroom_id)->first();
            if ($classroom && (int) $classroom->teacher_id === (int) $teacherId) {
                return true;
            }
        }
        if ($event->batch_id) {
            $batch = Batch::query()->whereKey($event->batch_id)->first();
            if ($batch && (int) $batch->teacher_id === (int) $teacherId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build FullCalendar color fields from event_types.color_code (e.g. #0000FF, 0000FF, #00F).
     *
     * @return array{backgroundColor: string, borderColor: string, textColor: string}
     */
    protected function calendarColorsForEventType(?EventType $eventType): array
    {
        $raw = $eventType && $eventType->color_code !== null
            ? trim((string) $eventType->color_code)
            : '';
        $raw = preg_replace('/\s+/', '', $raw) ?? '';

        if ($raw === '') {
            $bg = '#696cff';
        } elseif (preg_match('/^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/i', $raw)) {
            $hex = ltrim($raw, '#');
            if (strlen($hex) === 3) {
                $bg = sprintf(
                    '#%s%s%s%s%s%s',
                    $hex[0],
                    $hex[0],
                    $hex[1],
                    $hex[1],
                    $hex[2],
                    $hex[2],
                );
            } else {
                $bg = '#' . strtolower($hex);
            }
        } elseif (preg_match('/^[a-z]+$/i', $raw)) {
            $bg = strtolower($raw);
        } else {
            $bg = '#696cff';
        }

        $text = '#ffffff';
        if (preg_match('/^#([0-9a-fA-F]{6})$/', $bg)) {
            $r = hexdec(substr($bg, 1, 2));
            $g = hexdec(substr($bg, 3, 2));
            $b = hexdec(substr($bg, 5, 2));
            $lum = ($r * 0.299 + $g * 0.587 + $b * 0.114) / 255;
            $text = $lum > 0.65 ? '#212529' : '#ffffff';
        }

        return [
            'backgroundColor' => $bg,
            'borderColor' => $bg,
            'textColor' => $text,
        ];
    }

    public function saveEventType(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Unauthorized request';
            echo json_encode($this->response);

            return;
        }

        if (!Schema::hasTable('event_types')) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Event types are not available.';
            echo json_encode($this->response);

            return;
        }

        $validation = Validator::make($request->all(), [
            'id' => 'nullable|integer|exists:event_types,id',
            'title' => 'required|string|max:255',
            'color_code' => 'nullable|string|max:32',
        ]);

        if (!$validation->fails()) {
            $attrs = [
                'title' => trim((string) $request->title),
                'color_code' => $request->color_code !== null && trim((string) $request->color_code) !== ''
                    ? trim((string) $request->color_code)
                    : null,
            ];
            if ($request->filled('id')) {
                $row = EventType::query()->find((int) $request->id);
                if (!$row) {
                    $this->response['status'] = 0;
                    $this->response['error'] = 'Event type not found.';
                    echo json_encode($this->response);

                    return;
                }
                $row->fill($attrs);
                $row->save();
                $this->response['msg'] = 'Event type updated.';
            } else {
                EventType::query()->create($attrs);
                $this->response['msg'] = 'Event type saved.';
            }
            $this->response['status'] = 1;
            $this->response['redirect_url'] = url('user/teacher/event_types');
        } else {
            $this->response['status'] = 0;
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }

    public function deleteEventType(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Unauthorized request';
            echo json_encode($this->response);

            return;
        }

        if (!Schema::hasTable('event_types')) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Event types are not available.';
            echo json_encode($this->response);

            return;
        }

        $validation = Validator::make($request->all(), [
            'id' => 'required|integer|exists:event_types,id',
        ]);

        if ($validation->fails()) {
            $this->response['status'] = 0;
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
            echo json_encode($this->response);

            return;
        }

        $id = (int) $request->id;
        if (Schema::hasTable('events') && Event::query()->where('event_type_id', $id)->exists()) {
            $this->response['status'] = 0;
            $this->response['error'] = 'This type is used by one or more events. Remove or reassign those events first.';
            echo json_encode($this->response);

            return;
        }

        EventType::query()->whereKey($id)->delete();

        $this->response['status'] = 1;
        $this->response['msg'] = 'Event type deleted.';
        $this->response['redirect_url'] = url('user/teacher/event_types');
        echo json_encode($this->response);
    }

    public function saveEvent(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Unauthorized request';
            echo json_encode($this->response);

            return;
        }

        if (!Schema::hasTable('events') || !Schema::hasTable('event_types')) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Events are not available.';
            echo json_encode($this->response);

            return;
        }

        if ($request->input('classroom_id') === '' || $request->input('classroom_id') === null) {
            $request->merge(['classroom_id' => null]);
        }
        if ($request->input('batch_id') === '' || $request->input('batch_id') === null) {
            $request->merge(['batch_id' => null]);
        }

        $validation = Validator::make($request->all(), [
            'event_id' => 'nullable|integer|exists:events,id',
            'event_type_id' => 'required|integer|exists:event_types,id',
            'classroom_id' => 'nullable|integer',
            'batch_id' => 'nullable|integer',
            'all_classrooms' => 'nullable|boolean',
            'all_batches' => 'nullable|boolean',
            'title' => 'required',
            'description' => 'nullable',
            'event_date' => 'required',
            'event_time' => 'required',
            'status' => 'nullable',
        ]);

        if (!$validation->fails()) {
            $eventId = $request->filled('event_id') ? (int) $request->event_id : null;
            $hasScopeCols = Schema::hasColumn('events', 'all_classrooms')
                && Schema::hasColumn('events', 'all_batches');
            $allClassrooms = $hasScopeCols && $request->boolean('all_classrooms');
            $allBatches = $hasScopeCols && $request->boolean('all_batches');

            $classroomId = !$allClassrooms && $request->filled('classroom_id')
                ? (int) $request->classroom_id
                : null;
            $batchId = !$allBatches && $request->filled('batch_id') ? (int) $request->batch_id : null;

            if ($classroomId) {
                $classroom = Classroom::query()
                    ->where('teacher_id', $teacherId)
                    ->whereKey($classroomId)
                    ->first();
                if (!$classroom) {
                    $this->response['status'] = 0;
                    $this->response['error_array'] = formatErrors([
                        'classroom_id' => ['Classroom not found.'],
                    ]);
                    echo json_encode($this->response);

                    return;
                }
            }

            if ($batchId) {
                $batch = Batch::query()
                    ->where('teacher_id', $teacherId)
                    ->whereKey($batchId)
                    ->first();
                if (!$batch) {
                    $this->response['status'] = 0;
                    $this->response['error_array'] = formatErrors([
                        'batch_id' => ['Batch not found.'],
                    ]);
                    echo json_encode($this->response);

                    return;
                }
                if ($classroomId !== null && (int) $batch->classroom_id !== $classroomId) {
                    $this->response['status'] = 0;
                    $this->response['error_array'] = formatErrors([
                        'batch_id' => ['This batch does not belong to the selected classroom.'],
                    ]);
                    echo json_encode($this->response);

                    return;
                }
                if ($classroomId === null && !$allClassrooms) {
                    $classroomId = (int) $batch->classroom_id;
                }
            }

            $timeStr = $request->event_time;
            if (strlen($timeStr) === 5) {
                $timeStr .= ':00';
            } elseif (substr_count($timeStr, ':') === 1) {
                $timeStr .= ':00';
            }

            $eventAttrs = [
                'event_type_id' => (int) $request->event_type_id,
                'batch_id' => $batchId,
                'classroom_id' => $classroomId,
                'title' => trim((string) $request->title),
                'description' => $request->filled('description') ? trim((string) $request->description) : null,
                'event_date' => \Carbon\Carbon::parse($request->event_date)->format('Y-m-d'),
                'event_time' => $timeStr,
                'status' => (int) ($request->input('status', 0)),
            ];
            if ($hasScopeCols) {
                $eventAttrs['all_classrooms'] = $allClassrooms;
                $eventAttrs['all_batches'] = $allBatches;
            }

            if ($eventId) {
                $existing = Event::query()->whereKey($eventId)->first();
                if (!$existing || !$this->teacherCanEditEvent($existing, $teacherId)) {
                    $this->response['status'] = 0;
                    $this->response['error_array'] = formatErrors([
                        'event_id' => ['Event not found or you cannot edit it.'],
                    ]);
                    echo json_encode($this->response);

                    return;
                }
                $existing->fill($eventAttrs);
                $existing->save();

                $this->response['status'] = 1;
                $this->response['msg'] = 'Event updated.';
                $this->response['redirect_url'] = url('user/teacher/events');
            } else {
                if (Schema::hasColumn('events', 'teacher_id')) {
                    $eventAttrs['teacher_id'] = $teacherId;
                }
                Event::query()->create($eventAttrs);

                $this->response['status'] = 1;
                $this->response['msg'] = 'Event saved.';
                $this->response['redirect_url'] = url('user/teacher/events');
            }
        } else {
            $this->response['status'] = 0;
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }
}
