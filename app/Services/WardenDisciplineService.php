<?php

namespace App\Services;

use App\Models\DisciplineComplaint;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class WardenDisciplineService
{
    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     reports:LengthAwarePaginator,
     *     filters:array<string, mixed>,
     *     classes:array<int, array{id:int,name:string}>,
     *     students:array<int, array{id:int,name:string}>,
     *     statuses:array<int, string>
     * }
     */
    public function getReports(array $filters, User $user): array
    {
        $normalized = $this->normalizeFilters($filters);

        $reports = DisciplineComplaint::query()
            ->with([
                'student:id,student_id,name,class_id,status',
                'student.classRoom:id,name,section',
            ])
            ->whereHas('student', fn (Builder $query) => $query->forWarden($user))
            ->when($normalized['search'] !== null, function ($query) use ($normalized): void {
                $search = (string) $normalized['search'];
                $contains = '%'.$search.'%';
                $prefix = $search.'%';

                $query->where(function ($inner) use ($contains, $prefix): void {
                    $inner->where('description', 'like', $contains)
                        ->orWhere('action_taken', 'like', $contains)
                        ->orWhere('status', 'like', $contains)
                        ->orWhereHas('student', function ($studentQuery) use ($contains, $prefix): void {
                            $studentQuery->where('name', 'like', $contains)
                                ->orWhere('student_id', 'like', $prefix)
                                ->orWhere('father_name', 'like', $contains);
                        });
                });
            })
            ->when($normalized['student_id'] !== null, fn ($query) => $query->where('student_id', $normalized['student_id']))
            ->when($normalized['class_id'] !== null, function ($query) use ($normalized): void {
                $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('class_id', $normalized['class_id']));
            })
            ->when($normalized['date_from'] !== null, fn ($query) => $query->whereDate('complaint_date', '>=', $normalized['date_from']))
            ->when($normalized['date_to'] !== null, fn ($query) => $query->whereDate('complaint_date', '<=', $normalized['date_to']))
            ->when($normalized['incident_type'] !== null, function ($query) use ($normalized): void {
                $query->where('description', 'like', '%'.$normalized['incident_type'].'%');
            })
            ->when($normalized['status'] !== null, fn ($query) => $query->where('status', $normalized['status']))
            ->orderByDesc('complaint_date')
            ->orderByDesc('id')
            ->paginate((int) $normalized['per_page'])
            ->withQueryString();

        return [
            'reports' => $reports,
            'filters' => $normalized,
            'classes' => $this->classOptions($user),
            'students' => $this->studentOptions($user),
            'statuses' => $this->statusOptions(),
        ];
    }

    public function getReportDetail(DisciplineComplaint $report, User $user): DisciplineComplaint
    {
        $allowed = DisciplineComplaint::query()
            ->whereKey((int) $report->id)
            ->whereHas('student', fn (Builder $query) => $query->forWarden($user))
            ->exists();

        if (! $allowed) {
            throw new RuntimeException('You are not allowed to access this discipline report.');
        }

        return $report->load([
            'student:id,student_id,name,father_name,class_id,status',
            'student.classRoom:id,name,section',
        ]);
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function classOptions(User $user): array
    {
        $classIds = Student::query()
            ->forWarden($user)
            ->distinct()
            ->pluck('class_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        return SchoolClass::query()
            ->when($classIds !== [], fn (Builder $query) => $query->whereIn('id', $classIds))
            ->when($classIds === [], fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section'])
            ->map(fn (SchoolClass $class): array => [
                'id' => (int) $class->id,
                'name' => trim((string) $class->name.' '.(string) ($class->section ?? '')),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function statusOptions(): array
    {
        $statuses = DisciplineComplaint::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->map(static fn ($status): string => trim((string) $status))
            ->filter(static fn (string $status): bool => $status !== '')
            ->values()
            ->all();

        if ($statuses === []) {
            return ['open', 'pending', 'resolved', 'closed'];
        }

        return array_values(array_unique($statuses));
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function studentOptions(User $user): array
    {
        return Student::query()
            ->forWarden($user)
            ->whereHas('disciplineComplaints')
            ->orderBy('name')
            ->limit(400)
            ->get(['id', 'name', 'student_id'])
            ->map(fn (Student $student): array => [
                'id' => (int) $student->id,
                'name' => trim((string) $student->name.' ('.(string) $student->student_id.')'),
            ])
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     search:?string,
     *     class_id:?int,
     *     student_id:?int,
     *     date_from:?string,
     *     date_to:?string,
     *     incident_type:?string,
     *     status:?string,
     *     per_page:int
     * }
     */
    private function normalizeFilters(array $filters): array
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
        $perPage = max(10, min($perPage, 100));

        return [
            'search' => trim((string) ($filters['search'] ?? '')) ?: null,
            'class_id' => isset($filters['class_id']) && $filters['class_id'] !== ''
                ? (int) $filters['class_id']
                : null,
            'student_id' => isset($filters['student_id']) && $filters['student_id'] !== ''
                ? (int) $filters['student_id']
                : null,
            'date_from' => trim((string) ($filters['date_from'] ?? '')) ?: null,
            'date_to' => trim((string) ($filters['date_to'] ?? '')) ?: null,
            'incident_type' => trim((string) ($filters['incident_type'] ?? '')) ?: null,
            'status' => trim((string) ($filters['status'] ?? '')) ?: null,
            'per_page' => $perPage,
        ];
    }
}
