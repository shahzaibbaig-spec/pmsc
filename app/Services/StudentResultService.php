<?php

namespace App\Services;

use App\Models\Mark;
use App\Models\StudentClassHistory;
use App\Models\StudentResult;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class StudentResultService
{
    public function __construct(
        private readonly DailyDiaryService $dailyDiaryService
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function sessionOptions(): array
    {
        return $this->dailyDiaryService->sessionOptions();
    }

    /**
     * @return array<int, string>
     */
    public function availableSessionsForStudent(int $studentId): array
    {
        return collect(array_merge(
            StudentResult::query()
                ->where('student_id', $studentId)
                ->whereNotNull('session')
                ->pluck('session')
                ->filter(fn ($session): bool => is_string($session) && trim($session) !== '')
                ->values()
                ->all(),
            Mark::query()
                ->where('student_id', $studentId)
                ->whereNotNull('session')
                ->pluck('session')
                ->filter(fn ($session): bool => is_string($session) && trim($session) !== '')
                ->values()
                ->all(),
            StudentClassHistory::query()
                ->where('student_id', $studentId)
                ->whereNotNull('session')
                ->pluck('session')
                ->filter(fn ($session): bool => is_string($session) && trim($session) !== '')
                ->values()
                ->all(),
            $this->sessionOptions()
        ))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    public function resolveRequestedSession(?string $requestedSession, array $sessions): string
    {
        $session = trim((string) $requestedSession);
        if ($session !== '' && in_array($session, $sessions, true)) {
            return $session;
        }

        return $sessions[0] ?? $this->dailyDiaryService->resolveSession(null);
    }

    public function getStudentResults(int $studentId, string $session): EloquentCollection
    {
        return StudentResult::query()
            ->with(['classRoom:id,name,section', 'subject:id,name', 'exam:id,class_id,subject_id,exam_type,session'])
            ->where('student_id', $studentId)
            ->where('session', $session)
            ->orderByDesc('result_date')
            ->orderByDesc('id')
            ->get();
    }

    public function getRecentStudentResults(int $studentId, string $session, int $limit = 30): EloquentCollection
    {
        return StudentResult::query()
            ->with(['classRoom:id,name,section', 'subject:id,name', 'exam:id,class_id,subject_id,exam_type,session'])
            ->where('student_id', $studentId)
            ->where('session', $session)
            ->orderByDesc('result_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{results_count:int,average_percentage:float,grade:string}
     */
    public function getStudentResultStats(int $studentId, string $session): array
    {
        $aggregate = StudentResult::query()
            ->where('student_id', $studentId)
            ->where('session', $session)
            ->selectRaw('COUNT(*) as results_count, SUM(obtained_marks) as obtained_sum, SUM(total_marks) as total_sum')
            ->first();

        $resultsCount = (int) ($aggregate?->results_count ?? 0);
        $obtainedSum = (float) ($aggregate?->obtained_sum ?? 0);
        $totalSum = (float) ($aggregate?->total_sum ?? 0);
        $averagePercentage = $totalSum > 0 ? round(($obtainedSum / $totalSum) * 100, 2) : 0.0;

        return [
            'results_count' => $resultsCount,
            'average_percentage' => $averagePercentage,
            'grade' => $resultsCount > 0 ? $this->gradeFromPercentage($averagePercentage) : 'N/A',
        ];
    }

    public function sessionClassNameForStudent(int $studentId, string $session): ?string
    {
        $classHistory = StudentClassHistory::query()
            ->where('student_id', $studentId)
            ->where('session', $session)
            ->with('classRoom:id,name,section')
            ->latest('joined_on')
            ->first();

        if ($classHistory?->classRoom === null) {
            $resultClass = StudentResult::query()
                ->where('student_id', $studentId)
                ->where('session', $session)
                ->with('classRoom:id,name,section')
                ->latest('result_date')
                ->latest('id')
                ->first();

            return $this->classLabel($resultClass?->classRoom?->name, $resultClass?->classRoom?->section);
        }

        return $this->classLabel($classHistory->classRoom->name, $classHistory->classRoom->section);
    }

    private function classLabel(?string $name, ?string $section): ?string
    {
        $label = trim((string) $name.' '.(string) ($section ?? ''));

        return $label !== '' ? $label : null;
    }

    private function gradeFromPercentage(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'A+',
            $percentage >= 80 => 'A',
            $percentage >= 70 => 'B',
            $percentage >= 60 => 'C',
            $percentage >= 50 => 'D',
            default => 'F',
        };
    }
};
