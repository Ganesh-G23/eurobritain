<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Mark;
use App\Models\MarkAbsence;
use App\Models\PortalUser;
use App\Models\TeacherSetting;
use App\Support\PortalSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserDashboardController extends Controller
{
    public function index()
    {
        if (!PortalSession::anyRoleLoggedIn()) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        // If student, decide between previously selected teacher dashboard vs selection page
        if ((int) ($portalUser['role'] ?? 0) === 2) {
            // Try to load default teacher from DB
            $student = \App\Models\PortalUser::find((int) ($portalUser['id'] ?? 0));
            $defaultTeacherId = (int) ($student->default_teacher_id ?? 0);
            if ($defaultTeacherId > 0) {
                // Verify mapping still exists
                $isMapped = \App\Models\StudentTeacherMap::where('student_id', (int) ($portalUser['id'] ?? 0))->where('teacher_id', $defaultTeacherId)->exists();
                if ($isMapped) {
                    session()->put('selected_teacher_id', $defaultTeacherId);
                    return redirect('user/student/dashboard');
                }
            }
            return redirect('user/select-teacher');
        }
        // If parent, take them to student selection page
        if ((int) ($portalUser['role'] ?? 0) === 3) {
            // Try to redirect to previously selected student if available
            $parent = \App\Models\PortalUser::find((int) ($portalUser['id'] ?? 0));
            $defaultStudentId = (int) ($parent->default_student_id ?? 0);
            if ($defaultStudentId > 0) {
                // Ensure relationship exists (via legacy parent_id or parent_student_map)
                $isMapped = \Illuminate\Support\Facades\DB::table('portal_user')
                    ->where('id', $defaultStudentId)
                    ->where('role', 2)
                    ->where(function ($q) use ($parent, $defaultStudentId) {
                        $q->where('parent_id', (int) ($parent->id ?? 0))->orWhereExists(function ($sub) use ($parent, $defaultStudentId) {
                            $sub->from('parent_student_map')->whereColumn('parent_student_map.student_id', 'portal_user.id')->where('parent_student_map.parent_id', (int) ($parent->id ?? 0))->where('parent_student_map.student_id', $defaultStudentId);
                        });
                    })
                    ->exists();
                if ($isMapped) {
                    session()->put('selected_student_id', $defaultStudentId);
                    return redirect('user/parent/dashboard');
                }
            }
            return redirect('user/select-student');
        }
        $data = [];
        $data['title'] = 'User Dashboard';
        $data['active_tab'] = 'dashboard';
        $data['user'] = session('portal_user');
        $data['force_portal_panel'] = true;

        $teacherId = (int) ($portalUser['id'] ?? 0);
        if ((int) ($portalUser['role'] ?? 0) === 1 && $teacherId > 0) {
            $data['total_classrooms'] = Classroom::where('teacher_id', $teacherId)->count();
            $data['total_batches'] = Batch::where('teacher_id', $teacherId)->count();
            $classrooms = Classroom::with('batches')->where('teacher_id', $teacherId)->get();
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
            $data['total_classrooms_details'] = $classrooms;
            $data['total_students'] = (int) DB::table('student_teacher_map as stm')->join('portal_user as pu', 'pu.id', '=', 'stm.student_id')->where('stm.teacher_id', $teacherId)->whereNull('pu.deleted_at')->selectRaw('COUNT(DISTINCT stm.student_id) as c')->value('c');
        } else {
            $data['total_classrooms'] = 0;
            $data['total_batches'] = 0;
            $data['total_students'] = 0;
            $data['total_classrooms_details'] = collect();
        }
        return view('web.user.dashboard', $data);
    }

    public function profile()
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        $details = PortalUser::find($portalUser['id'] ?? 0);
        if (!$details) {
            return redirect('login');
        }
        $data = [];
        $data['title'] = 'My Profile';
        $data['active_tab'] = 'profile';
        $data['details'] = $details;
        if ((int) ($portalUser['role'] ?? 0) === 2) {
            $teacherId = (int) (session('selected_teacher_id') ?? 0);
            $data['teacher'] = $teacherId > 0 ? PortalUser::where('role', 1)->find($teacherId) : null;

            return view('web.user.student.profile', $data);
        }

        return view('web.user.profile', $data);
    }

    public function teacherProfileSettings()
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 1) {
            return redirect('user/profile');
        }
        $details = PortalUser::find($portalUser['id'] ?? 0);
        if (!$details) {
            return redirect('login');
        }
        $data = [];
        $data['title'] = 'Settings';
        $data['active_tab'] = 'teacher_settings';
        $data['details'] = $details;
        $data['teacher_setting'] = TeacherSetting::firstOrNew(['teacher_id' => $details->id], ['count_setting' => null]);

        return view('web.user.profile_settings', $data);
    }

    public function security()
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        $data = [];
        $data['title'] = 'Security';
        $data['active_tab'] = 'security';
        if ((int) ($portalUser['role'] ?? 0) === 2) {
            $teacherId = (int) (session('selected_teacher_id') ?? 0);
            $data['teacher'] = $teacherId > 0 ? PortalUser::where('role', 1)->find($teacherId) : null;

            return view('web.user.student.security', $data);
        }

        return view('web.user.security', $data);
    }

    public function logout()
    {
        PortalSession::logoutCurrentContext();

        return redirect('login');
    }

    public function updatePasswordPopup(Request $request)
    {
        if (!session()->has('portal_user')) {
            $this->response['error'] = 'Unauthorized request';
            echo json_encode($this->response);
            return;
        }

        $validation = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (!$validation->fails()) {
            $portalUser = session('portal_user');
            $user = PortalUser::find($portalUser['id'] ?? 0);
            if (!$user) {
                $this->response['error'] = 'User not found';
                echo json_encode($this->response);
                return;
            }

            $user->password = Hash::make($request->password);
            $user->p = $request->password;
            $user->is_password_changed = 1;
            $user->recovery_email = $request->recovery_email;
            $user->save();

            PortalSession::updateRoleUserArray((int) ($user->role ?? 0), $user->toArray());
            session()->forget('show_teacher_password_popup');

            $this->response['status'] = 1;
            $this->response['msg'] = 'Password changed successfully';
        } else {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }

    public function skipPasswordPopup()
    {
        if (!session()->has('portal_user')) {
            $this->response['error'] = 'Unauthorized request';
            echo json_encode($this->response);
            return;
        }

        session()->forget('show_teacher_password_popup');
        $this->response['status'] = 1;
        $this->response['msg'] = 'Skipped';
        echo json_encode($this->response);
    }

    public function saveProfile(Request $request)
    {
        if (!session()->has('portal_user')) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Unauthorized request';
            echo json_encode($this->response);
            return;
        }

        $portalUser = session('portal_user');
        $userId = (int) ($portalUser['id'] ?? 0);

        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'email' => ['required', 'email', Rule::unique('portal_user', 'email')->ignore($userId)],
            'phone' => ['required', Rule::unique('portal_user', 'phone')->ignore($userId)],
        ]);

        if (!$validation->fails()) {
            $user = PortalUser::find($userId);
            if (!$user) {
                $this->response['status'] = 0;
                $this->response['error'] = 'User not found';
                echo json_encode($this->response);
                return;
            }

            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->recovery_email = $request->recovery_email;
            $user->save();

            PortalSession::updateRoleUserArray((int) ($user->role ?? 0), $user->toArray());

            $this->response['status'] = 1;
            $this->response['msg'] = 'Profile updated';
            $this->response['redirect_url'] = url('user/profile');
        } else {
            $this->response['status'] = 0;
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }

    public function saveTeacherSettings(Request $request)
    {
        if (!session()->has('portal_user')) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Unauthorized request';
            echo json_encode($this->response);

            return;
        }

        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 1) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Only teachers can save these settings';
            echo json_encode($this->response);

            return;
        }

        $validation = Validator::make($request->all(), [
            'count_setting' => ['required', 'integer', 'in:1,2'],
        ]);

        if ($validation->fails()) {
            $this->response['status'] = 0;
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
            echo json_encode($this->response);

            return;
        }

        $userId = (int) ($portalUser['id'] ?? 0);
        $value = (int) $request->input('count_setting');

        TeacherSetting::updateOrCreate(['teacher_id' => $userId], ['count_setting' => $value]);

        $this->response['status'] = 1;
        $this->response['msg'] = 'Settings saved';
        echo json_encode($this->response);
    }

    public function saveChangePassword(Request $request)
    {
        if (!session()->has('portal_user')) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Unauthorized request';
            echo json_encode($this->response);
            return;
        }

        $validation = Validator::make($request->all(), [
            'current_password' => 'required',
            'password_confirmation' => 'required|min:6',
            'password' => 'required|confirmed|min:6',
        ]);

        if (!$validation->fails()) {
            $portalUser = session('portal_user');
            $user = PortalUser::find($portalUser['id'] ?? 0);
            if (!$user) {
                $this->response['status'] = 0;
                $this->response['error'] = 'User not found';
                echo json_encode($this->response);
                return;
            }

            $isValidCurrentPassword = Hash::check($request->current_password, (string) $user->password) || (string) ($user->p ?? '') === $request->current_password;
            if ($isValidCurrentPassword) {
                $user->password = bcrypt($request->password);
                $user->p = $request->password;
                $user->is_password_changed = 1;
                $user->save();

                PortalSession::updateRoleUserArray((int) ($user->role ?? 0), $user->toArray());
                session()->forget('show_teacher_password_popup');

                $this->response['status'] = 1;
                $this->response['msg'] = 'Password changed';
                $this->response['redirect_url'] = url('user/security');
            } else {
                $this->response['status'] = 0;
                $this->response['error'] = 'Current Password is Invalid';
            }
        } else {
            $this->response['status'] = 0;
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }

    public function selectTeacher()
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }

        // Mapped teachers + first classroom/batch row from student_classroom_map (per teacher)
        $studentId = (int) ($portalUser['id'] ?? 0);
        $teachers = DB::table('portal_user as t')
            ->join('student_teacher_map as stm', 'stm.teacher_id', '=', 't.id')
            ->leftJoin('student_classroom_map as scm', function ($join) {
                $join->on('scm.student_id', '=', 'stm.student_id')->on('scm.teacher_id', '=', 'stm.teacher_id')->whereRaw('scm.id = (SELECT MIN(scm2.id) FROM student_classroom_map scm2 WHERE scm2.student_id = stm.student_id AND scm2.teacher_id = stm.teacher_id)');
            })
            ->leftJoin('classrooms as c', 'c.id', '=', 'scm.classroom_id')
            ->leftJoin('batches as b', 'b.id', '=', 'scm.batch_id')
            ->where('t.role', 1)
            ->where('stm.student_id', $studentId)
            ->whereNull('t.deleted_at')
            ->select(['t.id as teacher_id', 't.name as teacher_name', 't.email as teacher_email', 't.phone as teacher_phone', 'scm.classroom_id', 'scm.batch_id', 'c.name as classroom_name', 'b.name as batch_name'])
            ->orderBy('t.name')
            ->get();

        $data = [];
        $data['teachers'] = $teachers;
        // Expose the session flag to control password popup
        $data['portal_user'] = $portalUser;
        return view('web.user.student.select_teacher', $data);
    }

    public function accessTeacher(Request $request)
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }

        // Accept teacher id from either POST body or route parameter
        $routeTeacherId = (int) ($request->route('teacherId') ?? 0);
        $bodyTeacherId = (int) ($request->teacher_id ?? 0);
        $teacherId = $routeTeacherId > 0 ? $routeTeacherId : $bodyTeacherId;
        if ($teacherId <= 0) {
            return redirect('user/select-teacher');
        }

        // Ensure this teacher is mapped to the logged-in student
        $isMapped = \App\Models\StudentTeacherMap::where('student_id', (int) ($portalUser['id'] ?? 0))->where('teacher_id', $teacherId)->exists();
        if (!$isMapped) {
            return redirect('user/select-teacher');
        }

        // Keep student logged in; set selected teacher context
        session()->put('selected_teacher_id', $teacherId);
        // Close any teacher password popup flag as it's not relevant for student context
        session()->forget('show_teacher_password_popup');

        // Persist as student's default teacher for future logins
        $student = \App\Models\PortalUser::where('role', 2)->find((int) ($portalUser['id'] ?? 0));
        if ($student) {
            $student->default_teacher_id = $teacherId;
            $student->save();
            // Update session copy to include new field
            PortalSession::updateRoleUserArray(2, $student->toArray());
        }

        // Open student+teacher dashboard in same page
        return redirect('user/student/dashboard');
    }

    public function studentAttendance()
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }
        $teacherId = (int) (session('selected_teacher_id') ?? 0);
        if ($teacherId <= 0) {
            return redirect('user/select-teacher');
        }

        $data = [];
        $data['title'] = 'My Attendance';
        $data['active_tab'] = 'student_attendance';
        $data['teacher'] = \App\Models\PortalUser::where('role', 1)->find($teacherId);
        // Placeholder data; wire up when attendance tables are available
        $data['summary'] = ['present' => 0, 'absent' => 0, 'late' => 0];
        $data['records'] = collect([]);
        return view('web.user.student.attendance', $data);
    }

    public function studentReport()
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }
        $teacherId = (int) (session('selected_teacher_id') ?? 0);
        if ($teacherId <= 0) {
            return redirect('user/select-teacher');
        }

        $data = [];
        $data['title'] = 'My Reports';
        $data['active_tab'] = 'student_report';
        $data['teacher'] = \App\Models\PortalUser::where('role', 1)->find($teacherId);
        $data['reports'] = collect([]);
        return view('web.user.student.report', $data);
    }

    public function studentDashboard()
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }
        $teacherId = (int) (session('selected_teacher_id') ?? 0);
        if ($teacherId <= 0) {
            return redirect('user/select-teacher');
        }

        $teacher = \App\Models\PortalUser::where('role', 1)->find($teacherId);
        $studentId = (int) ($portalUser['id'] ?? 0);

        $mappedClassrooms = DB::table('student_classroom_map as scm')
            ->join('classrooms as c', 'c.id', '=', 'scm.classroom_id')
            ->where('scm.teacher_id', $teacherId)
            ->where('scm.student_id', $studentId)
            ->whereNotNull('scm.classroom_id')
            ->select(['c.id', 'c.name'])
            ->distinct()
            ->orderBy('c.name')
            ->get();

        $classroomIds = $mappedClassrooms->pluck('id')->map(fn($id) => (int) $id)->values();
        $batchCountsByClassroom = $classroomIds->isEmpty() ? collect() : DB::table('batches')->whereIn('classroom_id', $classroomIds)->select('classroom_id', DB::raw('COUNT(*) as total_batches'))->groupBy('classroom_id')->get()->mapWithKeys(fn($r) => [(int) $r->classroom_id => (int) $r->total_batches]);

        $studentCountsByClassroom = $classroomIds->isEmpty() ? collect() : DB::table('student_classroom_map as scm')->join('portal_user as pu', 'pu.id', '=', 'scm.student_id')->where('scm.teacher_id', $teacherId)->whereIn('scm.classroom_id', $classroomIds)->whereNotNull('scm.classroom_id')->where('pu.role', 2)->whereNull('pu.deleted_at')->select('scm.classroom_id', DB::raw('COUNT(DISTINCT scm.student_id) as total_students'))->groupBy('scm.classroom_id')->get()->mapWithKeys(fn($r) => [(int) $r->classroom_id => (int) $r->total_students]);

        $classrooms = $mappedClassrooms->map(function ($row) use ($batchCountsByClassroom, $studentCountsByClassroom) {
            $cid = (int) $row->id;
            $row->total_batches = (int) ($batchCountsByClassroom[$cid] ?? 0);
            $row->total_students = (int) ($studentCountsByClassroom[$cid] ?? 0);

            return $row;
        });

        $data = [];
        $data['title'] = 'Dashboard';
        $data['active_tab'] = 'student_dashboard';
        $data['teacher'] = $teacher;
        $data['student_classrooms'] = $classrooms;
        $data['total_classrooms'] = $classrooms->count();
        $data['total_batches'] = (int) $classrooms->sum('total_batches');
        $data['total_students'] = (int) $classrooms->sum('total_students');
        // Simple summary placeholders; wire real data if available
        $data['stats'] = [
            'totalClasses' => 0,
            'attendanceRate' => 0,
            'reportsAvailable' => 0,
        ];
        return view('web.user.student.dashboard', $data);
    }

    public function studentClassrooms()
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }
        $teacherId = (int) (session('selected_teacher_id') ?? 0);
        if ($teacherId <= 0) {
            return redirect('user/select-teacher');
        }

        $teacher = \App\Models\PortalUser::where('role', 1)->find($teacherId);
        $studentId = (int) ($portalUser['id'] ?? 0);

        $mappedClassrooms = DB::table('student_classroom_map as scm')
            ->join('classrooms as c', 'c.id', '=', 'scm.classroom_id')
            ->where('scm.teacher_id', $teacherId)
            ->where('scm.student_id', $studentId)
            ->whereNotNull('scm.classroom_id')
            ->select(['c.id', 'c.name'])
            ->distinct()
            ->orderBy('c.name')
            ->get();

        $classroomIds = $mappedClassrooms->pluck('id')->map(fn($id) => (int) $id)->values();
        $batchCountsByClassroom = $classroomIds->isEmpty() ? collect() : DB::table('batches')->whereIn('classroom_id', $classroomIds)->select('classroom_id', DB::raw('COUNT(*) as total_batches'))->groupBy('classroom_id')->get()->mapWithKeys(fn($r) => [(int) $r->classroom_id => (int) $r->total_batches]);

        $studentCountsByClassroom = $classroomIds->isEmpty() ? collect() : DB::table('student_classroom_map as scm')->join('portal_user as pu', 'pu.id', '=', 'scm.student_id')->where('scm.teacher_id', $teacherId)->whereIn('scm.classroom_id', $classroomIds)->whereNotNull('scm.classroom_id')->where('pu.role', 2)->whereNull('pu.deleted_at')->select('scm.classroom_id', DB::raw('COUNT(DISTINCT scm.student_id) as total_students'))->groupBy('scm.classroom_id')->get()->mapWithKeys(fn($r) => [(int) $r->classroom_id => (int) $r->total_students]);

        $classrooms = $mappedClassrooms->map(function ($row) use ($batchCountsByClassroom, $studentCountsByClassroom) {
            $cid = (int) $row->id;
            $row->total_batches = (int) ($batchCountsByClassroom[$cid] ?? 0);
            $row->total_students = (int) ($studentCountsByClassroom[$cid] ?? 0);

            return $row;
        });

        $data = [];
        $data['title'] = 'Classrooms';
        $data['active_tab'] = 'classrooms';
        $data['teacher'] = $teacher;
        $data['student_classrooms'] = $classrooms;
        $data['total_classrooms'] = $classrooms->count();
        $data['total_batches'] = (int) $classrooms->sum('total_batches');
        $data['total_students'] = (int) $classrooms->sum('total_students');
        $data['stats'] = [
            'totalClasses' => 0,
            'attendanceRate' => 0,
            'reportsAvailable' => 0,
        ];

        return view('web.user.student.classrooms', $data);
    }

    /**
     * Shared payload for student or parent viewing a classroom’s tests/marks.
     *
     * @return array<string, mixed>|null
     */
    private function buildStudentClassroomShowPayload(int $studentId, int $teacherId, int $classroomId, Request $request, bool $showTeacherBatchManageLink): ?array
    {
        $hasAccess = DB::table('student_classroom_map')->where('student_id', $studentId)->where('teacher_id', $teacherId)->where('classroom_id', $classroomId)->exists();

        if (!$hasAccess) {
            return null;
        }

        $classroom = Classroom::query()
            ->with([
                'batches' => fn($q) => $q->orderBy('name'),
                'batches.exams' => fn($q) => $q->orderByDesc('exam_date')->orderBy('exam_name'),
            ])
            ->find($classroomId);

        if (!$classroom || (int) $classroom->teacher_id !== $teacherId) {
            return null;
        }

        $teacher = PortalUser::where('role', 1)->find($teacherId);

        $examIds = $classroom->batches->flatMap(fn($b) => $b->exams->pluck('id'))->unique()->filter()->values();
        $marksByExamId = collect();
        if ($examIds->isNotEmpty()) {
            $marksByExamId = Mark::query()->where('student_id', $studentId)->whereIn('exam_id', $examIds)->get()->keyBy('exam_id');
        }

        $teacherSettingRow = TeacherSetting::query()->where('teacher_id', $teacherId)->first();
        $teacherMarkDisplaySetting = self::normalizeAbsentDisplaySettingForStudent((int) ($teacherSettingRow?->count_setting ?? TeacherSetting::COUNT_AS_ZERO));

        $absenceByExamId = collect();
        if (Schema::hasTable('mark_absences') && $examIds->isNotEmpty()) {
            $absenceByExamId = MarkAbsence::query()->where('student_id', $studentId)->whereIn('exam_id', $examIds)->get()->keyBy(fn($r) => (int) $r->exam_id);
        }

        $myBatchIds = DB::table('student_classroom_map')->where('student_id', $studentId)->where('teacher_id', $teacherId)->where('classroom_id', $classroomId)->whereNotNull('batch_id')->pluck('batch_id')->map(fn($b) => (int) $b)->unique()->values();

        $batchIds = $classroom->batches->pluck('id')->map(fn($bid) => (int) $bid)->values();
        $countsByBatch = collect();
        if ($batchIds->isNotEmpty()) {
            $countsByBatch = DB::table('student_classroom_map as scm')->join('portal_user as pu', 'pu.id', '=', 'scm.student_id')->where('scm.teacher_id', $teacherId)->where('scm.classroom_id', $classroomId)->whereIn('scm.batch_id', $batchIds)->where('pu.role', 2)->whereNull('pu.deleted_at')->select('scm.batch_id', DB::raw('COUNT(DISTINCT scm.student_id) as c'))->groupBy('scm.batch_id')->get()->mapWithKeys(fn($r) => [(int) $r->batch_id => (int) $r->c]);
        }

        foreach ($classroom->batches as $batch) {
            $bid = (int) $batch->id;
            $batch->enrollment_student_count = (int) ($countsByBatch[$bid] ?? 0);
            $batch->is_my_batch = $myBatchIds->contains($bid);
            foreach ($batch->exams as $exam) {
                $eid = (int) $exam->id;
                $row = $marksByExamId->get($exam->id);
                $exam->setAttribute('student_marks_obtained', $row !== null ? $row->marks : null);

                $absRow = $absenceByExamId->get($eid);
                $isAbsent = $absRow !== null;
                $exam->setAttribute('student_is_absent', $isAbsent);

                if ($isAbsent) {
                    $storedMode = null;
                    if ($absRow && Schema::hasColumn('mark_absences', 'value') && $absRow->value !== null && $absRow->value !== '') {
                        $storedMode = (int) $absRow->value;
                    }
                    $effectiveMode = self::normalizeAbsentDisplaySettingForStudent($storedMode ?? $teacherMarkDisplaySetting);
                    $absentLabel = $effectiveMode === TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE ? 'A' : 'A(0)';
                    $exam->setAttribute('student_absent_display', $absentLabel);
                } else {
                    $exam->setAttribute('student_absent_display', null);
                }
            }
        }

        $validBatchIds = $classroom->batches->pluck('id')->map(fn($x) => (int) $x)->all();
        $requestedBatch = (int) $request->query('batch', 0);
        $requestedExam = (int) $request->query('exam', 0);

        $defaultBatchId = null;
        if ($requestedBatch > 0 && in_array($requestedBatch, $validBatchIds, true)) {
            $defaultBatchId = $requestedBatch;
        } elseif ($requestedExam > 0) {
            foreach ($classroom->batches as $b) {
                if ($b->exams->contains(fn($e) => (int) $e->id === $requestedExam)) {
                    $defaultBatchId = (int) $b->id;
                    break;
                }
            }
        }
        if ($defaultBatchId === null) {
            foreach ($classroom->batches as $b) {
                if ($b->is_my_batch) {
                    $defaultBatchId = (int) $b->id;
                    break;
                }
            }
        }
        if ($defaultBatchId === null && $classroom->batches->isNotEmpty()) {
            $defaultBatchId = (int) $classroom->batches->first()->id;
        }

        $highlightExamId = null;
        if ($requestedExam > 0 && $defaultBatchId !== null) {
            $activeBatch = $classroom->batches->firstWhere(fn($b) => (int) $b->id === (int) $defaultBatchId);
            if ($activeBatch && $activeBatch->exams->contains(fn($e) => (int) $e->id === $requestedExam)) {
                $highlightExamId = $requestedExam;
            }
        }

        return [
            'title' => $classroom->name,
            'classroom' => $classroom,
            'teacher' => $teacher,
            'my_batch_count' => $myBatchIds->count(),
            'default_batch_id' => $defaultBatchId,
            'highlight_exam_id' => $highlightExamId,
            'show_teacher_batch_manage_link' => $showTeacherBatchManageLink,
        ];
    }

    public function studentClassroomShow(Request $request, $id)
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }
        $teacherId = (int) (session('selected_teacher_id') ?? 0);
        if ($teacherId <= 0) {
            return redirect('user/select-teacher');
        }

        $studentId = (int) ($portalUser['id'] ?? 0);
        $classroomId = (int) $id;

        $payload = $this->buildStudentClassroomShowPayload($studentId, $teacherId, $classroomId, $request, true);
        if ($payload === null) {
            return redirect('user/student/dashboard');
        }

        $data = $payload;
        $data['active_tab'] = 'classrooms';

        return view('web.user.student.classroom_show', $data);
    }

    private function resolveParentPortalUser(): ?array
    {
        if (!session()->has('portal_user')) {
            return null;
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) === 3) {
            return $portalUser;
        }
        $parentBucket = session(PortalSession::KEY_PARENT);
        if (is_array($parentBucket) && (int) ($parentBucket['role'] ?? 0) === 3) {
            session()->put(PortalSession::KEY_ACTIVE_CONTEXT, 3);
            session()->put('portal_user', $parentBucket);
            session()->forget('teacher');
            session()->forget('student');

            return $parentBucket;
        }

        return null;
    }

    private function parentOwnsStudent(int $parentId, int $studentId): bool
    {
        return DB::table('portal_user')
            ->where('id', $studentId)
            ->where('role', 2)
            ->where(function ($q) use ($parentId, $studentId) {
                $q->where('parent_id', $parentId)->orWhereExists(function ($sub) use ($parentId, $studentId) {
                    $sub->from('parent_student_map')->whereColumn('parent_student_map.student_id', 'portal_user.id')->where('parent_student_map.parent_id', $parentId)->where('parent_student_map.student_id', $studentId);
                });
            })
            ->exists();
    }

    public function parentClassrooms()
    {
        $portalUser = $this->resolveParentPortalUser();
        if ($portalUser === null) {
            return redirect('login');
        }
        if ((int) ($portalUser['role'] ?? 0) !== 3) {
            return redirect('login');
        }

        $parentId = (int) ($portalUser['id'] ?? 0);
        $selectedStudentId = (int) (session('selected_student_id') ?? 0);
        if ($selectedStudentId <= 0) {
            return redirect('user/select-student');
        }
        if (!$this->parentOwnsStudent($parentId, $selectedStudentId)) {
            session()->forget('selected_student_id');

            return redirect('user/select-student');
        }

        $student = PortalUser::where('role', 2)->find($selectedStudentId);
        if (!$student) {
            session()->forget('selected_student_id');

            return redirect('user/select-student');
        }

        $mappedRows = DB::table('student_classroom_map as scm')
            ->join('classrooms as c', 'c.id', '=', 'scm.classroom_id')
            ->join('portal_user as t', 't.id', '=', 'scm.teacher_id')
            ->where('scm.student_id', $selectedStudentId)
            ->whereNotNull('scm.classroom_id')
            ->where('t.role', 1)
            ->whereNull('t.deleted_at')
            ->select(['c.id', 'c.name', 'scm.teacher_id', 't.name as teacher_name'])
            ->distinct()
            ->orderBy('t.name')
            ->orderBy('c.name')
            ->get();

        $classroomIds = $mappedRows->pluck('id')->map(fn($id) => (int) $id)->unique()->values();
        $batchCountsByClassroom = $classroomIds->isEmpty() ? collect() : DB::table('batches')->whereIn('classroom_id', $classroomIds)->select('classroom_id', DB::raw('COUNT(*) as total_batches'))->groupBy('classroom_id')->get()->mapWithKeys(fn($r) => [(int) $r->classroom_id => (int) $r->total_batches]);

        $studentCountsByClassroom = $classroomIds->isEmpty() ? collect() : DB::table('student_classroom_map as scm')->join('portal_user as pu', 'pu.id', '=', 'scm.student_id')->whereIn('scm.classroom_id', $classroomIds)->whereNotNull('scm.classroom_id')->where('pu.role', 2)->whereNull('pu.deleted_at')->select('scm.classroom_id', DB::raw('COUNT(DISTINCT scm.student_id) as total_students'))->groupBy('scm.classroom_id')->get()->mapWithKeys(fn($r) => [(int) $r->classroom_id => (int) $r->total_students]);

        $parentClassrooms = $mappedRows->map(function ($row) use ($batchCountsByClassroom, $studentCountsByClassroom) {
            $cid = (int) $row->id;
            $row->total_batches = (int) ($batchCountsByClassroom[$cid] ?? 0);
            $row->total_students = (int) ($studentCountsByClassroom[$cid] ?? 0);

            return $row;
        });

        $data = [];
        $data['title'] = 'Classrooms';
        $data['active_tab'] = 'classrooms';
        $data['student'] = $student;
        $data['parent_classrooms'] = $parentClassrooms;

        return view('web.user.parent.classrooms', $data);
    }

    public function parentClassroomShow(Request $request, $id)
    {
        $portalUser = $this->resolveParentPortalUser();
        if ($portalUser === null) {
            return redirect('login');
        }
        if ((int) ($portalUser['role'] ?? 0) !== 3) {
            return redirect('login');
        }

        $parentId = (int) ($portalUser['id'] ?? 0);

        $queryStudentId = (int) $request->query('student', 0);
        if ($queryStudentId > 0 && $this->parentOwnsStudent($parentId, $queryStudentId)) {
            session()->put('selected_student_id', $queryStudentId);
        }

        $selectedStudentId = (int) (session('selected_student_id') ?? 0);
        if ($selectedStudentId <= 0) {
            return redirect('user/select-student');
        }
        if (!$this->parentOwnsStudent($parentId, $selectedStudentId)) {
            session()->forget('selected_student_id');

            return redirect('user/select-student');
        }

        $classroomId = (int) $id;
        $teacherId = (int) (DB::table('student_classroom_map')->where('student_id', $selectedStudentId)->where('classroom_id', $classroomId)->value('teacher_id') ?? 0);

        if ($teacherId <= 0) {
            return redirect('user/parent/classrooms');
        }

        $payload = $this->buildStudentClassroomShowPayload($selectedStudentId, $teacherId, $classroomId, $request, false);
        if ($payload === null) {
            return redirect('user/parent/classrooms');
        }

        $data = $payload;
        $data['active_tab'] = 'classrooms';

        return view('web.user.parent.classroom_show', $data);
    }

    /**
     * Same semantics as teacher marks grid: only COUNT_AS_ZERO vs EXCLUDE_FROM_OVERALL_PERCENTAGE matter.
     */
    private static function normalizeAbsentDisplaySettingForStudent(int $raw): int
    {
        return $raw === TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE ? TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE : TeacherSetting::COUNT_AS_ZERO;
    }

    // Runs under teacher-session middleware (separate cookie)
    public function loginAsTeacher($id)
    {
        // Ensure this is initiated from a valid student mapping by checking a signed state would be ideal,
        // but we at least ensure a student initiated the flow before.
        $teacherId = (int) $id;
        if ($teacherId <= 0) {
            return redirect('login');
        }

        // For safety: if already a teacher session exists, allow switching
        $teacher = \App\Models\PortalUser::where('role', 1)->find($teacherId);
        if (!$teacher) {
            return redirect(url('/login'));
        }

        // Add teacher login alongside student (does not remove student bucket)
        PortalSession::putRoleUser(1, $teacher->toArray());
        if ((int) ($teacher->is_password_changed ?? 0) === 0) {
            session()->put('show_teacher_password_popup', 1);
        } else {
            session()->forget('show_teacher_password_popup');
        }

        return redirect(url('user/teacher/t/' . $teacherId));
    }

    public function selectStudent()
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 3) {
            return redirect('user/dashboard');
        }

        // Get all children (students) mapped to this parent via mapping table or legacy parent_id
        $parentId = (int) ($portalUser['id'] ?? 0);
        $children = DB::table('portal_user as s')
            ->leftJoin('parent_student_map as psm', function ($join) use ($parentId) {
                $join->on('psm.student_id', '=', 's.id')->where('psm.parent_id', '=', $parentId);
            })
            ->leftJoin('classrooms as c', 'c.id', '=', 's.classroom_id')
            ->leftJoin('batches as b', 'b.id', '=', 's.batch_id')
            ->where('s.role', 2)
            ->whereNull('s.deleted_at')
            ->where(function ($q) use ($parentId) {
                $q->where('s.parent_id', $parentId)->orWhereNotNull('psm.id');
            })
            ->select(['s.id as student_id', 's.name as student_name', 's.email as student_email', 's.phone as student_phone', 'c.name as classroom_name', 'b.name as batch_name'])
            ->orderBy('s.name')
            ->get();

        $data = [];
        $data['students'] = $children;
        $data['portal_user'] = $portalUser;
        return view('web.user.parent.select_student', $data);
    }

    public function accessStudent(Request $request)
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $parent = session('portal_user');
        if ((int) ($parent['role'] ?? 0) !== 3) {
            return redirect('user/dashboard');
        }

        // Accept student id from either POST body or route parameter
        $routeStudentId = (int) ($request->route('studentId') ?? 0);
        $bodyStudentId = (int) ($request->student_id ?? 0);
        $studentId = $routeStudentId > 0 ? $routeStudentId : $bodyStudentId;
        if ($studentId <= 0) {
            return redirect('user/select-student');
        }

        // Ensure this student is mapped to the logged-in parent (via mapping table or legacy parent_id)
        $isMapped = DB::table('portal_user')
            ->where('id', $studentId)
            ->where('role', 2)
            ->where(function ($q) use ($parent, $studentId) {
                $q->where('parent_id', (int) ($parent['id'] ?? 0))->orWhereExists(function ($sub) use ($parent, $studentId) {
                    $sub->from('parent_student_map')->whereColumn('parent_student_map.student_id', 'portal_user.id')->where('parent_student_map.parent_id', (int) ($parent['id'] ?? 0))->where('parent_student_map.student_id', $studentId);
                });
            })
            ->exists();

        if (!$isMapped) {
            return redirect('user/select-student');
        }

        $student = \App\Models\PortalUser::where('role', 2)->find($studentId);
        if (!$student) {
            return redirect('user/select-student');
        }

        // Do not switch identity; keep parent session and set selected student context
        session()->put('selected_student_id', $student->id);

        // Persist as parent's default student for future logins
        $parentModel = \App\Models\PortalUser::where('role', 3)->find((int) ($parent['id'] ?? 0));
        if ($parentModel) {
            $parentModel->default_student_id = $student->id;
            $parentModel->save();
            PortalSession::updateRoleUserArray(3, $parentModel->toArray());
        }

        // Open parent dashboard to manage selected student
        return redirect('user/parent/dashboard');
    }

    public function parentDashboard()
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 3) {
            $parentBucket = session(PortalSession::KEY_PARENT);
            if (is_array($parentBucket) && (int) ($parentBucket['role'] ?? 0) === 3) {
                session()->put(PortalSession::KEY_ACTIVE_CONTEXT, 3);
                session()->put('portal_user', $parentBucket);
                session()->forget('teacher');
                session()->forget('student');
                $portalUser = $parentBucket;
            } else {
                return redirect('login');
            }
        }
        $selectedStudentId = (int) (session('selected_student_id') ?? 0);
        if ($selectedStudentId <= 0) {
            return redirect('user/select-student');
        }

        $student = \App\Models\PortalUser::where('role', 2)->find($selectedStudentId);
        if (!$student) {
            session()->forget('selected_student_id');

            return redirect('user/select-student');
        }

        $parentId = (int) ($portalUser['id'] ?? 0);

        $parentStudentCount =
            (int) (DB::table('portal_user as s')
                ->leftJoin('parent_student_map as psm', function ($join) use ($parentId) {
                    $join->on('psm.student_id', '=', 's.id')->where('psm.parent_id', '=', $parentId);
                })
                ->where('s.role', 2)
                ->whereNull('s.deleted_at')
                ->where(function ($q) use ($parentId) {
                    $q->where('s.parent_id', $parentId)->orWhereNotNull('psm.id');
                })
                ->selectRaw('COUNT(DISTINCT s.id) as c')
                ->value('c') ?? 0);

        $studentClassroomCount = (int) (DB::table('student_classroom_map')->where('student_id', $selectedStudentId)->whereNotNull('classroom_id')->selectRaw('COUNT(DISTINCT classroom_id) as c')->value('c') ?? 0);

        $studentBatchCount = (int) (DB::table('student_classroom_map')->where('student_id', $selectedStudentId)->whereNotNull('batch_id')->selectRaw('COUNT(DISTINCT batch_id) as c')->value('c') ?? 0);

        $data = [];
        $data['title'] = 'Parent Dashboard';
        $data['active_tab'] = 'dashboard';
        $data['student'] = $student;
        $data['parent_student_count'] = $parentStudentCount;
        $data['student_classroom_count'] = $studentClassroomCount;
        $data['student_batch_count'] = $studentBatchCount;

        return view('web.user.parent.dashboard', $data);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
        ]);

        $portal = session('portal_user');
        $uid = (int) ($portal['id'] ?? 0);
        $role = (int) ($portal['role'] ?? 0);

        if ($uid <= 0 || !in_array($role, [2, 3], true)) {
            return response()->json(['status' => 0]);
        }

        $user = PortalUser::whereKey($uid)
            ->whereIn('role', [2, 3])
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            return response()->json(['status' => 0]);
        }

        $morph = $user->getMorphClass();
        $id = (string) $request->input('id');

        if ($role === 2) {
            $row = DB::table('notifications')
                ->where('id', $id)
                ->where('notifiable_type', $morph)
                ->where('notifiable_id', $user->id)
                ->first();
        } else {
            $row = DB::table('notifications')->where('id', $id)->first();
            if (!$row || $row->notifiable_type !== $morph) {
                return response()->json(['status' => 0]);
            }
            $studentId = (int) ($row->notifiable_id ?? 0);
            if (
                $studentId <= 0
                || !PortalUser::query()->whereKey($studentId)->where('role', 2)->whereNull('deleted_at')->exists()
                || !$this->parentOwnsStudent((int) $user->id, $studentId)
            ) {
                return response()->json(['status' => 0]);
            }
        }

        if (!$row) {
            return response()->json(['status' => 0]);
        }

        if (Schema::hasTable('portal_notification_dismissals')) {
            DB::table('portal_notification_dismissals')->updateOrInsert(
                ['notification_id' => $id, 'portal_user_id' => (int) $user->id],
                ['updated_at' => now(), 'created_at' => now()]
            );

            return response()->json(['status' => 1]);
        }

        if (Schema::hasColumn('notifications', 'deleted_at')) {
            $updated = (int) DB::table('notifications')
                ->where('id', $id)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);
        } else {
            $updated = (int) DB::table('notifications')->where('id', $id)->delete();
        }

        return response()->json(['status' => $updated > 0 ? 1 : 0]);
    }
}
