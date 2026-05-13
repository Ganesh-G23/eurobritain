<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\LeaveRequests;
use App\Models\PortalUser;
use App\Models\StudentClassroomMap;
use App\Notifications\PortalNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class StudentLeaveRequestController extends Controller
{
    public function index()
    {
        $user = PortalUser::query()->find(session('portal_user')['id']);
        $studentLeaveRequests = LeaveRequests::query()
            ->with(['classroom', 'batch'])
            ->where('student_id', (int) $user->id)
            ->orderByDesc('id')
            ->get();

        return view('web.user.student.leave_list', [
            'title' => 'Leave list',
            'active_tab' => 'student_leave',
            'student_leave_requests' => $studentLeaveRequests,
        ]);
    }

    public function create()
    {
        $user = PortalUser::with('studentClassroomMaps')->find(session('portal_user')['id']);
        $batchIds = $user->studentClassroomMaps->pluck('batch_id')->unique()->filter()->values();
        if ($batchIds->isNotEmpty()) {
            $batches = Batch::whereIn('id', $batchIds)->orderBy('name')->get();
            $classrooms = Classroom::whereIn('id', $batches->pluck('classroom_id')->unique()->filter()->values())->orderBy('name')->get();
        } else {
            $teacherIds = $user->teachers()->pluck('portal_user.id');
            $classrooms = Classroom::whereIn('teacher_id', $teacherIds)->get();
            $batches = Batch::whereIn('classroom_id', $classrooms->pluck('id'))->get();
        }

        return view('web.user.student.leave_request', [
            'title' => 'Request leave',
            'active_tab' => 'student_leave',
            'classrooms' => $classrooms,
            'batches' => $batches,
        ]);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'classroom_id' => 'required',
            'batch_id' => 'required',
            'from_date' => 'required',
            'to_date' => 'required',
            'reason' => 'required',
        ]);

        if (!$validation->fails()) {
            $user = PortalUser::find(session('portal_user')['id']);

            $teacherId = 0;
            $map = StudentClassroomMap::where('student_id', $user->id)
                ->where('classroom_id', $request->classroom_id)
                ->where('batch_id', $request->batch_id)
                ->first();
            if ($map) {
                $teacherId = (int) $map->teacher_id;
            } elseif ((int) $user->classroom_id === (int) $request->classroom_id && (int) $user->batch_id === (int) $request->batch_id) {
                $teacherId = (int) ($user->default_teacher_id ?? Classroom::whereKey($request->classroom_id)->value('teacher_id') ?? 0);
            }
            if ($teacherId < 1) {
                $teacherId = (int) ($user->teachers()->first()->id ?? 0);
            }

            $leaveRequest = new LeaveRequests();
            $leaveRequest->student_id = $user->id;
            $leaveRequest->teacher_id = $teacherId;
            $leaveRequest->classroom_id = $request->classroom_id;
            $leaveRequest->batch_id = $request->batch_id;
            $leaveRequest->from_date = $request->from_date;
            $leaveRequest->to_date = $request->to_date;
            $leaveRequest->reason = $request->reason;
            $leaveRequest->status = 'pending';
            $leaveRequest->save();

            if (Schema::hasTable('notifications') && $teacherId > 0) {
                $teacher = PortalUser::query()
                    ->whereKey($teacherId)
                    ->where('role', 1)
                    ->whereNull('deleted_at')
                    ->first();
                if ($teacher) {
                    $studentName = trim((string) ($user->name ?? ''));
                    if ($studentName === '') {
                        $studentName = 'A student';
                    }
                    $fromDate = Carbon::parse((string) $leaveRequest->from_date)->format('d M Y');
                    $toDate = Carbon::parse((string) $leaveRequest->to_date)->format('d M Y');
                    $dateLabel = $fromDate === $toDate ? $fromDate : ($fromDate . ' to ' . $toDate);
                    $title = 'New leave request';
                    $message = $studentName . ' submitted a leave request for ' . $dateLabel . '.';
                    $teacher->notify(new PortalNotification($title, $message, 'leave', [
                        'leave_request_id' => (int) $leaveRequest->id,
                        'student_id' => (int) $leaveRequest->student_id,
                        'teacher_id' => (int) $teacherId,
                        'classroom_id' => (int) $leaveRequest->classroom_id,
                        'batch_id' => (int) $leaveRequest->batch_id,
                        'from_date' => (string) $leaveRequest->from_date,
                        'to_date' => (string) $leaveRequest->to_date,
                        'status' => 'pending',
                    ]));
                    
                }
            }

            $this->response['status'] = 1;
            $this->response['msg'] = 'Leave request sent';
            $this->response['redirect_url'] = url('user/student/leave');
        } else {
            $this->response['error_array'] = formatErrors($validation->errors()->toArray());
        }

        echo json_encode($this->response);
    }
}
