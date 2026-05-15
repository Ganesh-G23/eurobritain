<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentStudentMap;
use App\Models\PortalUser;
use App\Models\Classroom;
use App\Models\Batch;
use App\Models\StudentClassroomMap;
use App\Models\StudentBatchEnrollmentPeriod;
use App\Models\StudentTeacherMap;
use App\Support\PortalSession;
use App\Support\StudentEnrollmentSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    function __construct()
    {
        Config::set('global.active_tab', 'teacher_list');
    }

    function list(Request $request)
    {
        $mainUrl = url('admin/teacher');
        $url = [];

        $teacherList = PortalUser::where('role', 1)->orderBy('id', 'desc');

        $response['search'] = $search = $request->search ?? "";
        if ($search) {
            $teacherList->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%');
            });
            $url[] = 'search=' . $search;
        }

        $response['page'] = $page = $request->page ?? 1;
        $response['per_page'] = $perPage = $request->per_page ?? 50;

        $response['url'] = $mainUrl . '?' . implode('&', $url);
        $response['num_rows'] = $teacherList->count();
        $response['teacher_list'] = $teacherList->limit($perPage)->offset(($page - 1) * $perPage)->get();

        $response['title'] = 'Teachers';
        $response['active_tab'] = 'teacher';

        return view('admin.teacher.list', $response);
    }

    function form(Request $request)
    {
        $id = base64_decode($request->id) ?? null;

        $teacher = new PortalUser();
        $response['edit'] = $teacher->find($id);
        $response['title'] = isset($response['edit']) ? 'Edit Teacher' : 'Add Teacher';
        $response['active_tab'] = 'teacher';

        return view('admin.teacher.form', $response);
    }

    function save(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;
        
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:portal_user,email' . ($id ? ',' . $id : ''),
            'phone' => 'required|string|unique:portal_user,phone' . ($id ? ',' . $id : ''),
        ]);

        // Removed: previous logic that required at least one of email/phone

        if (!$validation->fails()) {
            $user = new PortalUser();
            if ($id) {
                $user = PortalUser::find($id);
            }

            // Generate password only for new users
            $plainPassword = null;
            if (!$id) {
                $plainPassword = Str::random(8); // Generate 12 character random password
                $user->password = Hash::make($plainPassword);
                $user->p = $plainPassword;
                $user->is_password_changed = 0;
            }

            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->role = 1; // Always set role to 1 (teacher)
            $user->save();

            // Send email with password for new users (only if email is provided)
            if (!$id && $plainPassword && $request->email) {
                try {
                    Mail::send('admin.emails.teacher_registration', [
                        'name' => $request->name,
                        'email' => $request->email,
                        'phone' => $request->phone,
                        'password' => $plainPassword,
                    ], function ($message) use ($request) {
                        $message->to($request->email)
                            ->subject('Registration Successful - Your Login Credentials');
                    });
                } catch (\Exception $e) {
                    // Log error but don't fail the registration
                    Log::error('Failed to send registration email: ' . $e->getMessage());
                }
            }

            $this->response['status'] = 1;
            $this->response['msg'] = "Teacher saved successfully";
            if (isset($request->reload) && ($request->reload == 'no')) {
                $this->response['data']['row'] = $user;
            } else {
                $this->response['redirect_url'] = url("admin/teacher");
            }
        } else {
            $this->response['status'] = 0;
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }

    function view(Request $request)
    {
        $id = base64_decode($request->id) ?? null;
        
        if (!$id) {
            return redirect('admin/teacher');
        }

        $teacher = PortalUser::find($id);
        
        if (!$teacher) {
            return redirect('admin/teacher');
        }

        $response['teacher'] = $teacher;
        $response['classrooms'] = Classroom::where('teacher_id', $id)->orderBy('id', 'desc')->get();
        $response['batches'] = Batch::where('teacher_id', $id)->with('classroom')->orderBy('id', 'desc')->get();
        $response['students'] = PortalUser::query()
            ->where('portal_user.role', 2)
            ->whereHas('teachers', static function ($q) use ($id) {
                $q->whereKey($id);
            })
            ->with(['studentClassroomMaps' => static function ($q) use ($id) {
                $q->where('teacher_id', $id)->with(['classroom', 'batch']);
            }])
            ->orderBy('portal_user.id', 'desc')
            ->get();

        // Counts for overview
        $response['total_students'] = DB::table('portal_user')
            ->join('student_teacher_map as stm', 'stm.student_id', '=', 'portal_user.id')
            ->where('portal_user.role', 2)
            ->where('stm.teacher_id', $id)
            ->distinct('portal_user.id')
            ->whereNull('portal_user.deleted_at')
            ->count('portal_user.id');
        $response['total_classrooms'] = Classroom::where('teacher_id', $id)->count();
        $response['total_batches'] = Batch::where('teacher_id', $id)->count();
        
        $response['title'] = 'View Teacher';
        $response['active_tab'] = 'teacher';

        return view('admin.teacher.view', $response);
    }

    function saveClassroom(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'teacher_id' => 'required|exists:portal_user,id',
        ]);

        if (!$validation->fails()) {
            $classroom = new Classroom();
            $id = $request->id ? base64_decode($request->id) : null;
            if ($id) {
                $classroom = Classroom::find($id);
            }

            $classroom->name = $request->name;
            $classroom->teacher_id = (int) $request->teacher_id;
            $classroom->save();

            $this->response['status'] = 1;
            $this->response['msg'] = "Classroom saved successfully";
            if (isset($request->reload) && ($request->reload == 'no')) {
                $this->response['data']['row'] = $classroom;
            } else {
                $this->response['redirect_url'] = url("admin/teacher/view?id=" . base64_encode($request->teacher_id));
            }
        } else {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }

    function deleteClassroom(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;
        
        if ($id) {
            $classroom = Classroom::find($id);
            if ($classroom) {
                $teacherId = $classroom->teacher_id;
                $classroom->delete();
                $this->response['status'] = 1;
                $this->response['msg'] = "Classroom deleted successfully";
                $this->response['redirect_url'] = url("admin/teacher/view?id=" . base64_encode($teacherId));
            } else {
                $this->response['error'] = "Classroom not found";
            }
        } else {
            $this->response['error'] = "Invalid request";
        }

        echo json_encode($this->response);
    }

    function delete(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;
        
        if ($id) {
            $teacher = PortalUser::find($id);
            if ($teacher) {
                $teacher->delete();
                $this->response['status'] = 1;
                $this->response['msg'] = "Teacher deleted successfully";
                $this->response['redirect_url'] = url("admin/teacher");
            } else {
                $this->response['error'] = "Teacher not found";
            }
        } else {
            $this->response['error'] = "Invalid request";
        }

        echo json_encode($this->response);
    }

    function saveBatch(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id',
            'teacher_id' => 'required|exists:portal_user,id',
            'status' => 'required|in:active,pending,inactive',
            'schedule' => 'nullable|string',
        ]);

        if (!$validation->fails()) {
            $id = $request->id ? base64_decode($request->id) : null;
            if ($id) {
                $batch = Batch::find($id);
                if (!$batch) {
                    $this->response['error'] = "Batch not found";
                    echo json_encode($this->response);
                    return;
                }
            } else {
                $batch = new Batch();
            }

            $batch->name = $request->name;
            $batch->classroom_id = $request->classroom_id;
            $batch->teacher_id = $request->teacher_id;
            $batch->status = $request->status;
            $batch->schedule = $request->schedule;
            $batch->save();

            $this->response['status'] = 1;
            $this->response['msg'] = "Batch saved successfully";
            if (isset($request->reload) && ($request->reload == 'no')) {
                $this->response['data']['row'] = $batch;
            } else {
                $this->response['redirect_url'] = url("admin/teacher/view?id=" . base64_encode($request->teacher_id));
            }
        } else {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }

    function deleteBatch(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;
        
        if ($id) {
            $batch = Batch::find($id);
            if ($batch) {
                $teacherId = $batch->teacher_id;
                $batch->delete();
                $this->response['status'] = 1;
                $this->response['msg'] = "Batch deleted successfully";
                $this->response['redirect_url'] = url("admin/teacher/view?id=" . base64_encode($teacherId));
            } else {
                $this->response['error'] = "Batch not found";
            }
        } else {
            $this->response['error'] = "Invalid request";
        }

        echo json_encode($this->response);
    }

    function saveStudent(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'teacher_id' => 'required|exists:portal_user,id',
            'parent_name' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email',
            'parent_phone' => 'nullable|string|max:30',
        ];
        if ($request->id) {
            $decodedId = (int) base64_decode($request->id);
            // Scope to students only so a linked parent (same table, role 3) can share phone/email.
            $rules['email'] = [
                'required',
                'email',
                Rule::unique('portal_user', 'email')
                    ->ignore($decodedId)
                    ->where(function ($query) {
                        $query->where('role', 2)->whereNull('deleted_at');
                    }),
            ];
            $rules['phone'] = [
                'required',
                'string',
                Rule::unique('portal_user', 'phone')
                    ->ignore($decodedId)
                    ->where(function ($query) {
                        $query->where('role', 2)->whereNull('deleted_at');
                    }),
            ];
        }
        $validation = Validator::make($request->all(), $rules);

        if (!$validation->fails()) {
            $id = $request->id ? base64_decode($request->id) : null;
            $teacherId = (int) $request->teacher_id;
            $plainPassword = null;

            DB::beginTransaction();
            try {
                if ($id) {
                    $student = PortalUser::where('role', 2)->find($id);
                    if (!$student) {
                        DB::rollBack();
                        $this->response['error'] = "Student not found";
                        echo json_encode($this->response);
                        return;
                    }
                } else {
                    $studentByEmail = PortalUser::where('role', 2)->where('email', $request->email)->first();
                    $studentByPhone = PortalUser::where('role', 2)->where('phone', $request->phone)->first();
                    if ($studentByEmail && $studentByPhone && (int)$studentByEmail->id !== (int)$studentByPhone->id) {
                        DB::rollBack();
                        $this->response['status'] = 0;
                        $this->response['error'] = "Email and phone belong to different students.";
                        echo json_encode($this->response);
                        return;
                    }
                    $student = $studentByEmail ?: $studentByPhone;
                    if (!$student) {
                        if (PortalUser::where('email', $request->email)->where('role', '!=', 2)->exists()) {
                            DB::rollBack();
                            $this->response['status'] = 0;
                            $this->response['error'] = "Email already used by another user type.";
                            echo json_encode($this->response);
                            return;
                        }
                        if (PortalUser::where('phone', $request->phone)->where('role', '!=', 2)->exists()) {
                            DB::rollBack();
                            $this->response['status'] = 0;
                            $this->response['error'] = "Phone already used by another user type.";
                            echo json_encode($this->response);
                            return;
                        }
                        $student = new PortalUser();
                        $plainPassword = Str::random(8);
                        $student->password = Hash::make($plainPassword);
                        $student->p = $plainPassword;
                        $student->is_password_changed = 0;
                        $student->role = 2; // Student
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

            // Handle Parent creation/upsert in same portal_user table with role=3
            $parentName = trim((string)$request->parent_name);
            $parentEmail = trim((string)$request->parent_email);
            $parentPhone = trim((string)$request->parent_phone);
            if ($parentName !== '' || $parentEmail !== '' || $parentPhone !== '') {
                // Resolve parent to link/update following precedence:
                // 1) If email/phone provided and matches an existing PARENT (role=3), reuse that parent
                // 2) Else if student already has a parent, update that parent
                // 3) Else create a new parent
                $parent = null;

                // Try by email/phone to support reassigning to an existing parent (role=3) across multiple students
                if ($parentEmail !== '') {
                    $match = PortalUser::where('email', $parentEmail)->first();
                    if ($match) {
                        if ((int)($match->role ?? 0) !== 3) {
                            DB::rollBack();
                            $this->response['status'] = 0;
                            $this->response['error'] = 'Parent email already exists for another user type.';
                            echo json_encode($this->response);
                            return;
                        }
                        $parent = $match;
                    }
                }
                if (!$parent && $parentPhone !== '') {
                    $match = PortalUser::where('phone', $parentPhone)->first();
                    if ($match) {
                        if ((int)($match->role ?? 0) !== 3) {
                            DB::rollBack();
                            $this->response['status'] = 0;
                            $this->response['error'] = 'Parent phone already exists for another user type.';
                            echo json_encode($this->response);
                            return;
                        }
                        $parent = $match;
                    }
                }

                // If not found by email/phone, fall back to currently linked parent
                if (!$parent && !empty($student->parent_id)) {
                    $parent = PortalUser::where('role', 3)->find((int)$student->parent_id);
                }

                if (!$parent) {
                    // Create new parent
                    $parent = new PortalUser();
                    $parentPlain = Str::random(8);
                    $parent->password = Hash::make($parentPlain);
                    $parent->p = $parentPlain;
                    $parent->is_password_changed = 0;
                    $parent->role = 3; // Parent
                    $parent->created_by = $teacherId;
                } else {
                    // If reusing existing parent, don't error on same value; allow updating email/phone only if not colliding
                    if ($parentEmail !== '' && $parentEmail !== (string)$parent->email) {
                        $existsOther = PortalUser::where('email', $parentEmail)->where('id', '!=', $parent->id)->exists();
                        if ($existsOther) {
                            DB::rollBack();
                            $this->response['status'] = 0;
                            $this->response['error'] = 'Parent email already exists.';
                            echo json_encode($this->response);
                            return;
                        }
                    }
                    if ($parentPhone !== '' && $parentPhone !== (string)$parent->phone) {
                        $existsOther = PortalUser::where('phone', $parentPhone)->where('id', '!=', $parent->id)->exists();
                        if ($existsOther) {
                            DB::rollBack();
                            $this->response['status'] = 0;
                            $this->response['error'] = 'Parent phone already exists.';
                            echo json_encode($this->response);
                            return;
                        }
                    }
                }

                // Apply updates
                if ($parentName !== '') $parent->name = $parentName;
                if ($parentEmail !== '') $parent->email = $parentEmail;
                if ($parentPhone !== '') $parent->phone = $parentPhone;
                $parent->save();

                // Ensure link and parent_id reflect the resolved parent (supports reassigning)
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
                $this->response['status'] = 0;
                $this->response['error'] = "Unable to save student. " . $e->getMessage();
                echo json_encode($this->response);
                return;
            }

            // Send email with password for newly created student
            if (!$id && $plainPassword && $request->email) {
                try {
                    Mail::send('admin.emails.teacher_registration', [
                        'name' => $request->name,
                        'email' => $request->email,
                        'phone' => $request->phone,
                        'password' => $plainPassword,
                    ], function ($message) use ($request) {
                        $message->to($request->email)
                            ->subject('Registration Successful - Your Login Credentials');
                    });
                } catch (\Exception $e) {
                    // Log error but don't fail the student registration
                    Log::error('Failed to send student registration email: ' . $e->getMessage());
                }
            }

            $this->response['status'] = 1;
            $this->response['msg'] = "Student saved successfully";
            if (isset($request->reload) && ($request->reload == 'no')) {
                $this->response['data']['row'] = $student;
            } elseif ($request->boolean('return_student_list')) {
                $this->response['redirect_url'] = url('admin/student');
            } else {
                if ($teacherId) {
                    $this->response['redirect_url'] = url("admin/teacher/view?id=" . base64_encode($teacherId));
                }
            }
        } else {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }

    function syncStudentEnrollments(Request $request)
    {
        $studentId = $request->student_id ? (int) base64_decode($request->student_id) : 0;
        $teacherId = (int) ($request->teacher_id ?? 0);

        if ($studentId < 1 || $teacherId < 1) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Invalid student or teacher.';
            echo json_encode($this->response);

            return;
        }

        $student = PortalUser::where('role', 2)->find($studentId);
        if (! $student) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Student not found.';
            echo json_encode($this->response);

            return;
        }

        $pairs = StudentEnrollmentSync::pairsFromRequestArrays(
            (array) $request->input('map_classroom_id', []),
            (array) $request->input('map_batch_id', [])
        );

        if (count($pairs) < 1) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Add at least one classroom and batch.';
            echo json_encode($this->response);

            return;
        }

        [$ok, $err] = StudentEnrollmentSync::validatePairsForTeacher($teacherId, $pairs);
        if (! $ok) {
            $this->response['status'] = 0;
            $this->response['error'] = $err;
            echo json_encode($this->response);

            return;
        }

        try {
            StudentEnrollmentSync::syncForTeacher($studentId, $teacherId, $pairs);
        } catch (\Throwable $e) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Unable to save enrollments. '.$e->getMessage();
            echo json_encode($this->response);

            return;
        }

        $this->response['status'] = 1;
        $this->response['msg'] = 'Classrooms saved successfully';
        if ($request->boolean('return_student_list')) {
            $this->response['redirect_url'] = url('admin/student');
        } else {
            $this->response['redirect_url'] = url('admin/teacher/view?id='.base64_encode($teacherId));
        }
        echo json_encode($this->response);
    }

    function deleteStudent(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;
        $requestedTeacherId = $request->teacher_id ? base64_decode($request->teacher_id) : null;

        if ($id) {
            $student = PortalUser::where('role', 2)->find($id);
            if ($student) {
                $teacherId = $requestedTeacherId ? (int) $requestedTeacherId : null;
                if (! $teacherId) {
                    $batch = Batch::find($student->batch_id);
                    $teacherId = $batch ? (int) $batch->teacher_id : null;
                }
                StudentBatchEnrollmentPeriod::closeOpenPeriodsForStudent((int) $student->id);
                StudentClassroomMap::where('student_id', $student->id)->delete();
                StudentTeacherMap::where('student_id', $student->id)->delete();
                ParentStudentMap::where('student_id', $student->id)->delete();
                $student->delete();
                $this->response['status'] = 1;
                $this->response['msg'] = "Student deleted successfully";
                if ($teacherId) {
                    $this->response['redirect_url'] = url("admin/teacher/view?id=" . base64_encode($teacherId));
                }
            } else {
                $this->response['error'] = "Student not found";
            }
        } else {
            $this->response['error'] = "Invalid request";
        }

        echo json_encode($this->response);
    }

    function viewStudent(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;
        $requestedTeacherId = $request->teacher_id ? base64_decode($request->teacher_id) : null;

        if (!$id) {
            return redirect('admin/teacher');
        }

        $student = PortalUser::where('role', 2)
            ->with(['classroom', 'batch.classroom', 'teachers'])
            ->find($id);

        if (!$student) {
            return redirect('admin/teacher');
        }

        $teacherId = 0;
        if ($requestedTeacherId) {
            $isMapped = $student->teachers->contains(function ($teacher) use ($requestedTeacherId) {
                return (int)$teacher->id === (int)$requestedTeacherId;
            });
            if ($isMapped) {
                $teacherId = (int)$requestedTeacherId;
            }
        }
        if (!$teacherId) {
            $teacherId = (int)($student->teachers->first()->id ?? 0);
        }
        $response['student_classroom_maps'] = collect();
        if ($teacherId) {
            $maps = StudentClassroomMap::where('student_id', $student->id)
                ->where('teacher_id', $teacherId)
                ->with(['classroom', 'batch'])
                ->orderBy('id')
                ->get();
            $response['student_classroom_maps'] = $maps;
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
        }

        $response['student'] = $student;
        $response['teacher'] = $teacherId ? PortalUser::find($teacherId) : null;
        $response['student_teachers'] = $student->teachers;
        $response['title'] = 'View Student';
        $response['active_tab'] = 'teacher';

        return view('admin.teacher.student_view', $response);
    }

    function getBatchesByClassroom(Request $request)
    {
        $classroomId = $request->classroom_id;
        
        if (!$classroomId) {
            $this->response['status'] = 0;
            $this->response['error'] = "Classroom ID is required";
            echo json_encode($this->response);
            return;
        }

        $batches = Batch::where('classroom_id', $classroomId)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        $this->response['status'] = 1;
        $this->response['data'] = $batches;
        echo json_encode($this->response);
    }

    /**
     * Resolve one classroom/batch pair for bulk CSV (name or id, scoped to teacher).
     *
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
            : Classroom::where('teacher_id', $teacherId)->whereRaw('LOWER(name)=?', [strtolower($classroomRaw)])->first();
        $batch = ctype_digit($batchRaw)
            ? Batch::where('teacher_id', $teacherId)->find((int) $batchRaw)
            : Batch::where('teacher_id', $teacherId)->whereRaw('LOWER(name)=?', [strtolower($batchRaw)])->first();

        if (! $classroom && $batch) {
            $classroom = Classroom::where('teacher_id', $teacherId)->find((int) ($batch->classroom_id));
        }
        $classroomId = $classroom ? (int) $classroom->id : 0;
        $batchId = $batch ? (int) $batch->id : 0;

        if ($classroomId < 1 || $batchId < 1) {
            return ['error' => 'Unknown classroom or batch for this teacher.'];
        }

        $classroom = Classroom::where('teacher_id', $teacherId)->find($classroomId);
        $batch = Batch::where('teacher_id', $teacherId)->find($batchId);
        if (! $classroom) {
            return ['error' => 'Classroom does not belong to this teacher.'];
        }
        if (! $batch) {
            return ['error' => 'Batch does not belong to this teacher.'];
        }
        if ((int) $batch->classroom_id !== (int) $classroom->id) {
            $classroom = Classroom::where('teacher_id', $teacherId)->find((int) $batch->classroom_id);
            if (! $classroom) {
                return ['error' => 'Batch is not linked to a valid classroom for this teacher.'];
            }
            $classroomId = (int) $classroom->id;
        }

        return ['pair' => ['classroom_id' => $classroomId, 'batch_id' => $batchId]];
    }

    public function downloadStudentBulkSample(Request $request)
    {
        $teacherId = (int)($request->teacher_id ?? 0);
        if (!$teacherId) {
            return response('Invalid teacher_id', 400);
        }
        $firstBatch = Batch::where('teacher_id', $teacherId)->orderBy('id')->first();
        $firstClassroom = $firstBatch ? Classroom::find((int)$firstBatch->classroom_id) : Classroom::where('teacher_id', $teacherId)->orderBy('id')->first();
        $sampleClassroom = $firstClassroom ? (string)$firstClassroom->name : '8th Class';
        $sampleBatch = $firstBatch ? (string)$firstBatch->name : 'Batch A';

        $secondClassroom = Classroom::where('teacher_id', $teacherId)->orderBy('id')->skip(1)->first();
        $secondBatch = $secondClassroom
            ? Batch::where('teacher_id', $teacherId)->where('classroom_id', $secondClassroom->id)->orderBy('id')->first()
            : null;
        $sampleClassroom2 = $secondClassroom ? (string) $secondClassroom->name : '';
        $sampleBatch2 = $secondBatch ? (string) $secondBatch->name : '';

        $rows = [
            ['name', 'email', 'phone', 'classroom', 'batch', 'classroom_2', 'batch_2', 'classroom_3', 'batch_3', 'parent_name', 'parent_email', 'parent_phone'],
            ['John Doe', 'john.doe@example.com', '9876543210', $sampleClassroom, $sampleBatch, '', '', '', '', 'Parent One', 'parent.one@example.com', '9000000001'],
            ['Jane Smith', 'jane.smith@example.com', '9876543211', $sampleClassroom, $sampleBatch, $sampleClassroom2, $sampleBatch2, '', '', 'Parent Two', 'parent.two@example.com', '9000000002'],
        ];

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="admin_student_bulk_sample.csv"',
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
        $teacherId = (int)($request->teacher_id ?? 0);
        if (!$teacherId) {
            return response()->json(['status' => 0, 'error' => 'Invalid teacher_id'], 400);
        }

        $validation = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);
        if ($validation->fails()) {
            return response()->json(['status' => 0, 'error_array' => $validation->errors()->toArray()]);
        }

        $handle = @fopen($request->file('file')->getRealPath(), 'r');
        if (!$handle) {
            return response()->json(['status' => 0, 'error' => 'Unable to read uploaded file']);
        }

        $normalize = static function ($v) {
            $n = strtolower(trim((string)$v));
            return preg_replace('/^\xEF\xBB\xBF/', '', $n) ?? $n;
        };

        $header = fgetcsv($handle);
        if (!$header || !is_array($header)) {
            fclose($handle);
            return response()->json(['status' => 0, 'error' => 'CSV file is empty']);
        }
        $headerMap = [];
        foreach ($header as $i => $col) {
            $headerMap[$normalize($col)] = $i;
        }
        if (isset($headerMap['classroom']) && !isset($headerMap['classroom_id'])) {
            $headerMap['classroom_id'] = $headerMap['classroom'];
        }
        if (isset($headerMap['batch']) && !isset($headerMap['batch_id'])) {
            $headerMap['batch_id'] = $headerMap['batch'];
        }
        $required = ['name','email','phone','classroom_id','batch_id'];
        $missing = [];
        foreach ($required as $c) {
            if (!array_key_exists($c, $headerMap)) $missing[] = $c;
        }
        if ($missing) {
            fclose($handle);
            return response()->json(['status' => 0, 'error' => 'Missing required columns: ' . implode(', ', $missing)]);
        }
        // Optional parent columns
        $hasParentName = array_key_exists('parent_name', $headerMap);
        $hasParentEmail = array_key_exists('parent_email', $headerMap);
        $hasParentPhone = array_key_exists('parent_phone', $headerMap);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (!is_array($row)) continue;

            $name = trim((string)($row[$headerMap['name']] ?? ''));
            $email = trim((string)($row[$headerMap['email']] ?? ''));
            $phone = trim((string)($row[$headerMap['phone']] ?? ''));
            $classroomRaw = trim((string)($row[$headerMap['classroom_id']] ?? ''));
            $batchRaw = trim((string)($row[$headerMap['batch_id']] ?? ''));
            $parentName = $hasParentName ? trim((string)($row[$headerMap['parent_name']] ?? '')) : '';
            $parentEmail = $hasParentEmail ? trim((string)($row[$headerMap['parent_email']] ?? '')) : '';
            $parentPhone = $hasParentPhone ? trim((string)($row[$headerMap['parent_phone']] ?? '')) : '';

            if ($name === '' && $email === '' && $phone === '' && $classroomRaw === '' && $batchRaw === '') continue;

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

            $rowValidator = Validator::make([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'classroom_id' => $classroomId,
                'batch_id' => $batchId,
            ], [
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string|max:30',
                'classroom_id' => 'required|integer|min:1',
                'batch_id' => 'required|integer|min:1',
            ]);
            if ($rowValidator->fails()) {
                $errors[] = 'Row ' . $line . ': ' . implode(' ', array_map(static function ($m) { return is_array($m)?($m[0]??''):(string)$m; }, $rowValidator->errors()->toArray()));
                $skipped++; continue;
            }

            // Email must be unique in DB; soft-deleted rows still occupy the unique index, so use withTrashed().
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
            // Reuse existing parent by email/phone if role=3; error only if belongs to another role
            $parent = null;
            if ($parentEmail !== '') {
                $existingByEmail = PortalUser::where('email', $parentEmail)->first();
                if ($existingByEmail) {
                    if ((int)($existingByEmail->role ?? 0) === 3) {
                        $parent = $existingByEmail;
                    } else {
                        $errors[] = 'Row ' . $line . ': parent email already used by another user type.';
                        $skipped++; continue;
                    }
                }
            }
            if (!$parent && $parentPhone !== '') {
                $existingByPhone = PortalUser::where('phone', $parentPhone)->first();
                if ($existingByPhone) {
                    if ((int)($existingByPhone->role ?? 0) === 3) {
                        $parent = $existingByPhone;
                    } else {
                        $errors[] = 'Row ' . $line . ': parent phone already used by another user type.';
                        $skipped++; continue;
                    }
                }
            }

            if (!$parent) {
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
                } catch (\Throwable $e) {
                    $errors[] = 'Row ' . $line . ': failed to create parent (' . $e->getMessage() . ')';
                    // do not continue; still try to link if parent has id (unlikely). Skip row for safety.
                    $skipped++; continue;
                }
            } else {
                // Update name if provided (email/phone remain as-is in bulk flow)
                if ($parentName !== '') {
                    $parent->name = $parentName;
                    try { $parent->save(); } catch (\Throwable $e) { /* ignore name update failure in bulk */ }
                }
            }

            // Link student to parent
            try {
                $student->parent_id = $parent->id;
                $student->save();
            } catch (\Throwable $e) {
                $errors[] = 'Row ' . $line . ': failed to link parent to student (' . $e->getMessage() . ')';
            }
        }
        }

        fclose($handle);

        $status = ($created + $updated) > 0 ? 1 : 0;
        return response()->json([
            'status' => $status,
            'msg' => "Bulk upload completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.",
            'summary' => ['created' => $created, 'updated' => $updated, 'skipped' => $skipped],
            'errors' => $errors,
            'error' => $status === 0 ? 'No rows were inserted or updated. Please check row issues.' : null,
            'redirect_url' => url('admin/teacher/view?id=' . base64_encode($teacherId)),
        ]);
    }

    function updateTeacherPasswordPopup(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!$validation->fails()) {
            $adminSession = session('admin');
            $userId = $adminSession['id'] ?? null;

            if (!$userId) {
                $this->response['error'] = "Unauthorized request";
                echo json_encode($this->response);
                return;
            }

            $user = PortalUser::find($userId);
            if (!$user) {
                $this->response['error'] = "User not found";
                echo json_encode($this->response);
                return;
            }

            $user->password = Hash::make($request->password);
            $user->p = $request->password;
            $user->is_password_changed = 1;
            $user->save();

            session()->put('admin', $user->toArray());
            if ((int)($user->role ?? 0) === 1) {
                session()->put('teacher', $user->toArray());
            } else {
                session()->forget('teacher');
            }
            PortalSession::putRoleUser((int) ($user->role ?? 0), $user->toArray());
            session()->forget('show_teacher_password_popup');

            $this->response['status'] = 1;
            $this->response['msg'] = "Password changed successfully";
        } else {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }

    function skipTeacherPasswordPopup(Request $request)
    {
        session()->forget('show_teacher_password_popup');
        $this->response['status'] = 1;
        $this->response['msg'] = "Skipped";
        echo json_encode($this->response);
    }

    public function loginAs($encodedId)
    {
        $id = base64_decode($encodedId);
        if (!$id) {
            return redirect('admin/teacher');
        }

        $user = PortalUser::where('role', 1)->find($id);
        if (!$user) {
            return redirect('admin/teacher');
        }

        // Do NOT clear admin session to allow returning to admin panel
        PortalSession::forgetOtherRoleBuckets(1);
        PortalSession::putRoleUser(1, $user->toArray());

        if ((int)($user->is_password_changed ?? 0) === 0) {
            Session::put('show_teacher_password_popup', true);
        } else {
            Session::forget('show_teacher_password_popup');
        }

        return redirect(url('user/dashboard'));
    }
}
