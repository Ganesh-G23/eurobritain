<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StudentComplaintController extends Controller
{
    public function studentComplaints()
    {
        $portalUser = session('portal_user');
        $studentId = (int) ($portalUser['id'] ?? 0);
        if ((int) ($portalUser['role'] ?? 0) !== 2 || $studentId <= 0) {
            return redirect('user/dashboard');
        }

        $selectedTeacherId = (int) session('selected_teacher_id', 0);
        if ($selectedTeacherId <= 0) {
            $selectedTeacherId = (int) (PortalUser::query()->whereKey($studentId)->value('default_teacher_id') ?? 0);
        }

        $complaints = Complaint::query()
            ->with([
                'teacher:id,name',
                'parent:id,name',
            ])
            ->where('student_id', $studentId)
            ->when($selectedTeacherId > 0, function ($q) use ($selectedTeacherId) {
                $q->where('teacher_id', $selectedTeacherId);
            })
            ->orderByDesc('id')
            ->get();

        return view('web.user.student.complaints', [
            'title' => 'Complaints',
            'active_tab' => 'complaints',
            'complaints' => $complaints,
            'selected_teacher_id' => $selectedTeacherId,
        ]);
    }

    public function parentComplaints()
    {
        $portalUser = session('portal_user');
        $parentId = (int) ($portalUser['id'] ?? 0);
        $selectedStudentId = (int) session('selected_student_id', 0);

        if ((int) ($portalUser['role'] ?? 0) !== 3 || $parentId <= 0) {
            return redirect('user/dashboard');
        }

        if ($selectedStudentId <= 0) {
            return redirect('user/select-student');
        }

        $isMapped = PortalUser::query()
            ->where('id', $selectedStudentId)
            ->where('role', 2)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($parentId) {
                $q->where('parent_id', $parentId)
                    ->orWhereExists(function ($sub) use ($parentId) {
                        $sub->from('parent_student_map')
                            ->whereColumn('parent_student_map.student_id', 'portal_user.id')
                            ->where('parent_student_map.parent_id', $parentId);
                    });
            })
            ->exists();

        if (! $isMapped) {
            session()->forget('selected_student_id');
            return redirect('user/select-student');
        }

        $teachers = collect();
        if (DB::getSchemaBuilder()->hasTable('student_teacher_map')) {
            $teachers = PortalUser::query()
                ->join('student_teacher_map as stm', 'stm.teacher_id', '=', 'portal_user.id')
                ->where('stm.student_id', $selectedStudentId)
                ->where('portal_user.role', 1)
                ->whereNull('portal_user.deleted_at')
                ->select('portal_user.id', 'portal_user.name')
                ->orderBy('portal_user.name')
                ->distinct()
                ->get();
        }

        if ($teachers->isEmpty()) {
            $defaultTeacherId = (int) (PortalUser::query()->whereKey($selectedStudentId)->value('default_teacher_id') ?? 0);
            if ($defaultTeacherId > 0) {
                $fallbackTeacher = PortalUser::query()
                    ->whereKey($defaultTeacherId)
                    ->where('role', 1)
                    ->whereNull('deleted_at')
                    ->select('id', 'name')
                    ->first();
                if ($fallbackTeacher) {
                    $teachers = collect([$fallbackTeacher]);
                }
            }
        }

        $studentName = (string) (PortalUser::query()->whereKey($selectedStudentId)->value('name') ?? '');
        $complaints = Complaint::query()
            ->with([
                'teacher:id,name',
            ])
            ->where('parent_id', $parentId)
            ->where('student_id', $selectedStudentId)
            ->orderByDesc('id')
            ->get();

        return view('web.user.parent.complaints', [
            'title' => 'Complaints',
            'active_tab' => 'complaints',
            'student_name' => $studentName,
            'selected_student_id' => $selectedStudentId,
            'teachers' => $teachers,
            'complaints' => $complaints,
        ]);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'remark' => 'required|string|max:1000',
            'teacher_id' => 'nullable|integer',
            'student_id' => 'nullable|integer',
        ]);

        if ($validation->fails()) {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
            echo json_encode($this->response);
            return;
        }

        $portalUser = session('portal_user');
        $role = (int) ($portalUser['role'] ?? 0);
        $userId = (int) ($portalUser['id'] ?? 0);
        if ($userId <= 0) {
            $this->response['status'] = 0;
            $this->response['msg'] = 'Unauthorized request.';
            echo json_encode($this->response);
            return;
        }

        if ($role === 1) {
            $teacherId = $userId;
            $selectedStudentId = (int) $request->student_id;
            if ($selectedStudentId <= 0) {
                $this->response['status'] = 0;
                $this->response['msg'] = 'Please select a student.';
                echo json_encode($this->response);
                return;
            }

            $hasStudentTeacherMap = DB::getSchemaBuilder()->hasTable('student_teacher_map');
            $studentLinked = PortalUser::query()
                ->where('id', $selectedStudentId)
                ->where('role', 2)
                ->whereNull('deleted_at')
                ->where(function ($q) use ($teacherId, $hasStudentTeacherMap) {
                    if ($hasStudentTeacherMap) {
                        $q->whereExists(function ($sub) use ($teacherId) {
                            $sub->from('student_teacher_map')
                                ->whereColumn('student_teacher_map.student_id', 'portal_user.id')
                                ->where('student_teacher_map.teacher_id', $teacherId);
                        })->orWhere('default_teacher_id', $teacherId);
                    } else {
                        $q->where('default_teacher_id', $teacherId);
                    }
                })
                ->exists();

            if (! $studentLinked) {
                $this->response['status'] = 0;
                $this->response['msg'] = 'Selected student is not linked to this teacher.';
                echo json_encode($this->response);
                return;
            }

            $parentId = (int) (PortalUser::query()->whereKey($selectedStudentId)->value('parent_id') ?? 0);
            if ($parentId <= 0 && DB::getSchemaBuilder()->hasTable('parent_student_map')) {
                $parentId = (int) (DB::table('parent_student_map as psm')
                    ->join('portal_user as p', 'p.id', '=', 'psm.parent_id')
                    ->where('psm.student_id', $selectedStudentId)
                    ->where('p.role', 3)
                    ->whereNull('p.deleted_at')
                    ->orderBy('psm.id')
                    ->value('psm.parent_id') ?? 0);
            }

            if ($parentId <= 0) {
                $this->response['status'] = 0;
                $this->response['msg'] = 'No parent linked with this student.';
                echo json_encode($this->response);
                return;
            }
        } elseif ($role === 3) {
            $parentId = $userId;
            $selectedStudentId = (int) session('selected_student_id', 0);
            $teacherId = (int) $request->teacher_id;

            if ($selectedStudentId <= 0) {
                $this->response['status'] = 0;
                $this->response['msg'] = 'Please select a student first.';
                $this->response['redirect_url'] = url('user/select-student');
                echo json_encode($this->response);
                return;
            }

            $isMapped = PortalUser::query()
                ->where('id', $selectedStudentId)
                ->where('role', 2)
                ->whereNull('deleted_at')
                ->where(function ($q) use ($parentId) {
                    $q->where('parent_id', $parentId)
                        ->orWhereExists(function ($sub) use ($parentId) {
                            $sub->from('parent_student_map')
                                ->whereColumn('parent_student_map.student_id', 'portal_user.id')
                                ->where('parent_student_map.parent_id', $parentId);
                        });
                })
                ->exists();

            if (! $isMapped) {
                $this->response['status'] = 0;
                $this->response['msg'] = 'Selected student is not linked to this parent.';
                $this->response['redirect_url'] = url('user/select-student');
                echo json_encode($this->response);
                return;
            }
        } elseif ($role === 2) {
            $selectedStudentId = $userId;
            $teacherId = (int) ($request->teacher_id ?? 0);
            if ($teacherId <= 0) {
                $teacherId = (int) (session('selected_teacher_id') ?? 0);
            }
            if ($teacherId <= 0) {
                $teacherId = (int) (PortalUser::query()->whereKey($selectedStudentId)->value('default_teacher_id') ?? 0);
            }

            $parentId = (int) (PortalUser::query()->whereKey($selectedStudentId)->value('parent_id') ?? 0);
            if ($parentId <= 0 && DB::getSchemaBuilder()->hasTable('parent_student_map')) {
                $parentId = (int) (DB::table('parent_student_map as psm')
                    ->join('portal_user as p', 'p.id', '=', 'psm.parent_id')
                    ->where('psm.student_id', $selectedStudentId)
                    ->where('p.role', 3)
                    ->whereNull('p.deleted_at')
                    ->orderBy('psm.id')
                    ->value('psm.parent_id') ?? 0);
            }
            if ($parentId <= 0) {
                $this->response['status'] = 0;
                $this->response['msg'] = 'No parent linked with this student.';
                echo json_encode($this->response);
                return;
            }
        } else {
            $this->response['status'] = 0;
            $this->response['msg'] = 'Unauthorized request.';
            echo json_encode($this->response);
            return;
        }

        $defaultTeacherId = (int) (PortalUser::query()->whereKey($selectedStudentId)->value('default_teacher_id') ?? 0);
        $hasStudentTeacherMap = DB::getSchemaBuilder()->hasTable('student_teacher_map');
        if (! $hasStudentTeacherMap && $defaultTeacherId <= 0) {
            $teacherLinked = false;
        } else {
            $teacherLinked = PortalUser::query()
                ->whereKey($teacherId)
                ->where('role', 1)
                ->whereNull('deleted_at')
                ->where(function ($q) use ($selectedStudentId, $defaultTeacherId, $hasStudentTeacherMap) {
                    if ($hasStudentTeacherMap) {
                        $q->whereExists(function ($sub) use ($selectedStudentId) {
                            $sub->from('student_teacher_map')
                                ->whereColumn('student_teacher_map.teacher_id', 'portal_user.id')
                                ->where('student_teacher_map.student_id', $selectedStudentId);
                        });
                    }
                    if ($defaultTeacherId > 0) {
                        $q->orWhere('id', $defaultTeacherId);
                    }
                })
                ->exists();
        }

        if (! $teacherLinked) {
            $this->response['status'] = 0;
            $this->response['msg'] = 'No valid teacher found for this student.';
            echo json_encode($this->response);
            return;
        }

        $complaint = new Complaint();
        $complaint->student_id = $selectedStudentId;
        $complaint->parent_id = $parentId;
        $complaint->teacher_id = $teacherId;
        $complaint->remark = trim((string) $request->remark);
        $complaint->save();

        $this->response['status'] = 1;
        $this->response['msg'] = 'Complaint submitted successfully.';
        if ($role === 1) {
            $this->response['redirect_url'] = url('user/teacher/complaints');
        } elseif ($role === 3) {
            $this->response['redirect_url'] = url('user/parent/complaints');
        } else {
            $this->response['redirect_url'] = url('user/student/complaints');
        }
        echo json_encode($this->response);
    }

    public function complaintList()
    {
        $portalUser = session('portal_user');
        $role = (int) ($portalUser['role'] ?? 0);
        $userId = (int) ($portalUser['id'] ?? 0);
        if ($userId <= 0 || $role !== 1) {
            return redirect('user/dashboard');
        }

        $complaints = Complaint::query()
            ->with([
                'student:id,name',
                'parent:id,name',
            ])
            ->where('teacher_id', $userId)
            ->orderByDesc('id')
            ->get();

        $hasStudentTeacherMap = DB::getSchemaBuilder()->hasTable('student_teacher_map');
        $students = PortalUser::query()
            ->where('role', 2)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($userId, $hasStudentTeacherMap) {
                if ($hasStudentTeacherMap) {
                    $q->whereExists(function ($sub) use ($userId) {
                        $sub->from('student_teacher_map')
                            ->whereColumn('student_teacher_map.student_id', 'portal_user.id')
                            ->where('student_teacher_map.teacher_id', $userId);
                    })->orWhere('default_teacher_id', $userId);
                } else {
                    $q->where('default_teacher_id', $userId);
                }
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('web.user.teacher.complaints_list', [
            'title' => 'Complaints List',
            'active_tab' => 'complaints',
            'complaints' => $complaints,
            'students' => $students,
        ]);
    }
}
