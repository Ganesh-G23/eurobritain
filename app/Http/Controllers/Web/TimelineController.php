<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\TimeLine;
use Illuminate\Http\Request;

class TimelineController extends Controller
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

    public function timeline()
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        return view('web.user.teacher.timelines', [
            'title' => 'Timelines',
            'active_tab' => 'timelines',
            'mode' => 'create',
            'timeline' => null,
            'classrooms' => Classroom::where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'batches' => Batch::where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name', 'classroom_id']),
        ]);
    }

    public function edit($id)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $timeline = TimeLine::where('teacher_id', $teacherId)->findOrFail((int) $id);

        return view('web.user.teacher.timelines', [
            'title' => 'Edit Timeline',
            'active_tab' => 'timelines',
            'mode' => 'edit',
            'timeline' => $timeline,
            'classrooms' => Classroom::where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'batches' => Batch::where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name', 'classroom_id']),
        ]);
    }

    public function timelineList()
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        return view('web.user.teacher.timeline_list', [
            'title' => 'Timeline List',
            'active_tab' => 'timelines',
            'timelines' => TimeLine::with(['classroom:id,name', 'batch:id,name'])
                ->where('teacher_id', $teacherId)
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $validated = $request->validate([
            'id' => ['nullable', 'integer'],
            'classroom_id' => ['required', 'integer'],
            'batch_id' => ['required', 'integer'],
            'topic' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'integer', 'in:0,1,2'],
        ]);

        $classroom = Classroom::where('teacher_id', $teacherId)->find($validated['classroom_id']);
        if (!$classroom) {
            return back()
                ->withErrors(['classroom_id' => 'Invalid classroom selected.'])
                ->withInput();
        }

        $batch = Batch::where('teacher_id', $teacherId)
            ->where('classroom_id', $validated['classroom_id'])
            ->find($validated['batch_id']);

        if (!$batch) {
            return back()
                ->withErrors(['batch_id' => 'Invalid batch selected for the classroom.'])
                ->withInput();
        }

        $timeline = !empty($validated['id'])
            ? TimeLine::where('teacher_id', $teacherId)->findOrFail((int) $validated['id'])
            : new TimeLine();

        $timeline->teacher_id = $teacherId;
        $timeline->classroom_id = $validated['classroom_id'];
        $timeline->batch_id = $validated['batch_id'];
        $timeline->topic = trim($validated['topic']);
        $timeline->start_date = $validated['start_date'];
        $timeline->end_date = $validated['end_date'];
        $timeline->status = (int) $validated['status'];
        $timeline->save();

        $message = !empty($validated['id']) ? 'Timeline updated successfully.' : 'Timeline saved successfully.';
        return redirect('user/teacher/timelines/list')->with('success', $message);
    }
}
