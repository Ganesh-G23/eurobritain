<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use App\Services\AcademicReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicReportController extends Controller
{
    public function __construct(
        private readonly AcademicReportService $reportService
    ) {}

    public function studentIndex()
    {
        $portalUser = session('portal_user');
        if (! is_array($portalUser) || (int) ($portalUser['role'] ?? 0) !== 2) {
            return redirect('user/dashboard');
        }

        $teacherId = (int) (session('selected_teacher_id') ?? 0);
        if ($teacherId <= 0) {
            return redirect('user/select-teacher');
        }

        $studentId = (int) ($portalUser['id'] ?? 0);
        $teacher = PortalUser::where('role', 1)->find($teacherId);

        return view('web.user.student.report', [
            'title' => 'My Reports',
            'active_tab' => 'student_report',
            'teacher' => $teacher,
            'student' => PortalUser::where('role', 2)->find($studentId),
            'download_base_url' => url('user/student/report/download'),
            'context_label' => $teacher ? 'Teacher: '.$teacher->name : null,
        ]);
    }

    public function studentDownload(string $type)
    {
        $portalUser = session('portal_user');
        if (! is_array($portalUser) || (int) ($portalUser['role'] ?? 0) !== 2) {
            return redirect('login');
        }

        $teacherId = (int) (session('selected_teacher_id') ?? 0);
        if ($teacherId <= 0) {
            return redirect('user/select-teacher');
        }

        $studentId = (int) ($portalUser['id'] ?? 0);

        return $this->downloadPdf($type, $studentId, $teacherId);
    }

    public function parentIndex()
    {
        $portalUser = session('portal_user');
        if (! is_array($portalUser) || (int) ($portalUser['role'] ?? 0) !== 3) {
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

        return view('web.user.parent.report', [
            'title' => 'Academic Reports',
            'active_tab' => 'parent_report',
            'student' => $student,
            'download_base_url' => url('user/parent/report/download'),
            'context_label' => 'Student: '.$student->name,
        ]);
    }

    public function parentDownload(string $type)
    {
        $portalUser = session('portal_user');
        if (! is_array($portalUser) || (int) ($portalUser['role'] ?? 0) !== 3) {
            return redirect('login');
        }

        $parentId = (int) ($portalUser['id'] ?? 0);
        $selectedStudentId = (int) (session('selected_student_id') ?? 0);
        if ($selectedStudentId <= 0 || ! $this->parentOwnsStudent($parentId, $selectedStudentId)) {
            return redirect('user/select-student');
        }

        return $this->downloadPdf($type, $selectedStudentId, null);
    }

    public function teacherDownload(Request $request, string $id, string $type)
    {
        $portalUser = session('portal_user');
        if (! is_array($portalUser) || (int) ($portalUser['role'] ?? 0) !== 1) {
            return redirect('login');
        }

        $teacherId = (int) ($portalUser['id'] ?? 0);
        $decodedId = base64_decode((string) $id, true);
        if ($decodedId === false) {
            $decodedId = $id;
        }
        $studentId = (int) $decodedId;

        $student = PortalUser::query()
            ->where('role', 2)
            ->whereHas('teachers', static fn ($q) => $q->whereKey($teacherId))
            ->find($studentId);

        if (! $student) {
            return redirect('user/teacher/students');
        }

        return $this->downloadPdf($type, $studentId, $teacherId);
    }

    private function downloadPdf(string $type, int $studentId, ?int $teacherId)
    {
        if (! in_array($type, AcademicReportService::validTypes(), true)) {
            abort(404);
        }

        try {
            $reportData = $this->reportService->build($type, $studentId, $teacherId);
        } catch (\InvalidArgumentException) {
            abort(404);
        } catch (\RuntimeException) {
            abort(404);
        }

        $filename = $this->reportService->suggestedFilename($reportData);

        return $this->reportService->pdfResponse($reportData, $filename);
    }

    private function parentOwnsStudent(int $parentId, int $studentId): bool
    {
        if ($parentId < 1 || $studentId <= 0) {
            return false;
        }

        return DB::table('portal_user')
            ->where('id', $studentId)
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
    }
}
