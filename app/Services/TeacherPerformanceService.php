<?php

namespace App\Services;

use App\Models\TeacherPerformanceEvent;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TeacherPerformanceService
{
    public function __construct(
        private readonly TeacherRankingService $teacherRankingService,
        private readonly TeacherPerformanceSyncService $teacherPerformanceSyncService
    ) {
    }

    public function recordObservationPerformance(
        int $teacherId,
        string $sourceType,
        int $sourceId,
        string $session,
        float $score,
        ?float $maxScore,
        float $percentage,
        ?string $judgment = null,
        ?string $remarks = null,
        ?User $recordedBy = null
    ): TeacherPerformanceEvent {
        $event = TeacherPerformanceEvent::query()->create([
            'teacher_id' => $teacherId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'session' => trim($session),
            'score' => round($score, 2),
            'max_score' => $maxScore !== null ? round($maxScore, 2) : null,
            'percentage' => round($percentage, 2),
            'judgment' => $judgment !== null ? trim($judgment) : null,
            'remarks' => $remarks !== null ? trim($remarks) : null,
            'recorded_by' => $recordedBy?->id,
            'recorded_at' => now(),
        ]);

        // Keep existing ranking/ACR ecosystem in sync where possible.
        try {
            $this->teacherPerformanceSyncService->refreshTeacherAcr(
                $this->teacherProfileIdForUser((int) $teacherId),
                $this->teacherRankingService->resolveSession($session)
            );
        } catch (\Throwable) {
            // Observations should not fail if ranking/ACR refresh is unavailable.
        }

        return $event;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *   events:LengthAwarePaginator,
     *   summary:array<string, mixed>,
     *   filters:array<string, mixed>,
     *   sessions:array<int, string>
     * }
     */
    public function getTeacherPerformanceSummary(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);

        $query = TeacherPerformanceEvent::query()
            ->with([
                'teacher:id,name,email',
                'recordedBy:id,name',
            ]);

        $this->applyFilters($query, $normalized);

        $events = $query
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate((int) $normalized['per_page'])
            ->withQueryString();

        $summaryQuery = TeacherPerformanceEvent::query();
        $this->applyFilters($summaryQuery, $normalized);

        $summary = [
            'total_events' => (int) (clone $summaryQuery)->count(),
            'avg_percentage' => round((float) ((clone $summaryQuery)->avg('percentage') ?? 0), 2),
            'lesson_events' => (int) (clone $summaryQuery)
                ->where('source_type', TeacherPerformanceEvent::SOURCE_LESSON_OBSERVATION)
                ->count(),
            'notebook_events' => (int) (clone $summaryQuery)
                ->where('source_type', TeacherPerformanceEvent::SOURCE_NOTEBOOK_OBSERVATION)
                ->count(),
        ];

        return [
            'events' => $events,
            'summary' => $summary,
            'filters' => $normalized,
            'sessions' => $this->sessionOptions(),
        ];
    }

    private function teacherProfileIdForUser(int $userId): int
    {
        return (int) \App\Models\Teacher::query()
            ->where('user_id', $userId)
            ->value('id');
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 20;
        $perPage = max(10, min($perPage, 100));

        return [
            'teacher_id' => isset($filters['teacher_id']) && $filters['teacher_id'] !== '' ? (int) $filters['teacher_id'] : null,
            'source_type' => trim((string) ($filters['source_type'] ?? '')) ?: null,
            'session' => trim((string) ($filters['session'] ?? '')) ?: null,
            'per_page' => $perPage,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['teacher_id'] !== null) {
            $query->where('teacher_id', (int) $filters['teacher_id']);
        }
        if ($filters['source_type'] !== null) {
            $query->where('source_type', (string) $filters['source_type']);
        }
        if ($filters['session'] !== null) {
            $query->where('session', (string) $filters['session']);
        }
    }

    /**
     * @return array<int, string>
     */
    private function sessionOptions(): array
    {
        return TeacherPerformanceEvent::query()
            ->orderByDesc('session')
            ->pluck('session')
            ->filter(static fn ($session): bool => is_string($session) && trim($session) !== '')
            ->unique()
            ->values()
            ->all();
    }
}
