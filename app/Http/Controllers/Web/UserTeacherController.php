<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\ParentStudentMap;
use App\Models\PortalUser;
use App\Models\StudentTeacherMap;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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

        $mainUrl = url('user/teacher/students');
        $url = [];

        $students = PortalUser::where('role', 2)
            ->join('student_teacher_map as stm', 'stm.student_id', '=', 'portal_user.id')
            ->where('stm.teacher_id', $teacherId)
            ->select('portal_user.*', 'stm.classroom_id as classroom_id', 'stm.batch_id as batch_id')
            ->with(['batch', 'classroom']);

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
            $students->where('classroom_id', $classroomId);
            $url[] = 'classroom_id=' . $classroomId;
        }

        $data['batch_id'] = $batchId = (int)($request->batch_id ?? 0);
        if ($batchId > 0) {
            $students->where('batch_id', $batchId);
            $url[] = 'batch_id=' . $batchId;
        }

        $data['page'] = $page = (int)($request->page ?? 1);
        if ($page < 1) $page = 1;
        $data['per_page'] = $perPage = (int)($request->per_page ?? 50);
        if ($perPage < 1) $perPage = 50;

        $data['url'] = $mainUrl . (count($url) ? ('?' . implode('&', $url)) : '');
        $data['num_rows'] = (clone $students)->distinct('portal_user.id')->count('portal_user.id');
        $data['students'] = $students->orderBy('id', 'desc')
            ->distinct()
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

    // CRUD: Students
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
            'classroom_id' => 'required|exists:classrooms,id',
            'batch_id' => 'required|exists:batches,id',
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

        $classroom = Classroom::where('teacher_id', $teacherId)->find($request->classroom_id);
        $batch = Batch::where('teacher_id', $teacherId)->find($request->batch_id);
        if (!$classroom || !$batch) return response()->json(['status' => 0, 'error' => 'Invalid classroom or batch']);

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
            if (!$student->classroom_id) {
                $student->classroom_id = $request->classroom_id;
            }
            if (!$student->batch_id) {
                $student->batch_id = $request->batch_id;
            }
            $student->save();

            StudentTeacherMap::updateOrCreate([
                'student_id' => $student->id,
                'teacher_id' => $teacherId,
            ], [
                'classroom_id' => $request->classroom_id,
                'batch_id' => $request->batch_id,
            ]);

            $parentName = trim((string)$request->parent_name);
            $parentEmail = trim((string)$request->parent_email);
            $parentPhone = trim((string)$request->parent_phone);
            if ($parentName !== '' || $parentEmail !== '' || $parentPhone !== '') {
                $parent = null;
                if ($parentEmail !== '') {
                    $parent = PortalUser::where('role', 3)->where('email', $parentEmail)->first();
                }
                if (!$parent && $parentPhone !== '') {
                    $parent = PortalUser::where('role', 3)->where('phone', $parentPhone)->first();
                }
                if (!$parent) {
                    if ($parentEmail !== '' && PortalUser::where('email', $parentEmail)->where('role', '!=', 3)->exists()) {
                        DB::rollBack();
                        return response()->json(['status' => 0, 'error' => 'Parent email already used by another user type.']);
                    }
                    if ($parentPhone !== '' && PortalUser::where('phone', $parentPhone)->where('role', '!=', 3)->exists()) {
                        DB::rollBack();
                        return response()->json(['status' => 0, 'error' => 'Parent phone already used by another user type.']);
                    }
                    $parent = new PortalUser();
                    $parentPlain = Str::random(8);
                    $parent->password = Hash::make($parentPlain);
                    $parent->p = $parentPlain;
                    $parent->is_password_changed = 0;
                    $parent->role = 3;
                    $parent->created_by = $teacherId;
                }
                if ($parentName !== '') $parent->name = $parentName;
                if ($parentEmail !== '') $parent->email = $parentEmail;
                if ($parentPhone !== '') $parent->phone = $parentPhone;
                if ($parent->name === null || $parent->name === '') {
                    $parent->name = 'Parent of ' . $student->name;
                }
                $parent->save();

                ParentStudentMap::firstOrCreate([
                    'parent_id' => $parent->id,
                    'student_id' => $student->id,
                ]);

                if (!$student->parent_id) {
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
        StudentTeacherMap::where('student_id', $student->id)->where('teacher_id', $teacherId)->delete();
        $stillMapped = StudentTeacherMap::where('student_id', $student->id)->exists();
        if (!$stillMapped) {
            ParentStudentMap::where('student_id', $student->id)->delete();
            $student->delete();
        }
        return response()->json(['status' => 1, 'msg' => 'Deleted', 'redirect_url' => url('user/teacher/students')]);
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

        $rows = [
            ['name', 'email', 'phone', 'classroom', 'batch', 'parent_name', 'parent_email', 'parent_phone'],
            ['John Doe', 'john.doe@example.com', '9876543210', $sampleClassroomName, $sampleBatchName, 'Parent One', 'parent.one@example.com', '9000000001'],
            ['Jane Smith', 'jane.smith@example.com', '9876543211', $sampleClassroomName, $sampleBatchName, 'Parent Two', 'parent.two@example.com', '9000000002'],
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

            $classroom = ctype_digit($classroomRaw)
                ? Classroom::where('teacher_id', $teacherId)->find((int)$classroomRaw)
                : Classroom::where('teacher_id', $teacherId)->whereRaw('LOWER(name) = ?', [strtolower($classroomRaw)])->first();

            $batch = ctype_digit($batchRaw)
                ? Batch::where('teacher_id', $teacherId)->find((int)$batchRaw)
                : Batch::where('teacher_id', $teacherId)->whereRaw('LOWER(name) = ?', [strtolower($batchRaw)])->first();

            if (!$classroom && $batch) {
                $classroom = Classroom::where('teacher_id', $teacherId)->find((int)$batch->classroom_id);
            }

            $classroomId = $classroom ? (int)$classroom->id : 0;
            $batchId = $batch ? (int)$batch->id : 0;

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

            $classroom = Classroom::where('teacher_id', $teacherId)->find($classroomId);
            $batch = Batch::where('teacher_id', $teacherId)->find($batchId);
            if (!$classroom) {
                $errors[] = 'Row ' . $line . ': classroom_id does not belong to you.';
                $skipped++;
                continue;
            }
            if (!$batch) {
                $errors[] = 'Row ' . $line . ': batch_id does not belong to you.';
                $skipped++;
                continue;
            }
            if ((int)$batch->classroom_id !== (int)$classroom->id) {
                // Keep import resilient: trust batch's classroom mapping when mismatch is provided.
                $classroom = Classroom::where('teacher_id', $teacherId)->find((int)$batch->classroom_id);
                if (!$classroom) {
                    $errors[] = 'Row ' . $line . ': batch_id does not belong to a valid classroom for your account.';
                    $skipped++;
                    continue;
                }
                $classroomId = (int)$classroom->id;
            }

            $emailUsed = PortalUser::where('email', $email)->exists();
            if ($emailUsed) {
                $errors[] = 'Row ' . $line . ': email already exists.';
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
            $created++;

            $student->name = $name;
            $student->email = $email;
            $student->phone = $phone;
            $student->classroom_id = $classroomId;
            $student->batch_id = $batchId;
            try {
                $student->save();
            } catch (\Throwable $e) {
                $errors[] = 'Row ' . $line . ': failed to insert (' . $e->getMessage() . ')';
                $skipped++;
                $created--;
                continue;
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
            ->join('student_teacher_map as stm', 'stm.student_id', '=', 'portal_user.id')
            ->where('stm.teacher_id', $teacherId)
            ->select('portal_user.*')
            ->with(['classroom', 'batch.classroom', 'teachers'])
            ->find($decodedId);

        if (!$student) {
            return redirect('user/teacher/students');
        }

        $data = [];
        $data['title'] = 'View Student';
        $data['active_tab'] = 'teacher_students';
        $mapRow = StudentTeacherMap::where('student_id', $student->id)
            ->where('teacher_id', $teacherId)
            ->first();
        if ($mapRow) {
            $student->classroom_id = $mapRow->classroom_id;
            $student->batch_id = $mapRow->batch_id;
            $student->setRelation('classroom', $mapRow->classroom_id ? Classroom::find($mapRow->classroom_id) : null);
            $student->setRelation('batch', $mapRow->batch_id ? Batch::with('classroom')->find($mapRow->batch_id) : null);
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

        $classroom = Classroom::with('batches', 'students')->find($id);
        // dd($classroom);

        if (! $classroom || (int) $classroom->teacher_id !== $teacherId) {
            return redirect('user/teacher/classrooms');
        }

        // $mappedStudents = PortalUser::query()
        //     ->where('portal_user.role', 2)
        //     ->whereNull('portal_user.deleted_at')
        //     ->join('student_teacher_map as stm', 'stm.student_id', '=', 'portal_user.id')
        //     ->leftJoin('batches as b', 'b.id', '=', 'stm.batch_id')
        //     ->where('stm.teacher_id', $teacherId)
        //     ->where('stm.classroom_id', $classroom->id)
        //     ->orderBy('portal_user.name')
        //     ->select([
        //         'portal_user.id',
        //         'portal_user.name',
        //         'portal_user.email',
        //         'portal_user.phone',
        //         'portal_user.created_at',
        //         'stm.batch_id',
        //         'b.name as batch_name',
        //     ])
        //     ->get();

        // $batchStudentCounts = $mappedStudents->groupBy('batch_id')->map->count();

        $data = [];
        $data['title'] = 'Classroom: '.$classroom->name;
        $data['active_tab'] = 'teacher_classrooms';
        $data['classroom'] = $classroom;
        return view('web.user.teacher.classroom_details', $data);
    }

}

