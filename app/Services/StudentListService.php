<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentClassHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StudentListService
{
    public function __construct(private readonly DailyDiaryService $dailyDiaryService)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     students:LengthAwarePaginator,
     *     rows:array<int, array<string, mixed>>,
     *     filters:array<string, mixed>,
     *     classes:array<int, array{id:int,name:string,section:string}>,
     *     sections:array<int, string>,
     *     sessions:array<int, string>
     * }
     */
    public function getClassWiseStudents(array $filters): array
    {
        $normalized = $this->normalizeFilters($filters);

        $students = $this->buildFilteredQuery($normalized)
            ->orderBy('name')
            ->orderBy('student_id')
            ->paginate((int) $normalized['per_page'])
            ->withQueryString();

        $rows = $this->mapRows(
            collect($students->items()),
            (string) $normalized['session'],
            ((int) $students->currentPage() - 1) * (int) $students->perPage()
        );

        return [
            'students' => $students,
            'rows' => $rows,
            'filters' => $normalized,
            'classes' => $this->classOptions(),
            'sections' => $this->sectionOptions(isset($normalized['class_id']) ? (int) $normalized['class_id'] : null),
            'sessions' => $this->sessionOptions(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     students:Collection<int, Student>,
     *     rows:array<int, array<string, mixed>>,
     *     filters:array<string, mixed>,
     *     school:array<string, string|null>,
     *     generated_at:\Illuminate\Support\Carbon,
     *     total:int
     * }
     */
    public function getPrintData(array $filters): array
    {
        $normalized = $this->normalizeFilters($filters);

        $students = $this->buildFilteredQuery($normalized)
            ->orderBy('name')
            ->orderBy('student_id')
            ->get();

        return [
            'students' => $students,
            'rows' => $this->mapRows($students, (string) $normalized['session']),
            'filters' => $normalized,
            'school' => $this->schoolMeta(),
            'generated_at' => now(),
            'total' => $students->count(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function buildFilteredQuery(array $filters): Builder
    {
        $query = Student::query()
            ->with('classRoom:id,name,section');

        $status = (string) $filters['status'];
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $session = (string) $filters['session'];
        $classId = isset($filters['class_id']) ? (int) $filters['class_id'] : null;
        $section = isset($filters['section']) ? trim((string) $filters['section']) : null;
        $sessionHistoryExists = StudentClassHistory::query()
            ->where('session', $session)
            ->exists();

        if ($sessionHistoryExists) {
            $query->whereHas('classHistories', function (Builder $historyQuery) use ($session, $classId, $section): void {
                $historyQuery
                    ->where('session', $session)
                    ->when($classId !== null, fn (Builder $subQuery) => $subQuery->where('class_id', $classId))
                    ->when($section !== null && $section !== '', function (Builder $subQuery) use ($section): void {
                        $subQuery->whereHas('classRoom', fn (Builder $classQuery) => $classQuery->where('section', $section));
                    });
            });

            return $query;
        }

        if ($classId !== null) {
            $query->where('class_id', $classId);
        }

        if ($section !== null && $section !== '') {
            $query->whereHas('classRoom', fn (Builder $classQuery) => $classQuery->where('section', $section));
        }

        return $query;
    }

    /**
     * @param Collection<int, Student> $students
     * @return array<int, array<string, mixed>>
     */
    private function mapRows(Collection $students, string $session, int $offset = 0): array
    {
        $sessionClasses = $this->sessionClassMap($students->pluck('id')->map(fn ($id): int => (int) $id)->all(), $session);

        return $students
            ->values()
            ->map(function (Student $student, int $index) use ($offset, $sessionClasses): array {
                $classInfo = $sessionClasses[(int) $student->id] ?? [
                    'name' => trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? '')),
                    'section' => (string) ($student->classRoom?->section ?? ''),
                ];

                return [
                    'sr_no' => $offset + $index + 1,
                    'student_id' => (string) $student->student_id,
                    'name' => (string) $student->name,
                    'father_name' => (string) ($student->father_name ?? '-'),
                    'class_section' => trim((string) ($classInfo['name'] ?? '-')),
                    'contact' => (string) ($student->contact ?? '-'),
                    'age' => $student->age,
                    'date_of_birth' => optional($student->date_of_birth)->format('Y-m-d'),
                    'status' => (string) ($student->status ?? 'active'),
                ];
            })
            ->all();
    }

    /**
     * @param array<int, int> $studentIds
     * @return array<int, array{name:string,section:string}>
     */
    private function sessionClassMap(array $studentIds, string $session): array
    {
        if ($studentIds === []) {
            return [];
        }

        $histories = StudentClassHistory::query()
            ->with('classRoom:id,name,section')
            ->whereIn('student_id', $studentIds)
            ->where('session', $session)
            ->orderByDesc('joined_on')
            ->orderByDesc('id')
            ->get();

        $map = [];
        foreach ($histories as $history) {
            $studentId = (int) $history->student_id;
            if (isset($map[$studentId])) {
                continue;
            }

            $map[$studentId] = [
                'name' => trim((string) ($history->classRoom?->name ?? '').' '.(string) ($history->classRoom?->section ?? '')),
                'section' => (string) ($history->classRoom?->section ?? ''),
            ];
        }

        return $map;
    }

    /**
     * @return array<int, array{id:int,name:string,section:string}>
     */
    private function classOptions(): array
    {
        return SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section'])
            ->map(fn (SchoolClass $class): array => [
                'id' => (int) $class->id,
                'name' => trim((string) $class->name.' '.(string) ($class->section ?? '')),
                'section' => (string) ($class->section ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function sectionOptions(?int $classId = null): array
    {
        return SchoolClass::query()
            ->when($classId !== null, fn (Builder $query) => $query->where('id', $classId))
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section): string => trim((string) $section))
            ->filter(fn (string $section): bool => $section !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function sessionOptions(): array
    {
        return collect(array_merge(
            StudentClassHistory::query()
                ->pluck('session')
                ->filter(fn ($session): bool => is_string($session) && trim($session) !== '')
                ->values()
                ->all(),
            $this->dailyDiaryService->sessionOptions()
        ))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{session:string,class_id:?int,section:?string,status:string,per_page:int}
     */
    private function normalizeFilters(array $filters): array
    {
        $session = trim((string) ($filters['session'] ?? ''));
        if ($session === '') {
            $session = $this->dailyDiaryService->resolveSession(null);
        }

        $status = trim((string) ($filters['status'] ?? 'active'));
        if (! in_array($status, ['active', 'inactive', 'all'], true)) {
            $status = 'active';
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 25;
        $perPage = max(10, min($perPage, 200));

        return [
            'session' => $session,
            'class_id' => isset($filters['class_id']) && $filters['class_id'] !== '' ? (int) $filters['class_id'] : null,
            'section' => trim((string) ($filters['section'] ?? '')) ?: null,
            'status' => $status,
            'per_page' => $perPage,
        ];
    }

    /**
     * @return array{name:string,logo_url:?string,logo_absolute_path:?string}
     */
    private function schoolMeta(): array
    {
        $setting = SchoolSetting::cached();
        $logoUrl = null;
        $logoAbsolutePath = null;

        $logoPath = trim((string) ($setting?->logo_path ?? ''));
        if ($logoPath !== '') {
            $logoUrl = Storage::disk('public')->url($logoPath);
            $resolvedPath = public_path('storage/'.$logoPath);
            if (is_file($resolvedPath)) {
                $logoAbsolutePath = $resolvedPath;
            }
        }

        return [
            'name' => (string) ($setting?->school_name ?? config('app.name', 'School Management System')),
            'logo_url' => $logoUrl,
            'logo_absolute_path' => $logoAbsolutePath,
        ];
    }
}
