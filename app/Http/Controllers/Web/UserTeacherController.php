<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\ParentStudentMap;
use App\Models\PortalUser;
use App\Models\StudentClassroomMap;
use App\Models\StudentTeacherMap;
use App\Support\StudentEnrollmentSync;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserTeacherController extends Controller
{
    protected function requireTeacher()
    {
        $portal = session('portal_user');
        $userId = (int)($portal['id'] ?? 0);
        $role = (int)($portal['role'] ?? 0);
        if (!$userId || $role !== 1) {
            return [null, redirect('user/dashboard')];
        }
        return [$userId, null];
    }

    public function index()
    {
        return redirect('user/teacher/classrooms');
    }

    public function attendance(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) return $redirect;

        $data = [];
        $data['title'] = 'Attendance';
        $data['active_tab'] = 'teacher_attendance';

        // Placeholder data; integrate with actual attendance tables if available
        $data['filters'] = [
            'classrooms' => \App\Models\Classroom::where('teacher_id', $teacherId)->orderBy('name')->get(['id','name']),
            'batches' => \App\Models\Batch::where('teacher_id', $teacherId)->orderBy('name')->get(['id','name']),
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
        if ($redirect) return $redirect;

        $data = [];
        $data['title'] = 'My Classrooms';
        $data['active_tab'] = 'teacher_classrooms';
        $data['teacher_id'] = $teacherId;
        $data['classrooms'] = Classroom::where('teacher_id', $teacherId)
            ->orderBy('id', 'desc')
            ->get();
        return view('web.user.teacher.classrooms', $data);
    }

    public function batches()
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) return $redirect;

        $data = [];
        $data['title'] = 'My Batches';
        $data['active_tab'] = 'teacher_batches';
        $data['teacher_id'] = $teacherId;
        $data['batches'] = Batch::where('teacher_id', $teacherId)
            ->with('classroom')
            ->orderBy('id', 'desc')
            ->get();
        return view('web.user.teacher.batches', $data);
    }

    public function students(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) return $redirect;

        $data = [];
        $data['title'] = 'My Students';
        $data['active_tab'] = 'teacher_students';
        $data['teacher_id'] = $teacherId;
        $portal = session('portal_user', []);
        $data['teacher_name'] = ! empty($portal['name'])
            ? (string) $portal['name']
            : (string) (PortalUser::whereKey($teacherId)->value('name') ?? 'Teacher');

        $mainUrl = url('user/teacher/students');
        $url = [];

        $students = PortalUser::query()
            ->where('role', 2)
            ->whereHas('teachers', static function ($q) use ($teacherId) {
                $q->whereKey($teacherId);
            })
            ->with(['studentClassroomMaps' => static function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)->with(['classroom', 'batch']);
            }]);

        $data['search'] = $search = trim((string)($request->search ?? ''));
        if ($search !== '') {
            $students->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
            $url[] = 'search=' . urlencode($search);
        }

        $data['classroom_id'] = $classroomId = (int)($request->classroom_id ?? 0);
        if ($classroomId > 0) {
            $students->whereHas('studentClassroomMaps', static function ($q) use ($teacherId, $classroomId) {
                $q->where('teacher_id', $teacherId)->where('classroom_id', $classroomId);
            });
            $url[] = 'classroom_id=' . $classroomId;
        }

        $data['batch_id'] = $batchId = (int)($request->batch_id ?? 0);
        if ($batchId > 0) {
            $students->whereHas('studentClassroomMaps', static function ($q) use ($teacherId, $batchId) {
                $q->where('teacher_id', $teacherId)->where('batch_id', $batchId);
            });
            $url[] = 'batch_id=' . $batchId;
        }

        $data['page'] = $page = (int)($request->page ?? 1);
        if ($page < 1) $page = 1;
        $data['per_page'] = $perPage = (int)($request->per_page ?? 50);
        if ($perPage < 1) $perPage = 50;

        $data['url'] = $mainUrl . (count($url) ? ('?' . implode('&', $url)) : '');
        $data['num_rows'] = (clone $students)->count('portal_user.id');
        $data['students'] = $students->orderBy('id', 'desc')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $data['classrooms'] = Classroom::where('teacher_id', $teacherId)->orderBy('name')->get();
        $data['batches'] = Batch::where('teacher_id', $teacherId)->orderBy('name')->get();

        return view('web.user.teacher.students', $data);
    }

    // CRUD: Classrooms
    public function saveClassroom(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'id' => 'nullable'
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
        if ($redirect) return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);

        $id = $request->id;
        if (!$id) return response()->json(['status' => 0, 'error' => 'Invalid request']);
        $classroom = Classroom::where('teacher_id', $teacherId)->find($id);
        if (!$classroom) return response()->json(['status' => 0, 'error' => 'Classroom not found']);
        $classroom->delete();
        return response()->json(['status' => 1, 'msg' => 'Deleted', 'redirect_url' => url('user/teacher/classrooms')]);
    }

    // CRUD: Batches
    public function saveBatch(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id',
            'status' => 'required|in:active,pending,inactive',
            'id' => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error_array' => $validator->errors()->toArray()]);
        }
        $classroom = Classroom::where('teacher_id', $teacherId)->find($request->classroom_id);
        if (!$classroom) return response()->json(['status' => 0, 'error' => 'Invalid classroom']);
        $batch = $request->id ? Batch::where('teacher_id', $teacherId)->find($request->id) : new Batch();
        if (!$batch) return response()->json(['status' => 0, 'error' => 'Batch not found']);
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
        if ($redirect) return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);

        $id = $request->id;
        if (!$id) return response()->json(['status' => 0, 'error' => 'Invalid request']);
        $batch = Batch::where('teacher_id', $teacherId)->find($id);
        if (!$batch) return response()->json(['status' => 0, 'error' => 'Batch not found']);
        $batch->delete();
        return response()->json(['status' => 1, 'msg' => 'Deleted', 'redirect_url' => url('user/teacher/batches')]);
    }

    // CRUD: Students (profile/parent only; classrooms & batches via syncStudentEnrollments — same as admin)
    public function saveStudent(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);

        $idInput = (string)($request->id ?? '');
        $decodedId = base64_decode($idInput, true);
        $id = (is_string($decodedId) && ctype_digit($decodedId)) ? (int)$decodedId : (int)$idInput;

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
            $owned = PortalUser::where('role', 2)
                ->whereHas('teachers', static fn ($q) => $q->whereKey($teacherId))
                ->whereKey($id)
                ->exists();
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
                if ($studentByEmail && $studentByPhone && (int)$studentByEmail->id !== (int)$studentByPhone->id) {
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

            $parentName = trim((string)$request->parent_name);
            $parentEmail = trim((string)$request->parent_email);
            $parentPhone = trim((string)$request->parent_phone);
            if ($parentName !== '' || $parentEmail !== '' || $parentPhone !== '') {
                $parent = null;
                if ($parentEmail !== '') {
                    $match = PortalUser::where('email', $parentEmail)->first();
                    if ($match && (int)($match->role ?? 0) !== 3) {
                        DB::rollBack();

                        return response()->json(['status' => 0, 'error' => 'Parent email already used by another user type.']);
                    }
                    if ($match) {
                        $parent = $match;
                    }
                }
                if (!$parent && $parentPhone !== '') {
                    $match = PortalUser::where('phone', $parentPhone)->first();
                    if ($match && (int)($match->role ?? 0) !== 3) {
                        DB::rollBack();

                        return response()->json(['status' => 0, 'error' => 'Parent phone already used by another user type.']);
                    }
                    if ($match) {
                        $parent = $match;
                    }
                }
                if (!$parent && !empty($student->parent_id)) {
                    $parent = PortalUser::where('role', 3)->find((int)$student->parent_id);
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
                    if ($parentEmail !== '' && $parentEmail !== (string)$parent->email) {
                        if (PortalUser::where('email', $parentEmail)->where('id', '!=', $parent->id)->exists()) {
                            DB::rollBack();

                            return response()->json(['status' => 0, 'error' => 'Parent email already exists.']);
                        }
                    }
                    if ($parentPhone !== '' && $parentPhone !== (string)$parent->phone) {
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
                    $parent->name = 'Parent of '.$student->name;
                }
                $parent->save();

                ParentStudentMap::firstOrCreate([
                    'parent_id' => $parent->id,
                    'student_id' => $student->id,
                ]);
                if ((int)($student->parent_id ?? 0) !== (int)$parent->id) {
                    $student->parent_id = $parent->id;
                    $student->save();
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['status' => 0, 'error' => 'Unable to save student. '.$e->getMessage()]);
        }

        return response()->json(['status' => 1, 'msg' => 'Saved', 'redirect_url' => url('user/teacher/students')]);
    }

    public function syncStudentEnrollments(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);

        $studentId = $request->student_id ? (int) base64_decode((string) $request->student_id) : 0;
        if ($studentId < 1) {
            return response()->json(['status' => 0, 'error' => 'Invalid student.']);
        }

        $student = PortalUser::where('role', 2)->find($studentId);
        if (!$student || !$student->teachers()->whereKey($teacherId)->exists()) {
            return response()->json(['status' => 0, 'error' => 'Student not found.']);
        }

        $pairs = StudentEnrollmentSync::pairsFromRequestArrays(
            (array) $request->input('map_classroom_id', []),
            (array) $request->input('map_batch_id', [])
        );

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
            return response()->json(['status' => 0, 'error' => 'Unable to save enrollments. '.$e->getMessage()]);
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
        if ($redirect) return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);

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
        if ($redirect) return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);

        $idInput = (string)($request->id ?? '');
        $decodedId = base64_decode($idInput, true);
        $id = (is_string($decodedId) && ctype_digit($decodedId)) ? (int)$decodedId : (int)$idInput;
        if (!$id) return response()->json(['status' => 0, 'error' => 'Invalid request']);
        $student = PortalUser::where('role', 2)->find($id);
        if (!$student) return response()->json(['status' => 0, 'error' => 'Student not found']);
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
            : Classroom::where('teacher_id', $teacherId)->whereRaw('LOWER(name) = ?', [strtolower($classroomRaw)])->first();
        $batch = ctype_digit($batchRaw)
            ? Batch::where('teacher_id', $teacherId)->find((int) $batchRaw)
            : Batch::where('teacher_id', $teacherId)->whereRaw('LOWER(name) = ?', [strtolower($batchRaw)])->first();

        if (! $classroom && $batch) {
            $classroom = Classroom::where('teacher_id', $teacherId)->find((int) $batch->classroom_id);
        }
        $classroomId = $classroom ? (int) $classroom->id : 0;
        $batchId = $batch ? (int) $batch->id : 0;

        if ($classroomId < 1 || $batchId < 1) {
            return ['error' => 'Unknown classroom or batch for your account.'];
        }

        $classroom = Classroom::where('teacher_id', $teacherId)->find($classroomId);
        $batch = Batch::where('teacher_id', $teacherId)->find($batchId);
        if (! $classroom) {
            return ['error' => 'Classroom does not belong to your account.'];
        }
        if (! $batch) {
            return ['error' => 'Batch does not belong to your account.'];
        }
        if ((int) $batch->classroom_id !== (int) $classroom->id) {
            $classroom = Classroom::where('teacher_id', $teacherId)->find((int) $batch->classroom_id);
            if (! $classroom) {
                return ['error' => 'Batch is not linked to a valid classroom for your account.'];
            }
            $classroomId = (int) $classroom->id;
        }

        return ['pair' => ['classroom_id' => $classroomId, 'batch_id' => $batchId]];
    }

    public function downloadStudentBulkSample()
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) return $redirect;

        $firstBatch = Batch::where('teacher_id', $teacherId)->orderBy('id')->first();
        $firstClassroom = $firstBatch
            ? Classroom::where('teacher_id', $teacherId)->find((int)$firstBatch->classroom_id)
            : Classroom::where('teacher_id', $teacherId)->orderBy('id')->first();
        $sampleClassroomName = $firstClassroom ? (string)$firstClassroom->name : '8th Class';
        $sampleBatchName = $firstBatch ? (string)$firstBatch->name : 'Batch A';

        $secondClassroom = Classroom::where('teacher_id', $teacherId)->orderBy('id')->skip(1)->first();
        $secondBatch = $secondClassroom
            ? Batch::where('teacher_id', $teacherId)->where('classroom_id', $secondClassroom->id)->orderBy('id')->first()
            : null;
        $sampleClassroom2 = $secondClassroom ? (string) $secondClassroom->name : '';
        $sampleBatch2 = $secondBatch ? (string) $secondBatch->name : '';

        $rows = [
            ['name', 'email', 'phone', 'classroom', 'batch', 'classroom_2', 'batch_2', 'classroom_3', 'batch_3', 'parent_name', 'parent_email', 'parent_phone'],
            ['John Doe', 'john.doe@example.com', '9876543210', $sampleClassroomName, $sampleBatchName, '', '', '', '', 'Parent One', 'parent.one@example.com', '9000000001'],
            ['Jane Smith', 'jane.smith@example.com', '9876543211', $sampleClassroomName, $sampleBatchName, $sampleClassroom2, $sampleBatch2, '', '', 'Parent Two', 'parent.two@example.com', '9000000002'],
        ];

        $filename = 'student_bulk_sample.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function bulkUploadStudents(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);

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
            $normalized = strtolower(trim((string)$value));
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

            $name = trim((string)($row[$headerMap['name']] ?? ''));
            $email = trim((string)($row[$headerMap['email']] ?? ''));
            $phone = trim((string)($row[$headerMap['phone']] ?? ''));
            $classroomRaw = trim((string)($row[$headerMap['classroom_id']] ?? ''));
            $batchRaw = trim((string)($row[$headerMap['batch_id']] ?? ''));
            $parentName = array_key_exists('parent_name', $headerMap) ? trim((string)($row[$headerMap['parent_name']] ?? '')) : '';
            $parentEmail = array_key_exists('parent_email', $headerMap) ? trim((string)($row[$headerMap['parent_email']] ?? '')) : '';
            $parentPhone = array_key_exists('parent_phone', $headerMap) ? trim((string)($row[$headerMap['parent_phone']] ?? '')) : '';

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
            $pairsAssoc[$p1['classroom_id'].'-'.$p1['batch_id']] = $p1;

            foreach ([['classroom_2', 'batch_2'], ['classroom_3', 'batch_3']] as $cols) {
                [$ck, $bk] = $cols;
                if (! isset($headerMap[$ck]) || ! isset($headerMap[$bk])) {
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
                $pairsAssoc[$px['classroom_id'].'-'.$px['batch_id']] = $px;
            }

            $pairs = array_values($pairsAssoc);

            [$pairsOk, $pairsErr] = StudentEnrollmentSync::validatePairsForTeacher($teacherId, $pairs);
            if (! $pairsOk) {
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
                $errors[] = 'Row ' . $line . ': ' . implode(' ', array_map(static function ($msg) {
                    return is_array($msg) ? ($msg[0] ?? '') : (string)$msg;
                }, $rowValidator->errors()->toArray()));
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
                $phoneTakenByOther = PortalUser::where('role', 2)
                    ->where('phone', $phone)
                    ->where('id', '!=', $studentByEmail->id)
                    ->exists();
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
                    StudentTeacherMap::firstOrCreate(
                        [
                            'student_id' => $studentByEmail->id,
                            'teacher_id' => $teacherId,
                        ]
                    );
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
                    StudentTeacherMap::firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'teacher_id' => $teacherId,
                        ]
                    );
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
                    $skipped++; continue;
                }
                if ($parentPhone !== '' && PortalUser::where('phone', $parentPhone)->exists()) {
                    $errors[] = 'Row ' . $line . ': parent phone already exists.';
                    $skipped++; continue;
                }
                $parent = new PortalUser();
                $pPlain = Str::random(8);
                $parent->password = Hash::make($pPlain);
                $parent->p = $pPlain;
                $parent->is_password_changed = 0;
                $parent->role = 3;
                $parent->created_by = $teacherId;
                $parent->name = $parentName !== '' ? $parentName : 'Parent of ' . $student->name;
                if ($parentEmail !== '') $parent->email = $parentEmail;
                if ($parentPhone !== '') $parent->phone = $parentPhone;
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
        $status = ($created + $updated) > 0 ? 1 : 0;
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
        if ($redirect) return $redirect;

        $decodedId = base64_decode((string)$id, true);
        if ($decodedId === false) {
            $decodedId = $id;
        }

        $student = PortalUser::where('role', 2)
            ->whereHas('teachers', static fn ($q) => $q->whereKey($teacherId))
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

        $classroom = Classroom::with(['batches' => fn ($q) => $q->orderBy('name')])->find($id);

        if (! $classroom || (int) $classroom->teacher_id !== $teacherId) {
            return redirect('user/teacher/classrooms');
        }

        $mapRows = StudentClassroomMap::query()
            ->where('teacher_id', $teacherId)
            ->where('classroom_id', $classroom->id)
            ->whereNotNull('batch_id')
            ->get();

        $studentIds = $mapRows->pluck('student_id')->unique()->filter()->values();
        $studentsById = $studentIds->isEmpty()
            ? collect()
            : PortalUser::query()
                ->whereIn('id', $studentIds)
                ->where('role', 2)
                ->whereNull('deleted_at')
                ->get()
                ->keyBy('id');

        $mapsByBatch = $mapRows->groupBy('batch_id');
        $studentsByBatch = [];
        foreach ($classroom->batches as $batch) {
            $seen = [];
            $list = collect();
            foreach ($mapsByBatch->get($batch->id, collect()) as $map) {
                $student = $studentsById->get($map->student_id);
                if (! $student || isset($seen[$student->id])) {
                    continue;
                }
                $seen[$student->id] = true;
                $list->push($student);
            }
            $studentsByBatch[$batch->id] = $list->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }

        $totalStudentsInClassroom = $mapRows->pluck('student_id')->unique()->filter(fn ($sid) => $studentsById->has($sid))->count();

        $batchIds = $classroom->batches->pluck('id')->map(fn ($id) => (int) $id)->values();
        $examsGrouped = $batchIds->isEmpty()
            ? collect()
            : Exam::query()
                ->whereIn('batch_id', $batchIds)
                ->orderBy('exam_date')
                ->orderBy('id')
                ->get()
                ->groupBy(fn (Exam $e) => (int) $e->batch_id);

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

        $data = [];
        $data['title'] = 'Classroom: '.$classroom->name;
        $data['active_tab'] = 'teacher_classrooms';
        $data['classroom'] = $classroom;
        $data['students_by_batch'] = $studentsByBatch;
        $data['total_students_in_classroom'] = $totalStudentsInClassroom;
        $data['exams_by_batch'] = $examsByBatch;
        $data['marks_by_exam_student'] = $marksByExamStudent;
        $data['marks_table_ready'] = Schema::hasTable('marks');

        return view('web.user.teacher.classroom_details', $data);
    }

    public function saveExam(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
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

        $batch = Batch::query()
            ->where('teacher_id', $teacherId)
            ->whereKey((int) $request->batch_id)
            ->first();

        if (! $batch) {
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
            'redirect_url' => url('user/teacher/classrooms/details/'.$batch->classroom_id),
        ]);
    }

}

