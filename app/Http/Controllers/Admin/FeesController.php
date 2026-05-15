<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fees;
use App\Models\PortalUser;
use Illuminate\Http\Request;

class FeesController extends Controller
{
    public function index(Request $request)
    {
        $mainUrl = url('admin/fees');

        $teacherId = (int) ($request->teacher_id ?? 0);
        $studentId = (int) ($request->student_id ?? 0);
        $fromDate = trim((string) ($request->from_date ?? ''));
        $toDate = trim((string) ($request->to_date ?? ''));

        $page = max(1, (int) ($request->page ?? 1));
        $perPage = max(1, min(200, (int) ($request->per_page ?? 50)));

        $feesQuery = Fees::query()
            ->with([
                'teacher:id,name',
                'student:id,name',
                'classroom:id,name',
                'batch:id,name',
            ])
            ->orderByDesc('id');

        if ($teacherId > 0) {
            $feesQuery->where('teacher_id', $teacherId);
        }

        if ($studentId > 0) {
            $feesQuery->where('student_id', $studentId);
        }

        if ($fromDate !== '') {
            $feesQuery->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate !== '') {
            $feesQuery->whereDate('created_at', '<=', $toDate);
        }

        $queryParams = array_filter(
            [
                'teacher_id' => $teacherId > 0 ? $teacherId : null,
                'student_id' => $studentId > 0 ? $studentId : null,
                'from_date' => $fromDate !== '' ? $fromDate : null,
                'to_date' => $toDate !== '' ? $toDate : null,
            ],
            static fn ($v) => $v !== null && $v !== '',
        );

        $numRows = (clone $feesQuery)->count();

        $fees = $feesQuery
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $teachers = PortalUser::query()
            ->where('role', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $students = PortalUser::query()
            ->where('role', 2)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.fees.list', [
            'title' => 'Fees',
            'active_tab' => 'fees',
            'fees' => $fees,
            'teachers' => $teachers,
            'students' => $students,
            'teacher_id' => $teacherId,
            'student_id' => $studentId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'num_rows' => $numRows,
            'page' => $page,
            'per_page' => $perPage,
            'query_params' => $queryParams,
            'main_url' => $mainUrl,
        ]);
    }
}
