<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Event;
use App\Models\EventType;
use App\Models\LeaveRequests;
use App\Models\Mark;
use App\Models\MarkAbsence;
use App\Models\PortalUser;
use App\Models\StudentAttendance;
use App\Models\StudentPersonalEvent;
use App\Models\StudentTeacherMap;
use App\Models\TeacherSetting;
use App\Support\FullCalendarEventPayload;
use App\Support\PortalSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserDashboardController extends Controller
{
    public function index()
    {
        if (! PortalSession::anyRoleLoggedIn()) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        // If student, decide between previously selected teacher dashboard vs selection page
        if ((int) ($portalUser['role'] ?? 0) === 2) {
            // Try to load default teacher from DB
            $student = PortalUser::find((int) ($portalUser['id'] ?? 0));
            $defaultTeacherId = (int) ($student->default_teacher_id ?? 0);
            if ($defaultTeacherId > 0) {
                // Verify mapping still exists
                $studentId = (int) ($portalUser['id'] ?? 0);
                $isMapped = StudentTeacherMap::where('student_id', $studentId)->where('teacher_id', $defaultTeacherId)->exists();
                if ($isMapped) {
                    [$tid] = $this->alignStudentPortalTeacherWithEnrollments($studentId, $defaultTeacherId);
                    session()->put('selected_teacher_id', $tid);

                    return redirect('user/student/dashboard');
                }
            }

            return redirect('user/select-teacher');
        }
        // If parent, take them to student selection page
        if ((int) ($portalUser['role'] ?? 0) === 3) {
            // Try to redirect to previously selected student if available
            $parent = PortalUser::find((int) ($portalUser['id'] ?? 0));
            $defaultStudentId = (int) ($parent->default_student_id ?? 0);
            if ($defaultStudentId > 0) {
                // Ensure relationship exists (via legacy parent_id or parent_student_map)
                $isMapped = DB::table('portal_user')
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
        if (! session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        $details = PortalUser::find($portalUser['id'] ?? 0);
        if (! $details) {
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
        if (! session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 1) {
            return redirect('user/profile');
        }
        $details = PortalUser::find($portalUser['id'] ?? 0);
        if (! $details) {
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
        if (! session()->has('portal_user')) {
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
        if (! session()->has('portal_user')) {
            $this->response['error'] = 'Unauthorized request';
            echo json_encode($this->response);

            return;
        }

        $validation = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
        ]);

        if (! $validation->fails()) {
            $portalUser = session('portal_user');
            $user = PortalUser::find($portalUser['id'] ?? 0);
            if (! $user) {
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
        if (! session()->has('portal_user')) {
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
        if (! session()->has('portal_user')) {
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

        if (! $validation->fails()) {
            $user = PortalUser::find($userId);
            if (! $user) {
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

    public function saveEmailTwoFactor(Request $request)
    {
        if (! session()->has('portal_user')) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Unauthorized request';
            echo json_encode($this->response);

            return;
        }

        $portalUser = session('portal_user');
        $role = (int) ($portalUser['role'] ?? 0);
        if (! in_array($role, [1, 2], true)) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Email sign-in codes are only available for teachers and students.';
            echo json_encode($this->response);

            return;
        }

        $validation = Validator::make($request->all(), [
            'current_password' => 'required|string',
        ]);

        if ($validation->fails()) {
            $this->response['status'] = 0;
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
            echo json_encode($this->response);

            return;
        }

        $user = PortalUser::find((int) ($portalUser['id'] ?? 0));
        if (! $user) {
            $this->response['status'] = 0;
            $this->response['error'] = 'User not found';
            echo json_encode($this->response);

            return;
        }

        $isValidCurrentPassword = Hash::check($request->current_password, (string) $user->password)
            || (string) ($user->p ?? '') === $request->current_password;
        if (! $isValidCurrentPassword) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Current password is incorrect.';
            echo json_encode($this->response);

            return;
        }

        $enabled = $request->boolean('email_two_factor_enabled');
        if ($enabled && ! filled($user->email)) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Add an email address to your profile before enabling email sign-in codes.';
            echo json_encode($this->response);

            return;
        }

        $user->email_two_factor_enabled = $enabled;
        $user->save();

        PortalSession::updateRoleUserArray($role, $user->toArray());

        $this->response['status'] = 1;
        $this->response['msg'] = $enabled
            ? 'Email verification on sign-in is enabled.'
            : 'Email verification on sign-in is disabled.';
        echo json_encode($this->response);
    }

    public function saveTeacherSettings(Request $request)
    {
        if (! session()->has('portal_user')) {
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
        if (! session()->has('portal_user')) {
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

        if (! $validation->fails()) {
            $portalUser = session('portal_user');
            $user = PortalUser::find($portalUser['id'] ?? 0);
            if (! $user) {
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
        if (! session()->has('portal_user')) {
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

        foreach ($teachers as $row) {
            if ((int) ($row->classroom_id ?? 0) > 0) {
                continue;
            }
            $legacy = $this->legacyEnrollmentForStudentTeacher($studentId, (int) $row->teacher_id);
            if ($legacy === null) {
                continue;
            }
            $row->classroom_id = $legacy['classroom_id'];
            $row->batch_id = $legacy['batch_id'];
            $row->classroom_name = DB::table('classrooms')->where('id', $legacy['classroom_id'])->value('name');
            $row->batch_name = $legacy['batch_id'] !== null
                ? DB::table('batches')->where('id', $legacy['batch_id'])->value('name')
                : null;
        }

        $data = [];
        $data['teachers'] = $teachers;
        // Expose the session flag to control password popup
        $data['portal_user'] = $portalUser;

        return view('web.user.student.select_teacher', $data);
    }

    public function accessTeacher(Request $request)
    {
        if (! session()->has('portal_user')) {
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
        $isMapped = StudentTeacherMap::where('student_id', (int) ($portalUser['id'] ?? 0))->where('teacher_id', $teacherId)->exists();
        if (! $isMapped) {
            return redirect('user/select-teacher');
        }

        // Keep student logged in; set selected teacher context
        session()->put('selected_teacher_id', $teacherId);
        // Close any teacher password popup flag as it's not relevant for student context
        session()->forget('show_teacher_password_popup');

        // Persist as student's default teacher for future logins
        $student = PortalUser::where('role', 2)->find((int) ($portalUser['id'] ?? 0));
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
        if (! session()->has('portal_user')) {
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
        [$teacherId] = $this->alignStudentPortalTeacherWithEnrollments($studentId, $teacherId);
        $data = [];
        $data['title'] = 'My Attendance';
        $data['active_tab'] = 'student_attendance';
        $data['teacher'] = PortalUser::where('role', 1)->find($teacherId);
        $data['student_id'] = $studentId;
        $data['student_display_name'] = (string) ($portalUser['name'] ?? '');

        $data['attendance_table_ready'] = Schema::hasTable('student_attendances');
        $data['summary'] = ['present' => 0, 'absent' => 0, 'late' => 0];
        $data['attendance_batches'] = collect();

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
                'c.name as classroom_name',
            ])
            ->distinct()
            ->orderBy('c.name')
            ->orderBy('b.name')
            ->get();

        if ($batchRows->isEmpty()) {
            $legacy = $this->legacyEnrollmentForStudentTeacher($studentId, $teacherId);
            if ($legacy !== null) {
                if ($legacy['batch_id'] !== null) {
                    $batchRows = DB::table('batches as b')
                        ->join('classrooms as c', 'c.id', '=', 'b.classroom_id')
                        ->where('b.id', $legacy['batch_id'])
                        ->where('c.teacher_id', $teacherId)
                        ->whereNull('b.deleted_at')
                        ->select([
                            'b.id as batch_id',
                            'b.name as batch_name',
                            'c.name as classroom_name',
                        ])
                        ->get();
                } else {
                    $batchRows = DB::table('batches as b')
                        ->join('classrooms as c', 'c.id', '=', 'b.classroom_id')
                        ->where('b.classroom_id', $legacy['classroom_id'])
                        ->where('c.teacher_id', $teacherId)
                        ->whereNull('b.deleted_at')
                        ->select([
                            'b.id as batch_id',
                            'b.name as batch_name',
                            'c.name as classroom_name',
                        ])
                        ->orderBy('c.name')
                        ->orderBy('b.name')
                        ->get();
                }
            }
        }

        if (! $data['attendance_table_ready'] || $batchRows->isEmpty()) {
            return view('web.user.student.attendance', $data);
        }

        $batchIds = $batchRows->pluck('batch_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $allRows = StudentAttendance::query()
            ->whereIn('batch_id', $batchIds)
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

        $batchesOut = collect();
        foreach ($batchRows as $br) {
            $bid = (int) $br->batch_id;
            $keys = array_keys($dateKeysByBatch[$bid] ?? []);
            sort($keys, SORT_STRING);
            $cells = [];
            foreach ($keys as $d) {
                $st = $studentStatusByBatchDate[$bid][$d] ?? '';
                $cells[$d] = $st;
                if ($st === 'P') {
                    $data['summary']['present']++;
                } elseif ($st === 'A') {
                    $data['summary']['absent']++;
                } elseif ($st === 'L') {
                    $data['summary']['late']++;
                }
            }
            $batchesOut->push((object) [
                'id' => $bid,
                'batch_name' => (string) $br->batch_name,
                'classroom_name' => (string) $br->classroom_name,
                'dates' => $keys,
                'cells' => $cells,
            ]);
        }
        $data['attendance_batches'] = $batchesOut;

        return view('web.user.student.attendance', $data);
    }

    public function studentReport()
    {
        if (! session()->has('portal_user')) {
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
        $data['teacher'] = PortalUser::where('role', 1)->find($teacherId);
        $data['reports'] = collect([]);

        return view('web.user.student.report', $data);
    }

    public function studentDashboard()
    {
        if (! session()->has('portal_user')) {
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
        [$teacherId, $mappedClassrooms] = $this->alignStudentPortalTeacherWithEnrollments($studentId, $teacherId);
        $teacher = PortalUser::where('role', 1)->find($teacherId);

        $classroomIds = $mappedClassrooms->pluck('id')->map(fn ($id) => (int) $id)->values();
        $batchCountsByClassroom = $classroomIds->isEmpty() ? collect() : DB::table('batches')->whereIn('classroom_id', $classroomIds)->select('classroom_id', DB::raw('COUNT(*) as total_batches'))->groupBy('classroom_id')->get()->mapWithKeys(fn ($r) => [(int) $r->classroom_id => (int) $r->total_batches]);

        $studentCountsByClassroom = $classroomIds->isEmpty() ? collect() : DB::table('student_classroom_map as scm')->join('portal_user as pu', 'pu.id', '=', 'scm.student_id')->where('scm.teacher_id', $teacherId)->whereIn('scm.classroom_id', $classroomIds)->whereNotNull('scm.classroom_id')->where('pu.role', 2)->whereNull('pu.deleted_at')->select('scm.classroom_id', DB::raw('COUNT(DISTINCT scm.student_id) as total_students'))->groupBy('scm.classroom_id')->get()->mapWithKeys(fn ($r) => [(int) $r->classroom_id => (int) $r->total_students]);

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
        if (! session()->has('portal_user')) {
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
        [$teacherId, $mappedClassrooms] = $this->alignStudentPortalTeacherWithEnrollments($studentId, $teacherId);
        $teacher = PortalUser::where('role', 1)->find($teacherId);

        $classroomIds = $mappedClassrooms->pluck('id')->map(fn ($id) => (int) $id)->values();
        $batchCountsByClassroom = $classroomIds->isEmpty() ? collect() : DB::table('batches')->whereIn('classroom_id', $classroomIds)->select('classroom_id', DB::raw('COUNT(*) as total_batches'))->groupBy('classroom_id')->get()->mapWithKeys(fn ($r) => [(int) $r->classroom_id => (int) $r->total_batches]);

        $studentCountsByClassroom = $classroomIds->isEmpty() ? collect() : DB::table('student_classroom_map as scm')->join('portal_user as pu', 'pu.id', '=', 'scm.student_id')->where('scm.teacher_id', $teacherId)->whereIn('scm.classroom_id', $classroomIds)->whereNotNull('scm.classroom_id')->where('pu.role', 2)->whereNull('pu.deleted_at')->select('scm.classroom_id', DB::raw('COUNT(DISTINCT scm.student_id) as total_students'))->groupBy('scm.classroom_id')->get()->mapWithKeys(fn ($r) => [(int) $r->classroom_id => (int) $r->total_students]);

        $classrooms = $mappedClassrooms->map(function ($row) use ($batchCountsByClassroom, $studentCountsByClassroom) {
            $cid = (int) $row->id;
            $row->total_batches = (int) ($batchCountsByClassroom[$cid] ?? 0);
            $row->total_students = (int) ($studentCountsByClassroom[$cid] ?? 0);

            return $row;
        });

        $data = [];
        $data['title'] = 'Classroom';
        $data['active_tab'] = 'student_classrooms';
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

    public function studentEvents()
    {
        if (! session()->has('portal_user')) {
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
        [$teacherId] = $this->alignStudentPortalTeacherWithEnrollments($studentId, $teacherId);
        $payload = $this->buildStudentEventsCalendarPayload($studentId, $teacherId);
        if ($payload === null) {
            return redirect('user/select-teacher');
        }

        $data = [];
        $data['title'] = 'Events';
        $data['active_tab'] = 'student_events';
        $data['teacher'] = $payload['teacher'];
        $data['event_types'] = $payload['event_types'];
        $data['calendar_events'] = $payload['calendar_events'];

        return view('web.user.student.events', $data);
    }

    public function saveStudentPersonalEvent(Request $request)
    {
        if (! session()->has('portal_user')) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 2) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 403);
        }
        if (! Schema::hasTable('student_personal_events')) {
            return response()->json(['status' => 0, 'error' => 'Personal events are not available.']);
        }

        $studentId = (int) ($portalUser['id'] ?? 0);
        $validation = Validator::make($request->all(), [
            'id' => 'nullable|integer|exists:student_personal_events,id',
            'title' => 'required|string|max:255',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
            'description' => 'nullable|string',
            'all_day' => 'nullable|in:0,1,true,false',
            'reminder_eligible' => 'nullable|in:0,1,true,false',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 0,
                'error_array' => formatErrors($validation->errors()->toArray()),
            ]);
        }

        $allDay = $request->boolean('all_day', true);
        $start = Carbon::parse($request->start_at);
        $end = Carbon::parse($request->end_at);
        if ($allDay) {
            $start = $start->copy()->startOfDay();
            $end = $end->copy()->startOfDay();
            if ($end->lt($start)) {
                $end = $start->copy();
            }
        } else {
            if ($end->lt($start)) {
                $end = $start->copy()->addHour();
            }
        }

        $attrs = [
            'student_id' => $studentId,
            'title' => $request->title,
            'description' => $request->description,
            'start_at' => $start->format('Y-m-d H:i:s'),
            'end_at' => $end->format('Y-m-d H:i:s'),
            'all_day' => $allDay,
            'reminder_eligible' => $request->boolean('reminder_eligible', false),
        ];

        if ($request->filled('id')) {
            $row = StudentPersonalEvent::query()->where('student_id', $studentId)->whereKey((int) $request->id)->first();
            if (! $row) {
                return response()->json(['status' => 0, 'error' => 'Event not found.']);
            }
            $row->update($attrs);
        } else {
            StudentPersonalEvent::query()->create($attrs);
        }

        return response()->json([
            'status' => 1,
            'msg' => $request->filled('id') ? 'Personal event updated.' : 'Personal event saved.',
            'redirect_url' => url('user/student/events'),
        ]);
    }

    public function deleteStudentPersonalEvent(Request $request)
    {
        if (! session()->has('portal_user')) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 401);
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 2) {
            return response()->json(['status' => 0, 'error' => 'Unauthorized'], 403);
        }
        if (! Schema::hasTable('student_personal_events')) {
            return response()->json(['status' => 0, 'error' => 'Personal events are not available.']);
        }

        $validation = Validator::make($request->all(), [
            'id' => 'required|integer|exists:student_personal_events,id',
        ]);
        if ($validation->fails()) {
            return response()->json([
                'status' => 0,
                'error_array' => formatErrors($validation->errors()->toArray()),
            ]);
        }

        $studentId = (int) ($portalUser['id'] ?? 0);
        $deleted = StudentPersonalEvent::query()
            ->where('student_id', $studentId)
            ->whereKey((int) $request->id)
            ->delete();

        if (! $deleted) {
            return response()->json(['status' => 0, 'error' => 'Event not found.']);
        }

        return response()->json([
            'status' => 1,
            'msg' => 'Personal event deleted.',
            'redirect_url' => url('user/student/events'),
        ]);
    }

    /**
     * @return array{mappedClassroomIds: array<int, int>, mappedBatchIds: array<int, int>, enrollmentPairs: list<array{classroom_id: int, batch_id: int}>}
     */
    private function getEnrollmentContextForStudentTeacher(int $studentId, int $teacherId): array
    {
        $mappedRows = DB::table('student_classroom_map')
            ->where('teacher_id', $teacherId)
            ->where('student_id', $studentId)
            ->get(['classroom_id', 'batch_id']);

        $mappedClassroomIds = $mappedRows
            ->pluck('classroom_id')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $mappedBatchIds = $mappedRows
            ->pluck('batch_id')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $legacyEnrollment = $this->legacyEnrollmentForStudentTeacher($studentId, $teacherId);
        if ($legacyEnrollment !== null) {
            if (! in_array($legacyEnrollment['classroom_id'], $mappedClassroomIds, true)) {
                $mappedClassroomIds[] = $legacyEnrollment['classroom_id'];
            }
            if ($legacyEnrollment['batch_id'] !== null && ! in_array((int) $legacyEnrollment['batch_id'], $mappedBatchIds, true)) {
                $mappedBatchIds[] = (int) $legacyEnrollment['batch_id'];
            }
        }

        /** @var list<array{classroom_id: int, batch_id: int}> */
        $enrollmentPairs = [];
        foreach ($mappedRows as $row) {
            $cid = $row->classroom_id !== null ? (int) $row->classroom_id : 0;
            $bid = $row->batch_id !== null ? (int) $row->batch_id : 0;
            if ($cid > 0 && $bid > 0) {
                $enrollmentPairs[$cid.':'.$bid] = ['classroom_id' => $cid, 'batch_id' => $bid];
            }
        }
        if ($legacyEnrollment !== null) {
            $lc = (int) $legacyEnrollment['classroom_id'];
            $lb = $legacyEnrollment['batch_id'] !== null ? (int) $legacyEnrollment['batch_id'] : 0;
            if ($lc > 0 && $lb > 0) {
                $enrollmentPairs[$lc.':'.$lb] = ['classroom_id' => $lc, 'batch_id' => $lb];
            }
        }
        $enrollmentPairs = array_values($enrollmentPairs);

        return [
            'mappedClassroomIds' => $mappedClassroomIds,
            'mappedBatchIds' => $mappedBatchIds,
            'enrollmentPairs' => $enrollmentPairs,
        ];
    }

    /**
     * @param  Builder<Event>  $q
     */
    private function constrainEventsQueryToStudentEnrollment($q, int $studentId, int $teacherId): void
    {
        $ctx = $this->getEnrollmentContextForStudentTeacher($studentId, $teacherId);
        $mappedClassroomIds = $ctx['mappedClassroomIds'];
        $mappedBatchIds = $ctx['mappedBatchIds'];
        $enrollmentPairs = $ctx['enrollmentPairs'];
        $hasAllClassroomsColumn = Schema::hasColumn('events', 'all_classrooms');
        $hasAllBatchesColumn = Schema::hasColumn('events', 'all_batches');

        $q->where(function ($sub) use (
            $mappedClassroomIds,
            $mappedBatchIds,
            $enrollmentPairs,
            $hasAllClassroomsColumn,
            $hasAllBatchesColumn
        ) {
            $sub->whereRaw('1 = 0');

            if ($enrollmentPairs !== []) {
                $sub->orWhere(function ($both) use ($enrollmentPairs) {
                    $both->whereHas('classrooms')
                        ->whereHas('batches')
                        ->where(function ($inner) use ($enrollmentPairs) {
                            $inner->whereRaw('1 = 0');
                            foreach ($enrollmentPairs as $pair) {
                                $inner->orWhere(function ($row) use ($pair) {
                                    $cid = $pair['classroom_id'];
                                    $bid = $pair['batch_id'];
                                    $row->whereHas('classrooms', fn ($cq) => $cq->where('classrooms.id', $cid))
                                        ->whereHas('batches', fn ($bq) => $bq->where('batches.id', $bid));
                                });
                            }
                        });
                });
            }

            if ($mappedClassroomIds !== []) {
                $sub->orWhere(function ($classOnly) use ($mappedClassroomIds) {
                    $classOnly->whereHas('classrooms', function ($cq) use ($mappedClassroomIds) {
                        $cq->whereIn('classrooms.id', $mappedClassroomIds);
                    })->whereDoesntHave('batches');
                });
            }

            if ($mappedBatchIds !== []) {
                $sub->orWhere(function ($batchOnly) use ($mappedBatchIds) {
                    $batchOnly->whereHas('batches', function ($bq) use ($mappedBatchIds) {
                        $bq->whereIn('batches.id', $mappedBatchIds);
                    })->whereDoesntHave('classrooms');
                });
            }

            $sub->orWhere(function ($wide) {
                $wide->whereDoesntHave('classrooms')->whereDoesntHave('batches');
            });

            if ($hasAllClassroomsColumn || $hasAllBatchesColumn) {
                $sub->orWhere(function ($allQ) use ($hasAllClassroomsColumn, $hasAllBatchesColumn) {
                    if ($hasAllClassroomsColumn) {
                        $allQ->where('all_classrooms', 1);
                    }
                    if ($hasAllBatchesColumn) {
                        $method = $hasAllClassroomsColumn ? 'orWhere' : 'where';
                        $allQ->{$method}('all_batches', 1);
                    }
                });
            }
        });
    }

    private function teacherEventVisibleToStudent(Event $event, int $studentId, int $teacherId): bool
    {
        if ((int) $event->teacher_id !== $teacherId) {
            return false;
        }

        return Event::query()
            ->whereKey($event->id)
            ->where('teacher_id', $teacherId)
            ->where(function ($q) use ($studentId, $teacherId) {
                $this->constrainEventsQueryToStudentEnrollment($q, $studentId, $teacherId);
            })
            ->exists();
    }

    /**
     * Portal students who should see a teacher-scheduled event (reminders, etc.).
     *
     * @return list<int>
     */
    public function portalStudentIdsForTeacherEvent(Event $event): array
    {
        $teacherId = (int) $event->teacher_id;
        if ($teacherId <= 0) {
            return [];
        }

        $ids = DB::table('student_classroom_map')
            ->where('teacher_id', $teacherId)
            ->distinct()
            ->pluck('student_id');

        if (Schema::hasTable('student_teacher_map')) {
            $ids = $ids->merge(
                DB::table('student_teacher_map')->where('teacher_id', $teacherId)->pluck('student_id')
            );
        }

        $out = [];
        foreach ($ids->unique() as $sid) {
            $sid = (int) $sid;
            if ($sid <= 0) {
                continue;
            }
            if ($this->teacherEventVisibleToStudent($event, $sid, $teacherId)) {
                $out[] = $sid;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Read-only calendar payload for a student + teacher (same rules as student Events page).
     *
     * @return array{teacher: ?PortalUser, event_types: Collection, calendar_events: array<int, mixed>}|null
     */
    private function buildStudentEventsCalendarPayload(int $studentId, int $teacherId): ?array
    {
        if ($studentId <= 0 || $teacherId <= 0) {
            return null;
        }

        $teacher = PortalUser::where('role', 1)->find($teacherId);
        if (! $teacher) {
            return null;
        }

        $events = Event::with(['eventType', 'classrooms', 'batches'])
            ->where('teacher_id', $teacherId)
            ->where(function ($q) use ($studentId, $teacherId) {
                $this->constrainEventsQueryToStudentEnrollment($q, $studentId, $teacherId);
            })
            ->orderBy('start_date')
            ->get();

        $eventTypeIds = $events
            ->pluck('event_type_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $eventTypes = empty($eventTypeIds)
            ? collect([])
            : EventType::query()
                ->whereIn('id', $eventTypeIds)
                ->orderBy('title')
                ->get(['id', 'title', 'color_code']);

        $calendarEvents = $events
            ->map(fn ($event) => FullCalendarEventPayload::fromTeacherEvent($event, true))
            ->values()
            ->all();

        if (Schema::hasTable('student_personal_events')) {
            $personalRows = StudentPersonalEvent::query()
                ->where('student_id', $studentId)
                ->orderBy('start_at')
                ->get()
                ->map(fn (StudentPersonalEvent $e) => FullCalendarEventPayload::fromStudentPersonalEvent($e))
                ->all();
            $calendarEvents = array_merge($calendarEvents, $personalRows);
        }

        usort($calendarEvents, function ($a, $b) {
            return strcmp((string) ($a['start'] ?? ''), (string) ($b['start'] ?? ''));
        });

        return [
            'teacher' => $teacher,
            'event_types' => $eventTypes,
            'calendar_events' => $calendarEvents,
        ];
    }

    /**
     * Distinct teachers linked to a student (classroom map, else teacher map).
     *
     * @return Collection<int, object{teacher_id: int, teacher_name: string, teacher_email: string|null}>
     */
    private function teachersForStudentId(int $studentId): Collection
    {
        if ($studentId <= 0) {
            return collect();
        }

        $fromMap = DB::table('student_classroom_map as scm')
            ->join('portal_user as t', 't.id', '=', 'scm.teacher_id')
            ->where('scm.student_id', $studentId)
            ->where('t.role', 1)
            ->whereNull('t.deleted_at')
            ->select(['t.id as teacher_id', 't.name as teacher_name', 't.email as teacher_email'])
            ->distinct()
            ->orderBy('t.name')
            ->get();

        if ($fromMap->isNotEmpty()) {
            return $fromMap;
        }

        if (! Schema::hasTable('student_teacher_map')) {
            return collect();
        }

        return DB::table('student_teacher_map as stm')
            ->join('portal_user as t', 't.id', '=', 'stm.teacher_id')
            ->where('stm.student_id', $studentId)
            ->where('t.role', 1)
            ->whereNull('t.deleted_at')
            ->select(['t.id as teacher_id', 't.name as teacher_name', 't.email as teacher_email'])
            ->distinct()
            ->orderBy('t.name')
            ->get();
    }

    /**
     * Parent Events: union of per-teacher student-visible events (same rules as student calendar per teacher).
     *
     * @return array{event_types: Collection, calendar_events: array<int, mixed>}
     */
    private function buildParentAggregatedStudentEventsPayload(int $studentId, string $studentDisplayName): array
    {
        if ($studentId <= 0) {
            return ['event_types' => collect(), 'calendar_events' => []];
        }

        $teacherIds = $this->teachersForStudentId($studentId)
            ->pluck('teacher_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $typesById = [];
        $seenEventKeys = [];
        $rows = [];

        foreach ($teacherIds as $tid) {
            $payload = $this->buildStudentEventsCalendarPayload($studentId, $tid);
            if ($payload === null) {
                continue;
            }
            foreach ($payload['event_types'] as $et) {
                $typesById[(int) $et->id] = $et;
            }
            foreach ($payload['calendar_events'] as $ce) {
                $key = isset($ce['id']) ? (string) $ce['id'] : '';
                if ($key !== '' && isset($seenEventKeys[$key])) {
                    continue;
                }
                if ($key !== '') {
                    $seenEventKeys[$key] = true;
                }
                if ($studentDisplayName !== '') {
                    $ce['extendedProps'] = array_merge($ce['extendedProps'] ?? [], [
                        'portal_student_name' => $studentDisplayName,
                    ]);
                }
                $rows[] = $ce;
            }
        }

        usort($rows, function ($a, $b) {
            $as = isset($a['start']) ? (string) $a['start'] : '';
            $bs = isset($b['start']) ? (string) $b['start'] : '';

            return $as <=> $bs;
        });

        $eventTypes = collect($typesById)->sortBy(fn ($et) => (string) ($et->title ?? ''))->values();

        return [
            'event_types' => $eventTypes,
            'calendar_events' => array_values($rows),
        ];
    }

    public function parentEvents()
    {
        $portalUser = $this->resolveParentPortalUser();
        if ($portalUser === null) {
            return redirect('login');
        }
        if ((int) ($portalUser['role'] ?? 0) !== 3) {
            return redirect('login');
        }

        $selectedStudentId = (int) (session('selected_student_id') ?? 0);
        if ($selectedStudentId <= 0) {
            return redirect('user/select-student');
        }

        $parentId = (int) ($portalUser['id'] ?? 0);
        if (! $this->parentOwnsStudent($parentId, $selectedStudentId)) {
            session()->forget('selected_student_id');

            return redirect('user/select-student');
        }

        session()->forget('parent_selected_teacher_id');

        $student = PortalUser::where('role', 2)->find($selectedStudentId);
        $studentDisplayName = (string) ($student->name ?? '');
        $agg = $this->buildParentAggregatedStudentEventsPayload($selectedStudentId, $studentDisplayName);

        $data = [];
        $data['title'] = 'Events';
        $data['active_tab'] = 'parent_events';
        $data['student'] = $student;
        $data['student_display_name'] = $studentDisplayName;
        $data['teacher'] = null;
        $data['event_types'] = $agg['event_types'];
        $data['calendar_events'] = $agg['calendar_events'];

        return view('web.user.parent.events', $data);
    }

    /**
     * Older admin flows store one classroom (and optional batch) on portal_user. The student portal
     * primarily uses student_classroom_map; this bridges the two so legacy assignments still work.
     *
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

    /**
     * Classrooms for a student under one teacher: map rows plus legacy portal_user.classroom_id when missing.
     *
     * @return Collection<int, object{id: int|string, name: string}>
     */
    private function mergedStudentClassroomsForTeacher(int $studentId, int $teacherId): Collection
    {
        if ($studentId <= 0 || $teacherId <= 0) {
            return collect();
        }

        $classroomIds = DB::table('student_classroom_map')
            ->where('teacher_id', $teacherId)
            ->where('student_id', $studentId)
            ->whereNotNull('classroom_id')
            ->distinct()
            ->pluck('classroom_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $rows = collect();
        if ($classroomIds->isNotEmpty()) {
            $rows = Classroom::query()
                ->whereIn('id', $classroomIds->all())
                ->where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($c) => (object) ['id' => $c->id, 'name' => (string) $c->name]);
        }

        $legacy = $this->legacyEnrollmentForStudentTeacher($studentId, $teacherId);
        if ($legacy !== null && ! $rows->contains(fn ($r) => (int) $r->id === $legacy['classroom_id'])) {
            $name = DB::table('classrooms')->where('id', $legacy['classroom_id'])->whereNull('deleted_at')->value('name');
            if ($name !== null) {
                $rows->push((object) ['id' => $legacy['classroom_id'], 'name' => (string) $name]);
            }
        }

        return $rows->sortBy(fn ($r) => strtolower((string) $r->name))->values();
    }

    /**
     * When the session “selected teacher” has no classroom data but another teacher linked to the student does,
     * switch to that teacher so the portal matches student_classroom_map (fixes wrong default_teacher_id).
     *
     * @return array{0: int, 1: Collection<int, object{id: int|string, name: string}>}
     */
    private function alignStudentPortalTeacherWithEnrollments(int $studentId, int $teacherId): array
    {
        $merged = $this->mergedStudentClassroomsForTeacher($studentId, $teacherId);
        if ($merged->isNotEmpty()) {
            return [$teacherId, $merged];
        }

        $candidateIds = DB::table('student_classroom_map')
            ->where('student_id', $studentId)
            ->whereNotNull('classroom_id')
            ->distinct()
            ->orderBy('teacher_id')
            ->pluck('teacher_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($candidateIds as $tid) {
            if ($tid <= 0 || $tid === $teacherId) {
                continue;
            }
            if (! Schema::hasTable('student_teacher_map') || ! DB::table('student_teacher_map')->where('student_id', $studentId)->where('teacher_id', $tid)->exists()) {
                continue;
            }
            $try = $this->mergedStudentClassroomsForTeacher($studentId, $tid);
            if ($try->isNotEmpty()) {
                session()->put('selected_teacher_id', $tid);
                PortalUser::where('id', $studentId)->where('role', 2)->update(['default_teacher_id' => $tid]);
                $freshStudent = PortalUser::where('role', 2)->find($studentId);
                if ($freshStudent) {
                    PortalSession::updateRoleUserArray(2, $freshStudent->toArray());
                }

                return [$tid, $try];
            }
        }

        return [$teacherId, $merged];
    }

    /**
     * Teacher for a student’s classroom: prefer student_classroom_map, else legacy portal_user row + STM.
     */
    private function resolveTeacherIdForStudentClassroom(int $studentId, int $classroomId): int
    {
        if ($studentId <= 0 || $classroomId <= 0) {
            return 0;
        }

        $fromMap = (int) (DB::table('student_classroom_map')->where('student_id', $studentId)->where('classroom_id', $classroomId)->value('teacher_id') ?? 0);
        if ($fromMap > 0) {
            return $fromMap;
        }

        $student = PortalUser::query()->where('role', 2)->whereKey($studentId)->first(['classroom_id']);
        if (! $student || (int) ($student->classroom_id ?? 0) !== $classroomId) {
            return 0;
        }

        $tid = (int) (Classroom::query()->whereKey($classroomId)->value('teacher_id') ?? 0);
        if ($tid <= 0) {
            return 0;
        }

        if (! Schema::hasTable('student_teacher_map')) {
            return 0;
        }

        return DB::table('student_teacher_map')->where('student_id', $studentId)->where('teacher_id', $tid)->exists() ? $tid : 0;
    }

    /**
     * @param  Collection<int, object{id: int|string, name: string, teacher_id: int|string, teacher_name: string}>  $mappedRows
     * @return Collection<int, object{id: int|string, name: string, teacher_id: int|string, teacher_name: string}>
     */
    private function mergeLegacyIntoParentClassroomList(int $studentId, Collection $mappedRows): Collection
    {
        $student = PortalUser::query()->where('role', 2)->whereKey($studentId)->first(['classroom_id']);
        if (! $student || ! (int) ($student->classroom_id ?? 0)) {
            return $mappedRows;
        }

        $cid = (int) $student->classroom_id;
        $cRow = DB::table('classrooms as c')
            ->join('portal_user as t', 't.id', '=', 'c.teacher_id')
            ->where('c.id', $cid)
            ->whereNull('c.deleted_at')
            ->where('t.role', 1)
            ->whereNull('t.deleted_at')
            ->select(['c.id', 'c.name', 'c.teacher_id', 't.name as teacher_name'])
            ->first();

        if (! $cRow) {
            return $mappedRows;
        }

        $tid = (int) $cRow->teacher_id;
        $exists = $mappedRows->contains(fn ($r) => (int) $r->id === $cid && (int) ($r->teacher_id ?? 0) === $tid);
        if ($exists) {
            return $mappedRows;
        }

        $linked = DB::table('student_teacher_map')->where('student_id', $studentId)->where('teacher_id', $tid)->exists()
            || DB::table('student_classroom_map')->where('student_id', $studentId)->where('teacher_id', $tid)->exists();

        if (! $linked) {
            return $mappedRows;
        }

        $mappedRows->push((object) [
            'id' => $cid,
            'name' => $cRow->name,
            'teacher_id' => $tid,
            'teacher_name' => $cRow->teacher_name,
        ]);

        return $mappedRows;
    }

    /**
     * Shared payload for student or parent viewing a classroom’s tests/marks.
     *
     * @return array<string, mixed>|null
     */
    private function buildStudentClassroomShowPayload(int $studentId, int $teacherId, int $classroomId, Request $request, bool $showTeacherBatchManageLink): ?array
    {
        $hasAccess = DB::table('student_classroom_map')->where('student_id', $studentId)->where('teacher_id', $teacherId)->where('classroom_id', $classroomId)->exists();
        if (! $hasAccess) {
            $legacy = $this->legacyEnrollmentForStudentTeacher($studentId, $teacherId);
            $hasAccess = $legacy !== null && (int) $legacy['classroom_id'] === $classroomId;
        }

        if (! $hasAccess) {
            return null;
        }

        $classroom = Classroom::query()
            ->with([
                'batches' => fn ($q) => $q->orderBy('name'),
                'batches.exams' => fn ($q) => $q->orderByDesc('exam_date')->orderBy('exam_name'),
            ])
            ->find($classroomId);

        if (! $classroom || (int) $classroom->teacher_id !== $teacherId) {
            return null;
        }

        $myBatchIds = DB::table('student_classroom_map')->where('student_id', $studentId)->where('teacher_id', $teacherId)->where('classroom_id', $classroomId)->whereNotNull('batch_id')->pluck('batch_id')->map(fn ($b) => (int) $b)->unique()->values();
        $legacyEnrollment = $this->legacyEnrollmentForStudentTeacher($studentId, $teacherId);
        if ($legacyEnrollment !== null && (int) $legacyEnrollment['classroom_id'] === $classroomId && $legacyEnrollment['batch_id'] !== null) {
            $myBatchIds = $myBatchIds->push((int) $legacyEnrollment['batch_id'])->unique()->values();
        }
        if ($myBatchIds->isNotEmpty()) {
            $classroom->setRelation(
                'batches',
                $classroom->batches->filter(fn ($batch) => $myBatchIds->contains((int) $batch->id))->values()
            );
        }

        $teacher = PortalUser::where('role', 1)->find($teacherId);

        $examIds = $classroom->batches->flatMap(fn ($b) => $b->exams->pluck('id'))->unique()->filter()->values();
        $marksByExamId = collect();
        if ($examIds->isNotEmpty()) {
            $marksByExamId = Mark::query()->where('student_id', $studentId)->whereIn('exam_id', $examIds)->get()->keyBy('exam_id');
        }

        $teacherSettingRow = TeacherSetting::query()->where('teacher_id', $teacherId)->first();
        $teacherMarkDisplaySetting = self::normalizeAbsentDisplaySettingForStudent((int) ($teacherSettingRow?->count_setting ?? TeacherSetting::COUNT_AS_ZERO));

        $absenceByExamId = collect();
        if (Schema::hasTable('mark_absences') && $examIds->isNotEmpty()) {
            $absenceByExamId = MarkAbsence::query()->where('student_id', $studentId)->whereIn('exam_id', $examIds)->get()->keyBy(fn ($r) => (int) $r->exam_id);
        }

        $batchIds = $classroom->batches->pluck('id')->map(fn ($bid) => (int) $bid)->values();
        $countsByBatch = collect();
        if ($batchIds->isNotEmpty()) {
            $countsByBatch = DB::table('student_classroom_map as scm')->join('portal_user as pu', 'pu.id', '=', 'scm.student_id')->where('scm.teacher_id', $teacherId)->where('scm.classroom_id', $classroomId)->whereIn('scm.batch_id', $batchIds)->where('pu.role', 2)->whereNull('pu.deleted_at')->select('scm.batch_id', DB::raw('COUNT(DISTINCT scm.student_id) as c'))->groupBy('scm.batch_id')->get()->mapWithKeys(fn ($r) => [(int) $r->batch_id => (int) $r->c]);
        }

        $attendanceTableReady = Schema::hasTable('student_attendances');
        $allClassroomBatchIds = $classroom->batches->pluck('id')->map(fn ($x) => (int) $x)->values()->all();
        $attendanceDateKeysByBatch = [];
        $attendanceStudentStatusByBatchDate = [];
        if ($attendanceTableReady && $allClassroomBatchIds !== []) {
            $attRows = StudentAttendance::query()
                ->whereIn('batch_id', $allClassroomBatchIds)
                ->get(['batch_id', 'student_id', 'date', 'attendance_status']);
            foreach ($attRows as $row) {
                $bidAtt = (int) $row->batch_id;
                $dAtt = $row->date->format('Y-m-d');
                $attendanceDateKeysByBatch[$bidAtt][$dAtt] = true;
                if ((int) $row->student_id === $studentId) {
                    $attendanceStudentStatusByBatchDate[$bidAtt][$dAtt] = (string) $row->attendance_status;
                }
            }
        }
        foreach ($classroom->batches as $batch) {
            $bid = (int) $batch->id;
            $batch->enrollment_student_count = (int) ($countsByBatch[$bid] ?? 0);
            $batch->is_my_batch = $myBatchIds->contains($bid);
            if ($batch->is_my_batch) {
                $keysAtt = array_keys($attendanceDateKeysByBatch[$bid] ?? []);
                sort($keysAtt, SORT_STRING);
                $cellsAtt = [];
                foreach ($keysAtt as $dAtt) {
                    $cellsAtt[$dAtt] = $attendanceStudentStatusByBatchDate[$bid][$dAtt] ?? '';
                }
                $batch->setAttribute('attendance_dates', $keysAtt);
                $batch->setAttribute('attendance_cells', $cellsAtt);
            } else {
                $batch->setAttribute('attendance_dates', []);
                $batch->setAttribute('attendance_cells', []);
            }
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

        $validBatchIds = $classroom->batches->pluck('id')->map(fn ($x) => (int) $x)->all();
        $requestedBatch = (int) $request->query('batch', 0);
        $requestedExam = (int) $request->query('exam', 0);

        $defaultBatchId = null;
        if ($requestedBatch > 0 && in_array($requestedBatch, $validBatchIds, true)) {
            $defaultBatchId = $requestedBatch;
        } elseif ($requestedExam > 0) {
            foreach ($classroom->batches as $b) {
                if ($b->exams->contains(fn ($e) => (int) $e->id === $requestedExam)) {
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
            $activeBatch = $classroom->batches->firstWhere(fn ($b) => (int) $b->id === (int) $defaultBatchId);
            if ($activeBatch && $activeBatch->exams->contains(fn ($e) => (int) $e->id === $requestedExam)) {
                $highlightExamId = $requestedExam;
            }
        }

        $studentDisplayName = (string) (PortalUser::query()->whereKey($studentId)->value('name') ?? '');

        return [
            'title' => $classroom->name,
            'classroom' => $classroom,
            'teacher' => $teacher,
            'my_batch_count' => $myBatchIds->count(),
            'default_batch_id' => $defaultBatchId,
            'highlight_exam_id' => $highlightExamId,
            'show_teacher_batch_manage_link' => $showTeacherBatchManageLink,
            'attendance_table_ready' => $attendanceTableReady,
            'student_id' => $studentId,
            'student_display_name' => $studentDisplayName,
        ];
    }

    public function studentClassroomShow(Request $request, $id)
    {
        if (! session()->has('portal_user')) {
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
        $data['active_tab'] = 'student_classrooms';

        return view('web.user.student.classroom_show', $data);
    }

    private function resolveParentPortalUser(): ?array
    {
        if (! session()->has('portal_user')) {
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
        if (! $this->parentOwnsStudent($parentId, $selectedStudentId)) {
            session()->forget('selected_student_id');

            return redirect('user/select-student');
        }

        $student = PortalUser::where('role', 2)->find($selectedStudentId);
        if (! $student) {
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

        $mappedRows = $this->mergeLegacyIntoParentClassroomList($selectedStudentId, $mappedRows);

        $classroomIds = $mappedRows->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
        $batchCountsByClassroom = $classroomIds->isEmpty() ? collect() : DB::table('batches')->whereIn('classroom_id', $classroomIds)->select('classroom_id', DB::raw('COUNT(*) as total_batches'))->groupBy('classroom_id')->get()->mapWithKeys(fn ($r) => [(int) $r->classroom_id => (int) $r->total_batches]);

        $studentCountsByClassroom = $classroomIds->isEmpty() ? collect() : DB::table('student_classroom_map as scm')->join('portal_user as pu', 'pu.id', '=', 'scm.student_id')->whereIn('scm.classroom_id', $classroomIds)->whereNotNull('scm.classroom_id')->where('pu.role', 2)->whereNull('pu.deleted_at')->select('scm.classroom_id', DB::raw('COUNT(DISTINCT scm.student_id) as total_students'))->groupBy('scm.classroom_id')->get()->mapWithKeys(fn ($r) => [(int) $r->classroom_id => (int) $r->total_students]);

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
        if (! $this->parentOwnsStudent($parentId, $selectedStudentId)) {
            session()->forget('selected_student_id');

            return redirect('user/select-student');
        }

        $classroomId = (int) $id;
        $teacherId = $this->resolveTeacherIdForStudentClassroom($selectedStudentId, $classroomId);

        if ($teacherId <= 0) {
            return redirect('user/parent/classrooms');
        }

        $payload = $this->buildStudentClassroomShowPayload($selectedStudentId, $teacherId, $classroomId, $request, false);
        if ($payload === null) {
            return redirect('user/parent/classrooms');
        }

        $data = $payload;
        $data['active_tab'] = 'classrooms';
        $data['selected_student'] = PortalUser::query()
            ->where('role', 2)
            ->whereKey($selectedStudentId)
            ->first(['id', 'name', 'email']);

        return view('web.user.parent.classroom_show', $data);
    }

    /**
     * Same semantics as teacher marks grid: only COUNT_AS_ZERO vs EXCLUDE_FROM_OVERALL_PERCENTAGE matter.
     */
    private static function normalizeAbsentDisplaySettingForStudent(int $raw): int
    {
        return $raw === TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE ? TeacherSetting::EXCLUDE_FROM_OVERALL_PERCENTAGE : TeacherSetting::COUNT_AS_ZERO;
    }

    /**
     * Select a teacher context for the student session only (does not grant teacher portal access).
     */
    public function loginAsTeacher($id)
    {
        if (! session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }

        $teacherId = (int) $id;
        if ($teacherId <= 0) {
            return redirect('user/select-teacher');
        }

        $studentId = (int) ($portalUser['id'] ?? 0);
        $isMapped = StudentTeacherMap::where('student_id', $studentId)->where('teacher_id', $teacherId)->exists();
        if (! $isMapped) {
            return redirect('user/select-teacher');
        }

        if (! PortalUser::where('role', 1)->whereKey($teacherId)->exists()) {
            return redirect('user/select-teacher');
        }

        session()->put('selected_teacher_id', $teacherId);
        session()->forget('show_teacher_password_popup');

        return redirect('user/student/dashboard');
    }

    public function selectStudent()
    {
        if (! session()->has('portal_user')) {
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
        if (! session()->has('portal_user')) {
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

        if (! $isMapped) {
            return redirect('user/select-student');
        }

        $student = PortalUser::where('role', 2)->find($studentId);
        if (! $student) {
            return redirect('user/select-student');
        }

        // Do not switch identity; keep parent session and set selected student context
        session()->put('selected_student_id', $student->id);

        // Persist as parent's default student for future logins
        $parentModel = PortalUser::where('role', 3)->find((int) ($parent['id'] ?? 0));
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
        if (! session()->has('portal_user')) {
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

        $student = PortalUser::where('role', 2)->find($selectedStudentId);
        if (! $student) {
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

        if ($uid <= 0) {
            return response()->json(['status' => 0]);
        }

        $user = PortalUser::whereKey($uid)
            ->whereIn('role', [1, 2, 3])
            ->whereNull('deleted_at')
            ->first();

        if (! $user) {
            return response()->json(['status' => 0]);
        }

        $morph = $user->getMorphClass();
        $id = (string) $request->input('id');

        // ✅ ONLY allow user to delete THEIR OWN notification
        $row = DB::table('notifications')->where('id', $id)->where('notifiable_id', $user->id)->where('notifiable_type', $morph)->first();

        if (! $row) {
            return response()->json(['status' => 0]);
        }

        // ✅ If dismissal table exists → soft hide per user
        if (Schema::hasTable('portal_notification_dismissals')) {
            DB::table('portal_notification_dismissals')->updateOrInsert(
                [
                    'notification_id' => $id,
                    'portal_user_id' => $user->id,
                ],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            return response()->json(['status' => 1]);
        }

        // ✅ Otherwise delete normally
        if (Schema::hasColumn('notifications', 'deleted_at')) {
            $updated = DB::table('notifications')
                ->where('id', $id)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                    'read_at' => now(),
                ]);
        } else {
            $updated = DB::table('notifications')->where('id', $id)->delete();
        }

        return response()->json(['status' => $updated ? 1 : 0]);
    }

    public function studentLeave()
    {
        if (! session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        if ((int) ($portalUser['role'] ?? 0) !== 3) {
            return redirect('user/dashboard');
        }

        $parentId = (int) ($portalUser['id'] ?? 0);
        if ($parentId < 1) {
            return redirect('login');
        }

        $childIds = DB::table('portal_user as s')
            ->leftJoin('parent_student_map as psm', function ($join) use ($parentId) {
                $join->on('psm.student_id', '=', 's.id')->where('psm.parent_id', '=', $parentId);
            })
            ->where('s.role', 2)
            ->whereNull('s.deleted_at')
            ->where(function ($q) use ($parentId) {
                $q->where('s.parent_id', $parentId)->orWhereNotNull('psm.id');
            })
            ->pluck('s.id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $studentLeaveRequests = collect();
        if ($childIds->isNotEmpty()) {
            $studentLeaveRequests = LeaveRequests::query()
                ->with(['student', 'classroom', 'batch'])
                ->whereIn('student_id', $childIds)
                ->orderByDesc('id')
                ->get();
        }

        return view('web.user.parent.student_leave', [
            'title' => 'Student leave requests',
            'active_tab' => 'leave',
            'student_leave_requests' => $studentLeaveRequests,
        ]);
    }
}
