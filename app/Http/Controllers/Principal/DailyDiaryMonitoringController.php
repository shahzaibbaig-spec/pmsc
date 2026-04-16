<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Services\DailyDiaryService;
use App\Services\DiaryMonitoringService;
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
        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);

        $resolvedSession = $this->dailyDiaryService->resolveSession($filters['session'] ?? null);
        $resolvedDate = trim((string) ($filters['date'] ?? '')) ?: now()->toDateString();

        $options = $this->diaryMonitoringService->filterOptions($resolvedSession);
        $rows = $this->diaryMonitoringService->getMonitoringRows($resolvedSession, $resolvedDate, $filters);

        $totalExpected = count($rows);
        $totalPosted = collect($rows)->where('posted', true)->count();
        $missing = max($totalExpected - $totalPosted, 0);
        $completionPercentage = $totalExpected > 0
            ? round(($totalPosted / $totalExpected) * 100, 2)
            : 100.0;

        return view('principal.daily-diary.index', [
            'filters' => [
                'session' => $resolvedSession,
                'date' => $resolvedDate,
                'class_id' => isset($filters['class_id']) && $filters['class_id'] !== '' ? (int) $filters['class_id'] : null,
                'subject_id' => isset($filters['subject_id']) && $filters['subject_id'] !== '' ? (int) $filters['subject_id'] : null,
                'teacher_id' => isset($filters['teacher_id']) && $filters['teacher_id'] !== '' ? (int) $filters['teacher_id'] : null,
            ],
            'sessions' => $options['sessions'],
            'teachers' => $options['teachers'],
            'classes' => $options['classes'],
            'subjects' => $options['subjects'],
            'rows' => $rows,
            'stats' => [
                'total_expected_postings' => $totalExpected,
                'total_posted' => $totalPosted,
                'missing_postings' => $missing,
                'completion_percentage' => $completionPercentage,
            ],
        ]);
    }

    public function completionReport(Request $request): View
    {
        $validated = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date'],
        ]);

        $resolvedSession = $this->dailyDiaryService->resolveSession($validated['session'] ?? null);
        $resolvedDate = trim((string) ($validated['date'] ?? '')) ?: now()->toDateString();

        $report = $this->diaryMonitoringService->getPostingCompletionReport($resolvedSession, $resolvedDate);
        $options = $this->diaryMonitoringService->filterOptions($resolvedSession);

        return view('principal.daily-diary.completion-report', [
            'filters' => [
                'session' => $resolvedSession,
                'date' => $resolvedDate,
            ],
            'sessions' => $options['sessions'],
            'report' => $report,
            'missingRows' => $report['missing_rows'],
        ]);
    }
}

