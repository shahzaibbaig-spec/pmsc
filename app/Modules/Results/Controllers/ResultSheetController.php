<?php

namespace App\Modules\Results\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeBlockOverride;
use App\Models\SchoolClass;
use App\Modules\Fees\Services\FeeDefaulterService;
use App\Modules\Reports\Services\ReportService;
use App\Modules\Results\Services\ResultSheetService;
use App\Services\SectionHeadAssignmentService;
use App\Services\TeacherRankingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class ResultSheetController extends Controller
{
    public function classTests(Request $request, \App\Modules\Results\Services\ClassTestSheetService $service): View
    {
        $validated = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'session' => ['nullable', 'string', 'max:20'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'month' => ['nullable', 'date_format:Y-m'],
            'display' => ['nullable', 'in:marks,percentages'],
        ]);
        $classes = $this->classes();
        $sessions = $this->sessionOptions();
        $session = $validated['session'] ?? $sessions[1];
        if (! $request->user()->hasAnyRole(['Admin', 'Principal'])) {
            $scopes = app(SectionHeadAssignmentService::class)->getObserverScopes($request->user(), $session);
            $rankingService = app(TeacherRankingService::class);
            $classes = $classes->filter(fn (SchoolClass $classRoom) => in_array(
                $rankingService->resolveRankingGroupFromClass($classRoom), $scopes, true
            ))->values();
            if (isset($validated['class_id'])) {
                abort_unless($classes->contains('id', (int) $validated['class_id']), 403, 'This class is outside your section assignment for the selected session.');
            }
        }
        $filters = [
            'class_id' => $validated['class_id'] ?? $classes->first()?->id,
            'session' => $session,
            'student_id' => $validated['student_id'] ?? null,
            'month' => $validated['month'] ?? null,
            'display' => $validated['display'] ?? 'marks',
        ];
        if (! in_array($filters['session'], $sessions, true)) {
            $sessions[] = $filters['session'];
        }
        $students = $filters['class_id'] ? $service->students((int) $filters['class_id'], $filters['session']) : collect();
        $report = $filters['class_id'] ? $service->sheet((int) $filters['class_id'], $filters['session'],
            $filters['student_id'] ? (int) $filters['student_id'] : null, $filters['month']) : null;

        $sheetRoute = $request->routeIs('section-head.results.class-tests')
            ? 'section-head.results.class-tests' : 'principal.results.class-tests';

        return view('modules.principal.results.class-tests', compact('classes', 'sessions', 'filters', 'students', 'report', 'sheetRoute'));
    }

    public function __construct(
        private readonly ResultSheetService $resultSheetService,
        private readonly FeeDefaulterService $feeDefaulterService,
        private readonly ReportService $reportService,
    ) {
    }

    public function gazette(Request $request): View
    {
        [$filters, $report, $error] = $this->buildReportPayload($request);

        return view('modules.principal.results.gazette', [
            'classes' => $this->classes(),
            'sessions' => $this->sessionOptions(),
            'filters' => $filters,
            'report' => $report,
            'errorMessage' => $error,
        ]);
    }

    public function tabulation(Request $request): View
    {
        [$filters, $report, $error] = $this->buildReportPayload($request);

        return view('modules.principal.results.tabulation', [
            'classes' => $this->classes(),
            'sessions' => $this->sessionOptions(),
            'filters' => $filters,
            'report' => $report,
            'errorMessage' => $error,
        ]);
    }

    public function gazettePdf(Request $request): Response
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'session' => ['required', 'string', 'max:20'],
        ]);

        try {
            $report = $this->generateReport((int) $validated['class_id'], (string) $validated['session']);
        } catch (RuntimeException $exception) {
            return response($exception->getMessage(), 422);
        }

        $pdf = Pdf::loadView('modules.reports.result-gazette', [
            'school' => $this->reportService->schoolMeta(),
            'report' => $report,
        ])->setPaper('a4', 'landscape');

        $filename = sprintf(
            'result_gazette_class_%d_%s.pdf',
            (int) $validated['class_id'],
            str_replace('/', '-', (string) $validated['session'])
        );

        return $pdf->stream($filename);
    }

    public function tabulationPdf(Request $request): Response
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'session' => ['required', 'string', 'max:20'],
        ]);

        try {
            $report = $this->generateReport((int) $validated['class_id'], (string) $validated['session']);
        } catch (RuntimeException $exception) {
            return response($exception->getMessage(), 422);
        }

        $pdf = Pdf::loadView('modules.reports.tabulation-sheet', [
            'school' => $this->reportService->schoolMeta(),
            'report' => $report,
        ])->setPaper('a4', 'landscape');

        $filename = sprintf(
            'tabulation_sheet_class_%d_%s.pdf',
            (int) $validated['class_id'],
            str_replace('/', '-', (string) $validated['session'])
        );

        return $pdf->stream($filename);
    }

    /**
     * @return array{0:array{class_id:string|int,session:string},1:?array,2:?string}
     */
    private function buildReportPayload(Request $request): array
    {
        $classes = $this->classes();
        $sessions = $this->sessionOptions();

        $defaultClassId = (int) ($classes->first()->id ?? 0);
        $defaultSession = $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));

        $classId = $request->filled('class_id')
            ? (int) $request->query('class_id')
            : $defaultClassId;
        $session = $request->filled('session')
            ? trim((string) $request->query('session'))
            : $defaultSession;

        $filters = [
            'class_id' => $classId > 0 ? $classId : '',
            'session' => $session,
        ];

        if ($classId <= 0 || trim($session) === '') {
            return [$filters, null, null];
        }

        try {
            $report = $this->generateReport($classId, $session);
        } catch (RuntimeException $exception) {
            return [$filters, null, $exception->getMessage()];
        }

        return [$filters, $report, null];
    }

    /**
     * @return array{
     *   class:array{id:int,name:string},
     *   exam:array{session:string,exam_type:string,exam_type_label:string,generated_at:string},
     *   subjects:array<int,array{id:int,name:string,total_marks:int}>,
     *   rows:array<int,array{
     *     student_id:int,student_code:string,student_name:string,position:int,
     *     subject_marks:array<int,array{obtained:int,total:int}>,
     *     total_marks:int,obtained_marks:int,percentage:float,grade:string
     *   }>,
     *   summary:array{
     *     students_count:int,
     *     subjects_count:int,
     *     total_marks_per_student:int,
     *     class_average_percentage:float
     *   }
     * }
     */
    private function generateReport(int $classId, string $session): array
    {
        $resolvedSession = trim($session);
        $this->feeDefaulterService->processSession($resolvedSession);

        $blocked = $this->feeDefaulterService->blockedStudentsForClass(
            $classId,
            $resolvedSession,
            FeeBlockOverride::TYPE_RESULT_CARD
        );

        if ($blocked->isNotEmpty()) {
            $sample = $blocked
                ->take(3)
                ->map(fn (array $student): string => $student['name'].' ('.$student['student_id'].')')
                ->implode(', ');

            throw new RuntimeException(sprintf(
                'Result reports are blocked for %d defaulter(s) in this class. %s',
                $blocked->count(),
                $sample !== '' ? 'Examples: '.$sample.'.' : ''
            ));
        }

        return $this->resultSheetService->classSheet($classId, $resolvedSession);
    }

    private function classes()
    {
        return SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);
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
