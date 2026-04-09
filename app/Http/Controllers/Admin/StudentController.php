<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use App\Models\StudentClassroomMap;
use App\Models\StudentTeacherMap;
use App\Models\ParentStudentMap;

class StudentController extends Controller
{
    function list(Request $request)
    {
        $mainUrl = url('admin/student');

        $search = trim((string) ($request->search ?? ''));
        $teacherId = (int) ($request->teacher_id ?? 0);
        $classroomId = (int) ($request->classroom_id ?? 0);
        $batchId = (int) ($request->batch_id ?? 0);
        $page = max(1, (int) ($request->page ?? 1));
        $perPage = max(1, min(200, (int) ($request->per_page ?? 50)));

        $studentList = PortalUser::query()
            ->where('role', 2)
            ->with(['classroom', 'batch', 'teachers', 'studentClassroomMaps'])
            ->orderBy('id', 'desc');

        if ($search !== '') {
            $studentList->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if ($teacherId > 0) {
            $studentList->whereHas('teachers', function ($query) use ($teacherId) {
                $query->whereKey($teacherId);
            });
        }

        if ($classroomId > 0) {
            $studentList->where('classroom_id', $classroomId);
        }

        if ($batchId > 0) {
            $studentList->where('batch_id', $batchId);
        }

        $queryParams = array_filter([
            'search' => $search !== '' ? $search : null,
            'teacher_id' => $teacherId > 0 ? $teacherId : null,
            'classroom_id' => $classroomId > 0 ? $classroomId : null,
            'batch_id' => $batchId > 0 ? $batchId : null,
        ], static fn ($v) => $v !== null && $v !== '');

        $numRows = (clone $studentList)->count();

        $studentList = $studentList
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $teachers = PortalUser::where('role', 1)->orderBy('name')->get(['id', 'name']);

        $classroomsQuery = Classroom::query()->orderBy('name');
        if ($teacherId > 0) {
            $classroomsQuery->where('teacher_id', $teacherId);
        }
        $classrooms = $classroomsQuery->get(['id', 'name', 'teacher_id']);

        $batchesQuery = Batch::query()->with('classroom')->orderBy('name');
        if ($classroomId > 0) {
            $batchesQuery->where('classroom_id', $classroomId);
        } elseif ($teacherId > 0) {
            $batchesQuery->where('teacher_id', $teacherId);
        }
        $batches = $batchesQuery->get(['id', 'name', 'classroom_id', 'teacher_id']);

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

    function deleteStudent(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;
        
        if ($id) {
            $student = PortalUser::where('role', 2)->find($id);
            if ($student) {
                $batch = Batch::find($student->batch_id);
                $teacherId = $batch ? $batch->teacher_id : null;
                StudentClassroomMap::where('student_id', $student->id)->delete();
                StudentTeacherMap::where('student_id', $student->id)->delete();
                ParentStudentMap::where('student_id', $student->id)->delete();
                $student->delete();
                $this->response['status'] = 1;
                $this->response['msg'] = "Student deleted successfully";
                if ($teacherId) {
                    $this->response['redirect_url'] = url("admin/student");
                }
            } else {
                $this->response['error'] = "Student not found";
            }
        } else {
            $this->response['error'] = "Invalid request";
        }

        echo json_encode($this->response);
    }

    function add(Request $request)
    {
        $teachers = PortalUser::where('role', 1)->orderBy('name')->get(['id', 'name']);

        return view('admin.student.add', [
            'title' => 'Add student',
            'active_tab' => 'student',
            'teachers' => $teachers,
        ]);
    }

    function edit(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;
        if (! $id) {
            return redirect('admin/student');
        }

        $student = PortalUser::where('role', 2)->with('teachers')->find($id);
        if (! $student) {
            return redirect('admin/student');
        }

        $teachers = PortalUser::where('role', 1)->orderBy('name')->get(['id', 'name']);
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

    function enrollmentOptions(Request $request)
    {
        $teacherId = (int) ($request->teacher_id ?? 0);
        if ($teacherId < 1) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Invalid teacher.';
            echo json_encode($this->response);

            return;
        }

        if (! PortalUser::where('role', 1)->whereKey($teacherId)->exists()) {
            $this->response['status'] = 0;
            $this->response['error'] = 'Teacher not found.';
            echo json_encode($this->response);

            return;
        }

        $classrooms = Classroom::where('teacher_id', $teacherId)->orderBy('name')->get(['id', 'name']);
        $batches = Batch::where('teacher_id', $teacherId)->orderBy('name')->get(['id', 'name', 'classroom_id']);

        $this->response['status'] = 1;
        $this->response['data'] = [
            'classrooms' => $classrooms,
            'batches' => $batches,
        ];
        echo json_encode($this->response);
    }

    function form(Request $request)
    {
        $id = $request->id ? base64_decode($request->id) : null;
        $student = $id ? PortalUser::where('role', 2)->find($id) : new PortalUser();
        return view('admin.student.form', [
            'title' => 'Student',
            'active_tab' => 'student',
            'student' => $student,
        ]);
    }
}
