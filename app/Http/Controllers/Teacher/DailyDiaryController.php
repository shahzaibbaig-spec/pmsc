<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDailyDiaryRequest;
use App\Http\Requests\UpdateDailyDiaryRequest;
use App\Models\DailyDiary;
use App\Models\Teacher;
use App\Services\DailyDiaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DailyDiaryController extends Controller
{
    public function __construct(
        private readonly DailyDiaryService $dailyDiaryService
    ) {
    }

    public function index(Request $request): View
    {
        $teacher = $this->teacherFromAuth();

        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'diary_date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'is_published' => ['nullable', 'in:0,1'],
        ]);

        $payload = $this->dailyDiaryService->getTeacherDiaryEntries((int) $teacher->id, $filters);

        return view('teacher.daily-diary.index', [
            'teacher' => $teacher,
            'entries' => $payload['entries'],
            'filters' => $payload['filters'],
            'sessions' => $payload['sessions'],
            'classes' => $payload['classes'],
            'subjects' => $payload['subjects'],
        ]);
    }

    public function create(Request $request): View
    {
        $teacher = $this->teacherFromAuth();
        $session = trim((string) $request->query('session', '')) ?: null;

        $options = $this->dailyDiaryService->getTeacherPostingOptions((int) $teacher->id, $session);

        return view('teacher.daily-diary.create', [
            'teacher' => $teacher,
            'options' => $options,
            'selectedSession' => $options['selected_session'],
        ]);
    }

    public function store(StoreDailyDiaryRequest $request): RedirectResponse
    {
        try {
            $this->dailyDiaryService->createOrUpdateDiary(
                $request->validated(),
                (int) $request->user()->id
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['daily_diary' => $exception->getMessage()]);
        }

        return redirect()
            ->route('teacher.daily-diary.index', [
                'session' => $request->string('session')->toString(),
            ])
            ->with('success', 'Daily diary entry saved successfully.');
    }

    public function edit(DailyDiary $dailyDiary): View
    {
        $teacher = $this->teacherFromAuth();
        $this->ensureDiaryOwnership($dailyDiary, $teacher);

        $options = $this->dailyDiaryService->getTeacherPostingOptions(
            (int) $teacher->id,
            (string) $dailyDiary->session
        );

        return view('teacher.daily-diary.edit', [
            'teacher' => $teacher,
            'dailyDiary' => $dailyDiary,
            'options' => $options,
            'selectedSession' => (string) $dailyDiary->session,
        ]);
    }

    public function update(UpdateDailyDiaryRequest $request, DailyDiary $dailyDiary): RedirectResponse
    {
        $teacher = $this->teacherFromAuth();
        $this->ensureDiaryOwnership($dailyDiary, $teacher);

        try {
            $this->dailyDiaryService->createOrUpdateDiary(
                [
                    ...$request->validated(),
                    'diary_id' => (int) $dailyDiary->id,
                ],
                (int) $request->user()->id
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['daily_diary' => $exception->getMessage()]);
        }

        return redirect()
            ->route('teacher.daily-diary.index', [
                'session' => $request->string('session')->toString(),
            ])
            ->with('success', 'Daily diary entry updated successfully.');
    }

    private function teacherFromAuth(): Teacher
    {
        $teacher = auth()->user()?->teacher;
        abort_if($teacher === null, 403, 'Teacher profile not found.');

        return $teacher;
    }

    private function ensureDiaryOwnership(DailyDiary $dailyDiary, Teacher $teacher): void
    {
        abort_if((int) $dailyDiary->teacher_id !== (int) $teacher->id, 403, 'You can only manage your own diary entries.');
    }
}

