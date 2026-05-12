<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Classroom;
use App\Models\Fees;
use App\Models\PortalUser;
use App\Models\StudentClassroomMap;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeesController extends Controller
{
    public const PAYMENT_MODES = [
        'Cash' => 'Cash',
        'UPI' => 'UPI',
        'Card' => 'Card',
        'Bank Transfer' => 'Bank Transfer',
        'Cheque' => 'Cheque',
        'Online' => 'Online',
        'Other' => 'Other',
    ];

    protected function requireTeacher()
    {
        $portal = session('portal_user');
        $userId = (int) ($portal['id'] ?? 0);
        $role = (int) ($portal['role'] ?? 0);

        if (! $userId || $role !== 1) {
            return [null, redirect('login')];
        }

        return [$userId, null];
    }

    /**
     * @return array<int, array<int, array{id:int, name:string, email:?string}>>
     */
    protected function studentsByBatchForTeacher(int $teacherId): array
    {
        $rows = StudentClassroomMap::query()
            ->where('teacher_id', $teacherId)
            ->whereNotNull('batch_id')
            ->with(['student' => static function ($q) {
                $q->select(['id', 'name', 'email', 'role'])->where('role', 2);
            }])
            ->get(['batch_id', 'student_id']);

        $byBatch = [];
        foreach ($rows as $row) {
            if (! $row->student) {
                continue;
            }
            $bid = (int) $row->batch_id;
            $sid = (int) $row->student_id;
            if (! isset($byBatch[$bid][$sid])) {
                $byBatch[$bid][$sid] = [
                    'id' => $sid,
                    'name' => (string) $row->student->name,
                    'email' => $row->student->email !== null ? (string) $row->student->email : null,
                ];
            }
        }

        foreach ($byBatch as $bid => $students) {
            $list = array_values($students);
            usort($list, static fn ($a, $b) => strcmp($a['name'], $b['name']));
            $byBatch[$bid] = $list;
        }

        return $byBatch;
    }

    public function fees()
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        return view('web.user.teacher.fees', [
            'title' => 'Fees',
            'active_tab' => 'fees',
            'mode' => 'create',
            'fee' => null,
            'payment_modes' => self::PAYMENT_MODES,
            'classrooms' => Classroom::where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'batches' => Batch::where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name', 'classroom_id']),
            'students_by_batch' => $this->studentsByBatchForTeacher($teacherId),
        ]);
    }

    public function edit($id)
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        $fee = Fees::where('teacher_id', $teacherId)->findOrFail((int) $id);

        return view('web.user.teacher.fees', [
            'title' => 'Edit Fee',
            'active_tab' => 'fees',
            'mode' => 'edit',
            'fee' => $fee,
            'payment_modes' => self::PAYMENT_MODES,
            'classrooms' => Classroom::where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'batches' => Batch::where('teacher_id', $teacherId)
                ->orderBy('name')
                ->get(['id', 'name', 'classroom_id']),
            'students_by_batch' => $this->studentsByBatchForTeacher($teacherId),
        ]);
    }

    public function feesList()
    {
        [$teacherId, $redirect] = $this->requireTeacher();
        if ($redirect) {
            return $redirect;
        }

        return view('web.user.teacher.fees_list', [
            'title' => 'Fees List',
            'active_tab' => 'fees',
            'fees' => Fees::with([
                'classroom:id,name',
                'batch:id,name',
                'student:id,name,email',
            ])
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
            'student_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['required', 'string', Rule::in(array_keys(self::PAYMENT_MODES))],
            'remark' => ['nullable', 'string', 'max:2000'],
        ]);

        $classroom = Classroom::where('teacher_id', $teacherId)->find($validated['classroom_id']);
        if (! $classroom) {
            return back()
                ->withErrors(['classroom_id' => 'Invalid classroom selected.'])
                ->withInput();
        }

        $batch = Batch::where('teacher_id', $teacherId)
            ->where('classroom_id', $validated['classroom_id'])
            ->find($validated['batch_id']);

        if (! $batch) {
            return back()
                ->withErrors(['batch_id' => 'Invalid batch selected for the classroom.'])
                ->withInput();
        }

        $enrolled = StudentClassroomMap::where('teacher_id', $teacherId)
            ->where('classroom_id', $validated['classroom_id'])
            ->where('batch_id', $validated['batch_id'])
            ->where('student_id', $validated['student_id'])
            ->exists();

        if (! $enrolled) {
            return back()
                ->withErrors(['student_id' => 'Selected student is not in this batch for this classroom.'])
                ->withInput();
        }

        $fee = ! empty($validated['id'])
            ? Fees::where('teacher_id', $teacherId)->findOrFail((int) $validated['id'])
            : new Fees;

        $fee->teacher_id = $teacherId;
        $fee->classroom_id = $validated['classroom_id'];
        $fee->batch_id = $validated['batch_id'];
        $fee->student_id = $validated['student_id'];
        $fee->amount = $validated['amount'];
        $fee->payment_mode = $validated['payment_mode'];
        $fee->remark = isset($validated['remark']) ? trim((string) $validated['remark']) : null;
        if ($fee->remark === '') {
            $fee->remark = null;
        }
        $fee->save();

        $message = ! empty($validated['id']) ? 'Fee updated successfully.' : 'Fee saved successfully.';

        return redirect('user/teacher/fees/list')->with('success', $message);
    }

    public function parentFeesList()
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

        $studentName = (string) (PortalUser::query()->whereKey($selectedStudentId)->value('name') ?? '');

        $fees = Fees::query()
            ->with(['teacher:id,name', 'classroom:id,name', 'batch:id,name'])
            ->where('student_id', $selectedStudentId)
            ->orderByDesc('id')
            ->get();

        return view('web.user.parent.fees_list', [
            'title' => 'Fees',
            'active_tab' => 'fees',
            'student_name' => $studentName,
            'selected_student_id' => $selectedStudentId,
            'fees' => $fees,
        ]);
    }
}
