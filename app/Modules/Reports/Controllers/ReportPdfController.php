<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeBlockOverride;
use App\Models\SchoolClass;
use App\Modules\Fees\Services\FeeDefaulterService;
use App\Modules\Exams\Enums\ExamType;
use App\Modules\Medical\Requests\MedicalReportRequest;
use App\Modules\Medical\Services\MedicalService;
use App\Modules\Reports\Requests\AttendanceReportPdfRequest;
use App\Modules\Reports\Requests\ClassResultPdfRequest;
use App\Modules\Reports\Services\ReportService;
use App\Modules\Results\Requests\StudentResultPreviewRequest;
use App\Modules\Results\Services\ResultService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class ReportPdfController extends Controller
{
    public function __construct(
        private readonly ResultService $resultService,
        private readonly ReportService $reportService,
        private readonly MedicalService $medicalService,
        private readonly FeeDefaulterService $feeDefaulterService,
    ) {
    }

    public function index(): View
    {
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $sessions = $this->sessionOptions();

        return view('modules.reports.index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'defaultSession' => $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1)),
            'examTypes' => ExamType::options(),
        ]);
    }

    public function studentResultCardPdf(StudentResultPreviewRequest $request): Response
    {
        $studentId = (int) $request->input('student_id');
        $session = $request->string('session')->toString();

        try {
            $this->ensureResultCardNotBlocked($studentId, $session);

            $result = $this->resultService->generateStudentResult(
                $studentId,
                $session,
                $request->string('exam_type')->toString()
            );
        } catch (RuntimeException $exception) {
            return response($exception->getMessage(), 422);
        }

        $schoolMeta = $this->reportService->schoolMeta();
        $result['school']['logo_absolute_path'] = $schoolMeta['logo_absolute_path'] ?? null;

        $pdf = Pdf::loadView('modules.reports.result-card', [
            'result' => $result,
        ])->setPaper('a4', 'portrait');

        $filename = 'student_result_'.$result['student']['id'].'_'.$result['exam']['session'].'_'.$result['exam']['exam_type'].'.pdf';

        return $pdf->stream($filename);
    }

    public function classResultPdf(ClassResultPdfRequest $request): Response
    {
        try {
            $payload = $this->reportService->classResultData(
                (int) $request->input('class_id'),
                $request->string('session')->toString(),
                $request->string('exam_type')->toString()
            );
        } catch (RuntimeException $exception) {
            return response($exception->getMessage(), 422);
        }

        $pdf = Pdf::loadView('modules.reports.class-result', [
            'report' => $payload,
        ])->setPaper('a4', 'landscape');

        $filename = 'class_result_'.$request->input('class_id').'_'.$request->input('session').'_'.$request->input('exam_type').'.pdf';

        return $pdf->stream($filename);
    }

    public function classResultCardsPdf(ClassResultPdfRequest $request): Response
    {
        $classId = (int) $request->input('class_id');
        $session = $request->string('session')->toString();

        try {
            $this->feeDefaulterService->processSession($session);

            $blocked = $this->feeDefaulterService->blockedStudentsForClass(
                $classId,
                $session,
                FeeBlockOverride::TYPE_RESULT_CARD
            );

            if ($blocked->isNotEmpty()) {
                $sample = $blocked
                    ->take(3)
                    ->map(fn (array $student): string => $student['name'].' ('.$student['student_id'].')')
                    ->implode(', ');

                throw new RuntimeException(sprintf(
                    'Result cards are blocked for %d defaulter(s) in this class. %s',
                    $blocked->count(),
                    $sample !== '' ? 'Examples: '.$sample.'.' : ''
                ));
            }

            $payload = $this->resultService->generateClassResultCards(
                $classId,
                $session,
                $request->string('exam_type')->toString()
            );
        } catch (RuntimeException $exception) {
            return response($exception->getMessage(), 422);
        }

        $schoolMeta = $this->reportService->schoolMeta();
        $payload['school']['logo_absolute_path'] = $schoolMeta['logo_absolute_path'] ?? null;

        $pdf = Pdf::loadView('modules.reports.class-result-cards', [
            'report' => $payload,
        ])->setPaper('a4', 'portrait');

        $filename = 'class_result_cards_'.$request->input('class_id').'_'.$request->input('session').'_'.$request->input('exam_type').'.pdf';

        return $pdf->stream($filename);
    }

    private function ensureResultCardNotBlocked(int $studentId, string $session): void
    {
        if (! $this->feeDefaulterService->isStudentBlocked(FeeBlockOverride::TYPE_RESULT_CARD, $studentId, $session)) {
            return;
        }

        $breakdown = $this->feeDefaulterService->dueBreakdownForStudent($studentId, $session);
        $totalDue = round((float) ($breakdown['total_due'] ?? 0), 2);

        throw new RuntimeException(sprintf(
            'Official result card is blocked for this student due to unpaid dues (PKR %s).',
            number_format($totalDue, 2)
        ));
    }

    public function medicalReportPdf(MedicalReportRequest $request): Response
    {
        $payload = $this->medicalService->reportData($request->user(), $request->validated());
        $filters = $request->validated();

        $pdf = Pdf::loadView('modules.reports.medical-report', [
            'report' => $payload,
            'filters' => $filters,
            'school' => $this->reportService->schoolMeta(),
        ])->setPaper('a4', 'landscape');

        $filename = 'medical_report_'.$filters['report_type'].'_'.$filters['year'].'.pdf';

        return $pdf->stream($filename);
    }

    public function attendanceReportPdf(AttendanceReportPdfRequest $request): Response
    {
        try {
            $payload = $this->reportService->attendanceReportData(
                $request->string('date')->toString(),
                $request->filled('class_id') ? (int) $request->input('class_id') : null
            );
        } catch (RuntimeException $exception) {
            return response($exception->getMessage(), 422);
        }

        $pdf = Pdf::loadView('modules.reports.attendance-report', [
            'report' => $payload,
        ])->setPaper('a4', 'portrait');

        $filename = 'attendance_report_'.$request->input('date').'.pdf';

        return $pdf->stream($filename);
    }

    private function sessionOptions(int $backward = 1, int $forward = 3): array
    {
        $now = now();
        $currentStartYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        $sessions = [];
        for ($year = $currentStartYear - $backward; $year <= $currentStartYear + $forward; $year++) {
            $sessions[] = $year.'-'.($year + 1);
        }

        return $sessions;
    }
}
