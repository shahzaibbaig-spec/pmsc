<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Services\DailyDiaryService;
use App\Services\DiaryMonitoringService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyDiaryMonitoringController extends Controller
{
    public function __construct(
        private readonly DailyDiaryService $dailyDiaryService,
        private readonly DiaryMonitoringService $diaryMonitoringService
    ) {
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);

        $resolvedSession = $this->dailyDiaryService->resolveSession($validated['session'] ?? null);
        $resolvedDate = isset($validated['date']) && trim((string) $validated['date']) !== ''
            ? Carbon::parse((string) $validated['date'])->toDateString()
            : now()->toDateString();
        $scopeFilters = $this->scopeFilters($validated);

        $options = $this->diaryMonitoringService->filterOptions($resolvedSession);
        $rows = $this->diaryMonitoringService->getMonitoringRows($resolvedSession, $resolvedDate, $scopeFilters);
        $dashboardCards = $this->diaryMonitoringService->getCompletionDashboardCards($resolvedSession, $resolvedDate, $scopeFilters);

        return view('principal.daily-diary.index', [
            'filters' => [
                'session' => $resolvedSession,
                'date' => $resolvedDate,
                'class_id' => $scopeFilters['class_id'],
                'subject_id' => $scopeFilters['subject_id'],
                'teacher_id' => $scopeFilters['teacher_id'],
            ],
            'sessions' => $options['sessions'],
            'teachers' => $options['teachers'],
            'classes' => $options['classes'],
            'subjects' => $options['subjects'],
            'rows' => $rows,
            'cards' => $dashboardCards,
            'stats' => $dashboardCards,
        ]);
    }

    public function completionReport(Request $request): View
    {
        $validated = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);

        $resolvedSession = $this->dailyDiaryService->resolveSession($validated['session'] ?? null);
        $resolvedDate = isset($validated['date']) && trim((string) $validated['date']) !== ''
            ? Carbon::parse((string) $validated['date'])->toDateString()
            : now()->toDateString();
        $scopeFilters = $this->scopeFilters($validated);

        $report = $this->diaryMonitoringService->getPostingCompletionReport($resolvedSession, $resolvedDate, $scopeFilters);
        $dashboardCards = $this->diaryMonitoringService->getCompletionDashboardCards($resolvedSession, $resolvedDate, $scopeFilters);
        $missingRows = $this->diaryMonitoringService->getMissingDiaryTeachers($resolvedSession, $resolvedDate, $scopeFilters);
        $classwiseCompletion = $this->diaryMonitoringService->getClasswiseDiaryCompletion($resolvedSession, $resolvedDate, $scopeFilters);
        $options = $this->diaryMonitoringService->filterOptions($resolvedSession);

        return view('principal.daily-diary.completion-report', [
            'filters' => [
                'session' => $resolvedSession,
                'date' => $resolvedDate,
                'class_id' => $scopeFilters['class_id'],
                'subject_id' => $scopeFilters['subject_id'],
                'teacher_id' => $scopeFilters['teacher_id'],
            ],
            'sessions' => $options['sessions'],
            'teachers' => $options['teachers'],
            'classes' => $options['classes'],
            'subjects' => $options['subjects'],
            'report' => $report,
            'cards' => $dashboardCards,
            'missingRows' => $missingRows,
            'classwiseCompletion' => $classwiseCompletion,
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{class_id:?int,subject_id:?int,teacher_id:?int}
     */
    private function scopeFilters(array $validated): array
    {
        return [
            'class_id' => isset($validated['class_id']) && $validated['class_id'] !== ''
                ? (int) $validated['class_id']
                : null,
            'subject_id' => isset($validated['subject_id']) && $validated['subject_id'] !== ''
                ? (int) $validated['subject_id']
                : null,
            'teacher_id' => isset($validated['teacher_id']) && $validated['teacher_id'] !== ''
                ? (int) $validated['teacher_id']
                : null,
        ];
    }
}
