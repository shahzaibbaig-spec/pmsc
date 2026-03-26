<?php

namespace App\Http\Controllers\Principal;

use App\Exports\PrincipalAnalyticsExport;
use App\Http\Controllers\Controller;
use App\Services\AnalyticsReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnalyticsExportController extends Controller
{
    public function __construct(private readonly AnalyticsReportService $reportService)
    {
    }

    public function exportPdf(Request $request): BinaryFileResponse
    {
        [$session, $exam, $classId] = $this->validatedFilters($request);
        $report = $this->reportService->build($session, $exam, $classId);

        $pdf = Pdf::loadView('principal.analytics.export-pdf', [
            'report' => $report,
        ])->setPaper('a4', 'landscape');

        $fileName = 'principal-analytics-'.$report['filters']['session'].'-'.now()->format('Ymd_His').'.pdf';

        return $pdf->download($fileName);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        [$session, $exam, $classId] = $this->validatedFilters($request);
        $report = $this->reportService->build($session, $exam, $classId);

        $fileName = 'principal-analytics-'.$report['filters']['session'].'-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new PrincipalAnalyticsExport($report), $fileName);
    }

    public function boardSummaryPdf(Request $request)
    {
        [$session, $exam, $classId] = $this->validatedFilters($request);
        $filters = [
            'session' => $session,
            'exam' => $exam,
            'class_id' => $classId,
        ];

        $report = $this->reportService->buildBoardSummary($filters);
        $generatedAt = now();

        $pdf = Pdf::loadView('principal.analytics.board-summary-pdf', [
            'report' => $report,
            'filters' => $report['filters'],
            'generatedAt' => $generatedAt,
        ])->setPaper('a4', 'portrait');

        $fileName = 'board-summary-report-'.$generatedAt->format('Y-m-d_H-i-s').'.pdf';

        return $pdf->download($fileName);
    }

    /**
     * @return array{0:string,1:string|null,2:int|null}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'exam' => ['nullable', 'in:class_test,bimonthly_test,first_term,final_term'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
        ]);

        $session = $this->reportService->resolveSession(isset($validated['session']) ? (string) $validated['session'] : null);
        $exam = $this->reportService->resolveExam(isset($validated['exam']) ? (string) $validated['exam'] : null);
        $classId = isset($validated['class_id']) ? (int) $validated['class_id'] : null;

        return [$session, $exam, $classId];
    }
}
