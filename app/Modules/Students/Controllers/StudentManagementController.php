<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DisciplineComplaint;
use App\Models\FeeChallan;
use App\Models\MedicalHistory;
use App\Models\MedicalReferral;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Services\StudentPhotoService;
use App\Services\StudentResultService;
use App\Modules\Fees\Services\FeeManagementService;
use App\Modules\Students\Requests\BulkAddStudentsRequest;
use App\Modules\Students\Requests\BulkDeleteStudentsRequest;
use App\Modules\Students\Requests\ImportStudentsWorkbookRequest;
use App\Modules\Students\Requests\StoreStudentRequest;
use App\Modules\Students\Requests\UpdateStudentPhotoRequest;
use App\Modules\Students\Requests\UpdateStudentRequest;
use App\Modules\Students\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class StudentManagementController extends Controller
{
    public function __construct(
        private readonly StudentPhotoService $studentPhotoService,
        private readonly StudentResultService $studentResultService
    )
    {
    }

    public function index(): View
    {
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        return view('modules.students.index', [
            'classes' => $classes,
        ]);
    }

    public function import(
        ImportStudentsWorkbookRequest $request,
        StudentImportService $importService
    ): RedirectResponse {
        try {
            $summary = $importService->importUploadedFile(
                $request->file('workbook'),
                (bool) $request->boolean('update_existing', true)
            );
        } catch (Throwable $exception) {
            report($exception);

            $message = app()->isLocal()
                ? 'Import failed: '.$exception->getMessage()
                : 'Import failed due to an unexpected error.';

            return redirect()
                ->route('admin.students.index')
                ->with('students_import_error', $message);
        }

        return redirect()
            ->route('admin.students.index')
            ->with('students_import_summary', $summary);
    }

    public function bulkAdd(
        BulkAddStudentsRequest $request,
        StudentImportService $importService
    ): RedirectResponse {
        try {
            $summary = $importService->importFromBulkText(
                (string) $request->input('rows'),
                (bool) $request->boolean('update_existing', false)
            );
        } catch (Throwable $exception) {
            report($exception);

            $message = app()->isLocal()
                ? 'Bulk add failed: '.$exception->getMessage()
                : 'Bulk add failed due to an unexpected error.';

            return redirect()
                ->route('admin.students.index')
                ->with('students_bulk_error', $message);
        }

        return redirect()
            ->route('admin.students.index')
            ->with('students_bulk_summary', $summary);
    }

    public function bulkDelete(BulkDeleteStudentsRequest $request): JsonResponse
    {
        $ids = collect($request->validated('ids'))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $deleted = Student::query()->whereIn('id', $ids)->delete();

        return response()->json([
            'message' => 'Selected students deleted successfully.',
            'deleted' => (int) $deleted,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $search = (string) $request->input('search', '');
        $perPage = (int) $request->input('per_page', 10);
        $searchPrefix = $search !== '' ? $search.'%' : null;
        $searchContains = $search !== '' ? '%'.$search.'%' : null;

        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->when($search !== '', function ($query) use ($searchPrefix, $searchContains): void {
                $query->where(function ($q) use ($searchPrefix, $searchContains): void {
                    $q->where('student_id', 'like', $searchPrefix)
                        ->orWhere('name', 'like', $searchContains)
                        ->orWhere('father_name', 'like', $searchContains)
                        ->orWhere('contact', 'like', $searchPrefix)
                        ->orWhereHas('classRoom', function ($classQuery) use ($searchContains): void {
                            $classQuery->where('name', 'like', $searchContains)
                                ->orWhere('section', 'like', $searchContains);
                        });
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $rows = collect($students->items())->map(function (Student $student): array {
            return [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->name,
                'father_name' => $student->father_name,
                'class_name' => trim(($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? '')),
                'contact' => $student->contact,
                'status' => $student->status,
                'profile_url' => route('admin.students.show', $student),
                'edit_url' => route('admin.students.edit', $student),
                'delete_url' => route('admin.students.delete-page', $student),
                'id_card_url' => route('idcards.single', ['student' => $student]),
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'total' => $students->total(),
                'per_page' => $students->perPage(),
            ],
        ]);
    }

    public function create(): View
    {
        $classes = SchoolClass::query()->orderBy('name')->orderBy('section')->get();

        return view('modules.students.create', compact('classes'));
    }

    public function store(StoreStudentRequest $request)
    {
        $student = Student::query()->create($request->validated());

        return redirect()
            ->route('admin.students.show', $student)
            ->with('success', 'Student created successfully.');
    }

    public function edit(Student $student): View
    {
        $classes = SchoolClass::query()->orderBy('name')->orderBy('section')->get();

        return view('modules.students.edit', compact('student', 'classes'));
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $student->update($request->validated());

        return redirect()
            ->route('admin.students.show', $student)
            ->with('success', 'Student updated successfully.');
    }

    public function updatePhoto(UpdateStudentPhotoRequest $request, Student $student): RedirectResponse
    {
        $removePhoto = (bool) $request->boolean('remove_photo');
        $uploadedPhoto = $request->file('photo');
        $capturedPhoto = trim((string) $request->input('photo_capture', ''));

        if (! $removePhoto && ! $uploadedPhoto && $capturedPhoto === '') {
            return back()->withErrors([
                'photo' => 'Upload a photo or capture one from the camera.',
            ]);
        }

        $previousPhotoPath = $this->studentPhotoService->normalizePath((string) $student->photo_path);
        $nextPhotoPath = $previousPhotoPath;
        $successMessage = 'Student photo updated successfully.';

        try {
            if ($uploadedPhoto) {
                $nextPhotoPath = $this->studentPhotoService->storeUploadedPhoto($uploadedPhoto);
            } elseif ($capturedPhoto !== '') {
                $nextPhotoPath = $this->studentPhotoService->storeCapturedPhoto($capturedPhoto);
            } elseif ($removePhoto) {
                $nextPhotoPath = null;
                $successMessage = 'Student photo removed successfully.';
            }
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'photo' => $exception->getMessage(),
            ]);
        }

        if ($nextPhotoPath !== $previousPhotoPath) {
            $student->forceFill(['photo_path' => $nextPhotoPath])->save();

            if ($previousPhotoPath !== null) {
                $this->studentPhotoService->deletePhoto($previousPhotoPath);
            }
        }

        return back()->with('success', $successMessage);
    }

    public function deletePage(Student $student): View
    {
        return view('modules.students.delete', compact('student'));
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json(['message' => 'Student deleted successfully.']);
    }

    public function show(Request $request, Student $student): View
    {
        $student->load(['classRoom']);

        $resultSessions = $this->studentResultService->availableSessionsForStudent((int) $student->id);
        $selectedResultSession = $this->studentResultService->resolveRequestedSession(
            is_string($request->query('session')) ? (string) $request->query('session') : null,
            $resultSessions
        );

        $attendanceStats = $this->attendanceStats((int) $student->id);
        $resultStats = $this->resultStats((int) $student->id, $selectedResultSession);
        $feeStats = $this->feeStats((int) $student->id);

        $medicalVisits = MedicalHistory::query()
            ->where('student_id', (int) $student->id)
            ->count();

        $tabs = [
            'overview' => 'Overview',
            'subjects' => 'Subjects',
            'attendance' => 'Attendance',
            'results' => 'Results',
            'fee' => 'Fee',
            'medical' => 'Medical',
            'discipline' => 'Discipline',
        ];

        $isPrincipal = auth()->user()?->hasRole('Principal') ?? false;
        $tabEndpointTemplate = $isPrincipal
            ? route('principal.students.tabs', ['student' => $student, 'tab' => '__TAB__'])
            : route('admin.students.tabs', ['student' => $student, 'tab' => '__TAB__']);

        return view('modules.students.profile', [
            'student' => $student,
            'tabs' => $tabs,
            'tabEndpointTemplate' => $tabEndpointTemplate,
            'resultSessions' => $resultSessions,
            'selectedResultSession' => $selectedResultSession,
            'summaryStats' => [
                'attendance_percentage' => $attendanceStats['attendance_percentage'],
                'current_grade' => $resultStats['grade'],
                'current_grade_percentage' => $resultStats['average_percentage'],
                'pending_fee' => $feeStats['pending_amount'],
                'medical_visits' => $medicalVisits,
            ],
        ]);
    }

    public function tabContent(Request $request, Student $student, string $tab): JsonResponse
    {
        $allowedTabs = [
            'overview',
            'subjects',
            'attendance',
            'results',
            'fee',
            'medical',
            'discipline',
        ];

        if (! in_array($tab, $allowedTabs, true)) {
            return response()->json(['message' => 'Invalid tab selection.'], 404);
        }

        $resultSessions = $this->studentResultService->availableSessionsForStudent((int) $student->id);
        $selectedResultSession = $this->studentResultService->resolveRequestedSession(
            is_string($request->query('session')) ? (string) $request->query('session') : null,
            $resultSessions
        );

        $student->load(['classRoom']);

        $viewData = match ($tab) {
            'overview' => $this->overviewTabData($student, $selectedResultSession, $resultSessions),
            'subjects' => $this->subjectsTabData($student),
            'attendance' => $this->attendanceTabData($student),
            'results' => $this->resultsTabData($student, $selectedResultSession, $resultSessions),
            'fee' => $this->feeTabData($student),
            'medical' => $this->medicalTabData($student),
            'discipline' => $this->disciplineTabData($student),
            default => [],
        };

        $html = view('modules.students.profile-tabs.'.$tab, array_merge(
            ['student' => $student],
            $viewData
        ))->render();

        return response()->json([
            'tab' => $tab,
            'html' => $html,
        ]);
    }

    private function subjectsForStudent(Student $student): Collection
    {
        $subjects = $student->sessionSubjects()
            ->select('subjects.id', 'subjects.name', 'subjects.code')
            ->orderBy('subjects.name')
            ->distinct()
            ->get();

        if ($subjects->isNotEmpty()) {
            return $subjects;
        }

        return $student->subjects()
            ->select('subjects.id', 'subjects.name', 'subjects.code')
            ->orderBy('subjects.name')
            ->get();
    }

    private function attendanceRecords(int $studentId, int $limit = 60): array
    {
        $modernAttendance = Attendance::query()
            ->where('student_id', $studentId)
            ->orderByDesc('date')
            ->limit($limit)
            ->get(['id', 'student_id', 'date', 'status']);

        if ($modernAttendance->isNotEmpty()) {
            return [
                'source' => 'attendance',
                'records' => $modernAttendance,
            ];
        }

        $legacyAttendance = StudentAttendance::query()
            ->where('student_id', $studentId)
            ->orderByDesc('date')
            ->limit($limit)
            ->get(['id', 'student_id', 'date', 'status', 'remarks']);

        return [
            'source' => 'student_attendance',
            'records' => $legacyAttendance,
        ];
    }

    private function attendanceStats(int $studentId): array
    {
        $modernTotal = Attendance::query()
            ->where('student_id', $studentId)
            ->count();

        if ($modernTotal > 0) {
            $stats = Attendance::query()
                ->where('student_id', $studentId)
                ->selectRaw(
                    "COUNT(*) as total_count,
                    SUM(CASE WHEN lower(status) IN ('present', 'p') THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN lower(status) IN ('absent', 'a') THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN lower(status) IN ('leave', 'l') THEN 1 ELSE 0 END) as leave_count"
                )
                ->first();
        } else {
            $stats = StudentAttendance::query()
                ->where('student_id', $studentId)
                ->selectRaw(
                    "COUNT(*) as total_count,
                    SUM(CASE WHEN lower(status) IN ('present', 'p') THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN lower(status) IN ('absent', 'a') THEN 1 ELSE 0 END) as absent_count,
                    SUM(CASE WHEN lower(status) IN ('leave', 'l') THEN 1 ELSE 0 END) as leave_count"
                )
                ->first();
        }

        $total = (int) ($stats?->total_count ?? 0);
        $present = (int) ($stats?->present_count ?? 0);
        $absent = (int) ($stats?->absent_count ?? 0);
        $leave = (int) ($stats?->leave_count ?? 0);

        $attendancePercentage = $total > 0
            ? round(($present / $total) * 100, 2)
            : 0.0;

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'leave' => $leave,
            'attendance_percentage' => $attendancePercentage,
        ];
    }

    private function resultStats(int $studentId, string $session): array
    {
        return $this->studentResultService->getStudentResultStats($studentId, $session);
    }

    private function feeStats(int $studentId): array
    {
        $challans = FeeChallan::query()
            ->where('student_id', $studentId)
            ->withSum('payments as paid_total', 'amount_paid')
            ->orderByDesc('due_date')
            ->orderByDesc('issue_date')
            ->get([
                'id',
                'challan_number',
                'session',
                'month',
                'issue_date',
                'due_date',
                'total_amount',
                'status',
                'paid_at',
            ]);

        $today = now()->toDateString();
        $totals = $challans->reduce(function (array $carry, FeeChallan $challan) use ($today): array {
            $totalAmount = (float) $challan->total_amount;
            $paidAmount = min((float) ($challan->paid_total ?? 0), $totalAmount);
            $dueAmount = max($totalAmount - $paidAmount, 0);
            $isOverdue = $dueAmount > 0
                && $challan->due_date !== null
                && $challan->due_date->toDateString() < $today;

            $carry['total_billed'] += $totalAmount;
            $carry['total_paid'] += $paidAmount;
            $carry['pending_amount'] += $dueAmount;
            $carry['pending_challans'] += $dueAmount > 0 ? 1 : 0;
            $carry['overdue_challans'] += $isOverdue ? 1 : 0;

            return $carry;
        }, [
            'total_billed' => 0.0,
            'total_paid' => 0.0,
            'pending_amount' => 0.0,
            'pending_challans' => 0,
            'overdue_challans' => 0,
        ]);

        $dueSummary = app(FeeManagementService::class)->dueSummaryForStudent($studentId);
        $legacyPending = round((float) $totals['pending_amount'], 2);
        $computedPending = round((float) ($dueSummary['total_due'] ?? 0), 2);

        return array_merge($totals, [
            'legacy_pending_amount' => $legacyPending,
            'installment_due' => round((float) ($dueSummary['installment_due'] ?? 0), 2),
            'arrears_due' => round((float) ($dueSummary['arrears_due'] ?? 0), 2),
            'pending_amount' => $computedPending > 0 ? $computedPending : $legacyPending,
            'challans' => $challans,
        ]);
    }

    private function overviewTabData(Student $student, string $session, array $resultSessions): array
    {
        $subjects = $this->subjectsForStudent($student);
        $attendanceStats = $this->attendanceStats((int) $student->id);
        $resultStats = $this->resultStats((int) $student->id, $session);
        $feeStats = $this->feeStats((int) $student->id);
        $attendanceData = $this->attendanceRecords((int) $student->id, 8);

        $recentResults = $this->studentResultService->getRecentStudentResults((int) $student->id, $session, 6);

        $recentMedical = MedicalHistory::query()
            ->where('student_id', (int) $student->id)
            ->orderByDesc('visit_date')
            ->limit(5)
            ->get();

        $openDisciplineCount = DisciplineComplaint::query()
            ->where('student_id', (int) $student->id)
            ->whereNotIn('status', ['closed', 'resolved'])
            ->count();

        return [
            'subjectsCount' => $subjects->count(),
            'attendanceStats' => $attendanceStats,
            'resultStats' => $resultStats,
            'resultSession' => $session,
            'resultSessions' => $resultSessions,
            'feeStats' => $feeStats,
            'recentAttendance' => $attendanceData['records'],
            'recentResults' => $recentResults,
            'recentChallans' => $feeStats['challans']->take(6),
            'recentMedical' => $recentMedical,
            'openDisciplineCount' => $openDisciplineCount,
        ];
    }

    private function subjectsTabData(Student $student): array
    {
        $subjects = $this->subjectsForStudent($student);
        $matrixAssignments = $student->subjectMatrixAssignments()
            ->with(['subject:id,name,code', 'subjectGroup:id,name'])
            ->orderByDesc('updated_at')
            ->get();

        $groupedMatrix = $matrixAssignments
            ->groupBy(fn ($item) => $item->subject_group_id ? 'group_'.$item->subject_group_id : 'common')
            ->map(function (Collection $rows): array {
                $first = $rows->first();

                return [
                    'group_name' => $first?->subjectGroup?->name ?? 'Common Subjects',
                    'subjects' => $rows
                        ->map(fn ($row): array => [
                            'name' => $row->subject?->name ?? '-',
                            'code' => $row->subject?->code,
                        ])
                        ->unique('name')
                        ->values(),
                ];
            })
            ->values();

        return [
            'subjects' => $subjects,
            'groupedMatrix' => $groupedMatrix,
        ];
    }

    private function attendanceTabData(Student $student): array
    {
        $attendanceData = $this->attendanceRecords((int) $student->id, 120);
        $records = $attendanceData['records'];
        $attendanceStats = $this->attendanceStats((int) $student->id);

        $monthlySummary = $records
            ->groupBy(fn ($row) => optional($row->date)->format('Y-m'))
            ->map(function (Collection $rows, string $month): array {
                $present = $rows->filter(fn ($row) => in_array(strtolower((string) $row->status), ['present', 'p'], true))->count();
                $absent = $rows->filter(fn ($row) => in_array(strtolower((string) $row->status), ['absent', 'a'], true))->count();
                $leave = $rows->filter(fn ($row) => in_array(strtolower((string) $row->status), ['leave', 'l'], true))->count();

                return [
                    'month' => $month,
                    'total' => $rows->count(),
                    'present' => $present,
                    'absent' => $absent,
                    'leave' => $leave,
                    'percentage' => $rows->count() > 0
                        ? round(($present / $rows->count()) * 100, 2)
                        : 0.0,
                ];
            })
            ->take(6)
            ->values();

        return [
            'attendanceSource' => $attendanceData['source'],
            'attendanceRecords' => $records,
            'attendanceStats' => $attendanceStats,
            'monthlySummary' => $monthlySummary,
        ];
    }

    private function resultsTabData(Student $student, string $session, array $resultSessions): array
    {
        $results = $this->studentResultService->getRecentStudentResults((int) $student->id, $session, 80);

        $resultStats = $this->resultStats((int) $student->id, $session);

        return [
            'results' => $results,
            'resultStats' => $resultStats,
            'resultSession' => $session,
            'resultSessions' => $resultSessions,
        ];
    }

    private function feeTabData(Student $student): array
    {
        $feeStats = $this->feeStats((int) $student->id);
        $feeService = app(FeeManagementService::class);
        $dueSummary = $feeService->dueSummaryForStudent((int) $student->id);

        $challans = $feeStats['challans']->map(function (FeeChallan $challan): array {
            $totalAmount = (float) $challan->total_amount;
            $paidAmount = min((float) ($challan->paid_total ?? 0), $totalAmount);
            $dueAmount = max($totalAmount - $paidAmount, 0);

            return [
                'id' => (int) $challan->id,
                'challan_number' => $challan->challan_number,
                'session' => $challan->session,
                'month' => $challan->month,
                'issue_date' => $challan->issue_date,
                'due_date' => $challan->due_date,
                'status' => $challan->status,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
            ];
        });

        $installmentSchedule = $feeService->installmentScheduleForStudent((int) $student->id)
            ->map(function ($installment): array {
                $amount = round((float) $installment->amount, 2);
                $paidAmount = round((float) $installment->paid_amount, 2);
                $remaining = round(max($amount - $paidAmount, 0), 2);

                return [
                    'id' => (int) $installment->id,
                    'plan_id' => (int) $installment->fee_installment_plan_id,
                    'plan_session' => (string) ($installment->plan?->session ?? ''),
                    'plan_type' => (string) ($installment->plan?->plan_type ?? ''),
                    'plan_name' => (string) ($installment->plan?->plan_name ?? ''),
                    'installment_no' => (int) $installment->installment_no,
                    'title' => (string) ($installment->title ?? ''),
                    'due_date' => $installment->due_date,
                    'amount' => $amount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remaining,
                    'status' => (string) $installment->status,
                ];
            })
            ->values();

        $manualArrears = $feeService->arrearsForStudent((int) $student->id)
            ->map(function ($arrear): array {
                $amount = round((float) $arrear->amount, 2);
                $paidAmount = round((float) $arrear->paid_amount, 2);
                $remaining = round(max($amount - $paidAmount, 0), 2);

                return [
                    'id' => (int) $arrear->id,
                    'session' => $arrear->session,
                    'title' => (string) $arrear->title,
                    'amount' => $amount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remaining,
                    'status' => (string) $arrear->status,
                    'due_date' => $arrear->due_date,
                    'notes' => $arrear->notes,
                ];
            })
            ->values();

        return [
            'feeStats' => $feeStats,
            'dueSummary' => $dueSummary,
            'challans' => $challans,
            'installmentSchedule' => $installmentSchedule,
            'manualArrears' => $manualArrears,
        ];
    }

    private function medicalTabData(Student $student): array
    {
        $medicalHistory = MedicalHistory::query()
            ->where('student_id', (int) $student->id)
            ->orderByDesc('visit_date')
            ->limit(80)
            ->get();

        $referrals = MedicalReferral::query()
            ->where('student_id', (int) $student->id)
            ->orderByDesc('referred_at')
            ->limit(40)
            ->get();

        return [
            'medicalHistory' => $medicalHistory,
            'medicalReferrals' => $referrals,
        ];
    }

    private function disciplineTabData(Student $student): array
    {
        $disciplineComplaints = DisciplineComplaint::query()
            ->where('student_id', (int) $student->id)
            ->orderByDesc('complaint_date')
            ->limit(80)
            ->get();

        $openCount = $disciplineComplaints
            ->filter(fn ($row) => ! in_array(strtolower((string) $row->status), ['closed', 'resolved'], true))
            ->count();

        return [
            'disciplineComplaints' => $disciplineComplaints,
            'openCount' => $openCount,
        ];
    }

}
