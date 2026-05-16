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
        ]);
    }

    public function edit($id)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $timeline = TimeLine::where('teacher_id', $teacherId)
            ->findOrFail((int) $id);

        return view('web.user.teacher.timelines', [
            'title' => 'Edit Timeline',
            'active_tab' => 'timelines',
            'mode' => 'edit',
            'timeline' => $timeline,
            'classrooms' => Classroom::where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function timelineList()
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $batchNames = Batch::where('teacher_id', $teacherId)
            ->pluck('name', 'id');

        return view('web.user.teacher.timeline_list', [
            'title' => 'Timeline List',
            'active_tab' => 'timelines',
            'batch_names' => $batchNames,
            'timelines' => TimeLine::with(['classroom:id,name'])
                ->where('teacher_id', $teacherId)
                ->orderByDesc('date')
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
            'batch_ids' => ['required', 'array', 'min:1'],
            'batch_ids.*' => ['integer', 'distinct'],
            'topic' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
        ]);

        $classroom = Classroom::where('teacher_id', $teacherId)->find($validated['classroom_id']);
        if (!$classroom) {
            return back()
                ->withErrors(['classroom_id' => 'Invalid classroom selected.'])
                ->withInput();
        }

        $batchIds = collect($validated['batch_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $validBatchIds = Batch::where('teacher_id', $teacherId)
            ->where('classroom_id', $validated['classroom_id'])
            ->whereIn('id', $batchIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($validBatchIds->count() !== $batchIds->count()) {
            return back()
                ->withErrors(['batch_ids' => 'One or more batches are invalid for the selected classroom.'])
                ->withInput();
        }

        $timeline = !empty($validated['id'])
            ? TimeLine::where('teacher_id', $teacherId)->findOrFail((int) $validated['id'])
            : new TimeLine();

        $timeline->teacher_id = $teacherId;
        $timeline->classroom_id = $validated['classroom_id'];
        $timeline->batch_ids = $validBatchIds->all();
        $timeline->topic = trim($validated['topic']);
        $timeline->date = $validated['date'];
        $timeline->save();

        $message = !empty($validated['id']) ? 'Timeline updated successfully.' : 'Timeline saved successfully.';

        return redirect('user/teacher/timelines/list')->with('success', $message);
    }
}
