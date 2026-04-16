<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\CognitiveAssessmentService;
use App\Services\DailyDiaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class StudentDailyDiaryController extends Controller
{
    public function __construct(
        private readonly DailyDiaryService $dailyDiaryService,
        private readonly CognitiveAssessmentService $cognitiveAssessmentService
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'diary_date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $student = $request->user()
            ? $this->cognitiveAssessmentService->resolveStudentForUser($request->user())
            : null;

        if (! $student) {
            return view('student.daily-diary.index', [
                'student' => null,
                'filters' => [
                    'session' => $this->dailyDiaryService->resolveSession($filters['session'] ?? null),
                    'subject_id' => null,
                    'diary_date' => null,
                    'date_from' => null,
                    'date_to' => null,
                ],
                'sessions' => $this->dailyDiaryService->sessionOptions(),
                'todayEntries' => collect(),
                'weekEntries' => collect(),
                'historyEntries' => collect(),
                'message' => 'Student profile is not linked to this login yet. Please ask Admin to align student name or email with a student record.',
            ]);
        }

        $payload = $this->dailyDiaryService->getStudentVisibleDiaryEntries((int) $student->id, $filters);
        $entries = $payload['entries'];

        $today = now()->toDateString();
        $weekStart = Carbon::now()->startOfWeek()->toDateString();
        $weekEnd = Carbon::now()->endOfWeek()->toDateString();

        $todayEntries = $entries
            ->filter(fn ($entry): bool => optional($entry->diary_date)->toDateString() === $today)
            ->values();

        $weekEntries = $entries
            ->filter(function ($entry) use ($today, $weekStart, $weekEnd): bool {
                $entryDate = optional($entry->diary_date)->toDateString();
                if ($entryDate === null || $entryDate === '') {
                    return false;
                }

                return $entryDate !== $today
                    && $entryDate >= $weekStart
                    && $entryDate <= $weekEnd;
            })
            ->values();

        $historyEntries = $entries
            ->filter(function ($entry) use ($today, $weekStart, $weekEnd): bool {
                $entryDate = optional($entry->diary_date)->toDateString();
                if ($entryDate === null || $entryDate === '') {
                    return false;
                }

                if ($entryDate === $today) {
                    return false;
                }

                return $entryDate < $weekStart || $entryDate > $weekEnd;
            })
            ->values();

        return view('student.daily-diary.index', [
            'student' => $payload['student'],
            'filters' => $payload['filters'],
            'sessions' => $payload['sessions'],
            'todayEntries' => $todayEntries,
            'weekEntries' => $weekEntries,
            'historyEntries' => $historyEntries,
            'message' => null,
        ]);
    }
}

