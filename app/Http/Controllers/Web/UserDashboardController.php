<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\PortalRememberFromCookie;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    public function index()
    {
        if (!session()->has('portal_user')) {
            return redirect('login');
        }
        $portalUser = session('portal_user');
        // If student, decide between previously selected teacher dashboard vs selection page
        if ((int)($portalUser['role'] ?? 0) === 2) {
            // Try to load default teacher from DB
            $student = \App\Models\PortalUser::find((int)($portalUser['id'] ?? 0));
            $defaultTeacherId = (int)($student->default_teacher_id ?? 0);
            if ($defaultTeacherId > 0) {
                // Verify mapping still exists
                $isMapped = \App\Models\StudentTeacherMap::where('student_id', (int)($portalUser['id'] ?? 0))
                    ->where('teacher_id', $defaultTeacherId)
                    ->exists();
                if ($isMapped) {
                    session()->put('selected_teacher_id', $defaultTeacherId);
                    return redirect('user/student/dashboard');
                }
            }
            return redirect('user/select-teacher');
        }
        // If parent, take them to student selection page
        if ((int)($portalUser['role'] ?? 0) === 3) {
            // Try to redirect to previously selected student if available
            $parent = \App\Models\PortalUser::find((int)($portalUser['id'] ?? 0));
            $defaultStudentId = (int)($parent->default_student_id ?? 0);
            if ($defaultStudentId > 0) {
                // Ensure relationship exists (via legacy parent_id or parent_student_map)
                $isMapped = \Illuminate\Support\Facades\DB::table('portal_user')->where('id', $defaultStudentId)->where('role', 2)
                    ->where(function ($q) use ($parent, $defaultStudentId) {
                        $q->where('parent_id', (int)($parent->id ?? 0))
                          ->orWhereExists(function ($sub) use ($parent, $defaultStudentId) {
                              $sub->from('parent_student_map')
                                  ->whereColumn('parent_student_map.student_id', 'portal_user.id')
                                  ->where('parent_student_map.parent_id', (int)($parent->id ?? 0))
                                  ->where('parent_student_map.student_id', $defaultStudentId);
                          });
                    })->exists();
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
            $data['total_classrooms_details'] = Classroom::with('batches')->where('teacher_id', $teacherId)->get();
            $data['total_students'] = (int) DB::table('student_teacher_map')
                ->where('teacher_id', $teacherId)
                ->selectRaw('COUNT(DISTINCT student_id) as c')
                ->value('c');
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
            $data['teacher'] = $teacherId > 0
                ? PortalUser::where('role', 1)->find($teacherId)
                : null;

            return view('web.user.student.profile', $data);
        }

        return view('web.user.profile', $data);
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
            $data['teacher'] = $teacherId > 0
                ? PortalUser::where('role', 1)->find($teacherId)
                : null;

            return view('web.user.student.security', $data);
        }

        return view('web.user.security', $data);
    }

    public function logout()
    {
        $portal = session('portal_user');
        if (! empty($portal['id'])) {
            PortalUser::whereKey((int) $portal['id'])->update(['remember_token' => null]);
        }
        Cookie::queue(Cookie::forget(PortalRememberFromCookie::COOKIE_NAME));
        session()->forget('portal_user');
        session()->forget('teacher');
        session()->forget('show_teacher_password_popup');
        session()->forget('acting_teacher_id');
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
            $user->save();

            session()->put('portal_user', $user->toArray());
            if ((int)($user->role ?? 0) === 1) {
                session()->put('teacher', $user->toArray());
            }
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
        $userId = (int)($portalUser['id'] ?? 0);

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

            session()->put('portal_user', $user->toArray());
            if ((int)($user->role ?? 0) === 1) {
                session()->put('teacher', $user->toArray());
            }

            $this->response['status'] = 1;
            $this->response['msg'] = 'Profile updated';
            $this->response['redirect_url'] = url('user/profile');
        } else {
            $this->response['status'] = 0;
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

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

            $isValidCurrentPassword = Hash::check($request->current_password, (string)$user->password) || ((string)($user->p ?? '') === $request->current_password);
            if ($isValidCurrentPassword) {
                $user->password = bcrypt($request->password);
                $user->p = $request->password;
                $user->is_password_changed = 1;
                $user->save();

                session()->put('portal_user', $user->toArray());
                if ((int)($user->role ?? 0) === 1) {
                    session()->put('teacher', $user->toArray());
                }
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
        if ((int)($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }

        // Fetch mapped teachers for this student with classroom/batch info from the map row
        // Use query builder with alias to avoid SoftDeletes global scope aliasing issues
        $teachers = DB::table('portal_user as t')
            ->join('student_teacher_map as stm', 'stm.teacher_id', '=', 't.id')
            ->leftJoin('classrooms as c', 'c.id', '=', 'stm.classroom_id')
            ->leftJoin('batches as b', 'b.id', '=', 'stm.batch_id')
            ->where('t.role', 1)
            ->where('stm.student_id', (int)($portalUser['id'] ?? 0))
            ->whereNull('t.deleted_at')
            ->select([
                't.id as teacher_id',
                't.name as teacher_name',
                't.email as teacher_email',
                't.phone as teacher_phone',
                'stm.classroom_id',
                'stm.batch_id',
                'c.name as classroom_name',
                'b.name as batch_name',
            ])
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
        if ((int)($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }

        // Accept teacher id from either POST body or route parameter
        $routeTeacherId = (int)($request->route('teacherId') ?? 0);
        $bodyTeacherId = (int)($request->teacher_id ?? 0);
        $teacherId = $routeTeacherId > 0 ? $routeTeacherId : $bodyTeacherId;
        if ($teacherId <= 0) {
            return redirect('user/select-teacher');
        }

        // Ensure this teacher is mapped to the logged-in student
        $isMapped = \App\Models\StudentTeacherMap::where('student_id', (int)($portalUser['id'] ?? 0))
            ->where('teacher_id', $teacherId)
            ->exists();
        if (!$isMapped) {
            return redirect('user/select-teacher');
        }

        // Keep student logged in; set selected teacher context
        session()->put('selected_teacher_id', $teacherId);
        // Close any teacher password popup flag as it's not relevant for student context
        session()->forget('show_teacher_password_popup');

        // Persist as student's default teacher for future logins
        $student = \App\Models\PortalUser::where('role', 2)->find((int)($portalUser['id'] ?? 0));
        if ($student) {
            $student->default_teacher_id = $teacherId;
            $student->save();
            // Update session copy to include new field
            session()->put('portal_user', $student->toArray());
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
        if ((int)($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }
        $teacherId = (int)(session('selected_teacher_id') ?? 0);
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
        if ((int)($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }
        $teacherId = (int)(session('selected_teacher_id') ?? 0);
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
        if ((int)($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }
        $teacherId = (int)(session('selected_teacher_id') ?? 0);
        if ($teacherId <= 0) {
            return redirect('user/select-teacher');
        }

        $teacher = \App\Models\PortalUser::where('role', 1)->find($teacherId);
        $data = [];
        $data['title'] = 'Dashboard';
        $data['active_tab'] = 'student_dashboard';
        $data['teacher'] = $teacher;
        // Simple summary placeholders; wire real data if available
        $data['stats'] = [
            'totalClasses' => 0,
            'attendanceRate' => 0,
            'reportsAvailable' => 0,
        ];
        return view('web.user.student.dashboard', $data);
    }

    // Runs under teacher-session middleware (separate cookie)
    public function loginAsTeacher($id)
    {
        // Ensure this is initiated from a valid student mapping by checking a signed state would be ideal,
        // but we at least ensure a student initiated the flow before.
        $teacherId = (int)$id;
        if ($teacherId <= 0) {
            return redirect('login');
        }

        // For safety: if already a teacher session exists, allow switching
        $teacher = \App\Models\PortalUser::where('role', 1)->find($teacherId);
        if (!$teacher) {
            return redirect(url('/login'));
        }

        // Set dedicated teacher session
        session()->put('portal_user', $teacher->toArray());
        session()->put('teacher', $teacher->toArray());
        if ((int)($teacher->is_password_changed ?? 0) === 0) {
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
        if ((int)($portalUser['role'] ?? 0) !== 3) {
            return redirect('user/dashboard');
        }

        // Get all children (students) mapped to this parent via mapping table or legacy parent_id
        $parentId = (int)($portalUser['id'] ?? 0);
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
            ->select([
                's.id as student_id',
                's.name as student_name',
                's.email as student_email',
                's.phone as student_phone',
                'c.name as classroom_name',
                'b.name as batch_name',
            ])
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
        if ((int)($parent['role'] ?? 0) !== 3) {
            return redirect('user/dashboard');
        }

        // Accept student id from either POST body or route parameter
        $routeStudentId = (int)($request->route('studentId') ?? 0);
        $bodyStudentId = (int)($request->student_id ?? 0);
        $studentId = $routeStudentId > 0 ? $routeStudentId : $bodyStudentId;
        if ($studentId <= 0) {
            return redirect('user/select-student');
        }

        // Ensure this student is mapped to the logged-in parent (via mapping table or legacy parent_id)
        $isMapped = DB::table('portal_user')->where('id', $studentId)->where('role', 2)
            ->where(function ($q) use ($parent, $studentId) {
                $q->where('parent_id', (int)($parent['id'] ?? 0))
                  ->orWhereExists(function ($sub) use ($parent, $studentId) {
                      $sub->from('parent_student_map')
                          ->whereColumn('parent_student_map.student_id', 'portal_user.id')
                          ->where('parent_student_map.parent_id', (int)($parent['id'] ?? 0))
                          ->where('parent_student_map.student_id', $studentId);
                  });
            })->exists();

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
        $parentModel = \App\Models\PortalUser::where('role', 3)->find((int)($parent['id'] ?? 0));
        if ($parentModel) {
            $parentModel->default_student_id = $student->id;
            $parentModel->save();
            session()->put('portal_user', $parentModel->toArray());
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
        if ((int)($portalUser['role'] ?? 0) !== 3) {
            return redirect('user/dashboard');
        }
        $selectedStudentId = (int)(session('selected_student_id') ?? 0);
        if ($selectedStudentId <= 0) {
            return redirect('user/select-student');
        }

        $student = \App\Models\PortalUser::where('role', 2)->find($selectedStudentId);
        if (!$student) {
            session()->forget('selected_student_id');
            return redirect('user/select-student');
        }

        $data = [];
        $data['title'] = 'Parent Dashboard';
        $data['active_tab'] = 'dashboard';
        $data['student'] = $student;
        return view('web.user.parent.dashboard', $data);
    }
}

