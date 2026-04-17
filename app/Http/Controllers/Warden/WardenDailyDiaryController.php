<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warden\WardenDailyDiaryFilterRequest;
use App\Models\DailyDiary;
use App\Services\DailyDiaryService;
use Illuminate\View\View;

class WardenDailyDiaryController extends Controller
{
    public function __construct(
        private readonly DailyDiaryService $dailyDiaryService
    ) {
    }

    public function index(WardenDailyDiaryFilterRequest $request): View
    {
        $validated = $request->validated();
        $result = $this->dailyDiaryService->getPrincipalDiaryEntries([
            'session' => $validated['session'] ?? null,
            'diary_date' => $validated['date'] ?? null,
            'class_id' => $validated['class_id'] ?? null,
            'subject_id' => $validated['subject_id'] ?? null,
            'teacher_id' => $validated['teacher_id'] ?? null,
            'is_published' => $validated['is_published'] ?? null,
        ]);

        return view('warden.daily-diary.index', [
            'entries' => $result['entries'],
            'sessions' => $result['sessions'],
            'classes' => $result['classes'],
            'subjects' => $result['subjects'],
            'teachers' => $result['teachers'],
            'filters' => [
                'session' => (string) ($result['filters']['session'] ?? ''),
                'date' => $result['filters']['diary_date'],
                'class_id' => $result['filters']['class_id'],
                'subject_id' => $result['filters']['subject_id'],
                'teacher_id' => $result['filters']['teacher_id'],
                'is_published' => $result['filters']['is_published'],
            ],
        ]);
    }

    public function show(DailyDiary $dailyDiary): View
    {
        $dailyDiary->load([
            'teacher.user:id,name',
            'classRoom:id,name,section',
            'subject:id,name',
            'createdBy:id,name',
            'attachments:id,daily_diary_id,file_path,file_name,created_at',
        ]);

        return view('warden.daily-diary.show', [
            'dailyDiary' => $dailyDiary,
        ]);
    }
}
