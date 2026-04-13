<?php

namespace App\Services;

use App\Models\TeacherAcr;

class TeacherPerformanceSyncService
{
    public function __construct(
        private readonly TeacherRankingService $teacherRankingService,
        private readonly TeacherAcrService $teacherAcrService,
    ) {
    }

    public function syncAfterMarksChange(int $teacherId, string $session, ?string $examType = null): void
    {
        $resolvedSession = $this->teacherRankingService->resolveSession($session);
        $resolvedExamType = $this->teacherRankingService->normalizeExamType($examType);

        $this->refreshTeacherCgpa($teacherId, $resolvedSession, $resolvedExamType);
        $this->refreshTeacherRankings($resolvedSession, $resolvedExamType);

        // ACR academics are computed from overall ranking snapshots (exam_type = null).
        if ($resolvedExamType !== null) {
            $this->refreshTeacherCgpa($teacherId, $resolvedSession, null);
            $this->refreshTeacherRankings($resolvedSession, null);
        }

        $this->refreshTeacherAcr($teacherId, $resolvedSession);
    }

    public function refreshTeacherCgpa(int $teacherId, string $session, ?string $examType = null): void
    {
        $resolvedSession = $this->teacherRankingService->resolveSession($session);
        $resolvedExamType = $this->teacherRankingService->normalizeExamType($examType);

        $this->teacherRankingService->refreshSingleTeacherRanking($teacherId, $resolvedSession, $resolvedExamType);
    }

    public function refreshTeacherRankings(string $session, ?string $examType = null): void
    {
        $resolvedSession = $this->teacherRankingService->resolveSession($session);
        $resolvedExamType = $this->teacherRankingService->normalizeExamType($examType);

        if (! $this->teacherRankingService->rankingsTableReady()) {
            return;
        }

        $this->teacherRankingService->storeTeacherCgpaRankings($resolvedSession, $resolvedExamType);
    }

    public function refreshTeacherAcr(int $teacherId, string $session): void
    {
        $this->refreshDraftAcrAcademicMetrics($teacherId, $session);
    }

    public function refreshDraftAcrAcademicMetrics(int $teacherId, string $session): void
    {
        $resolvedSession = $this->teacherRankingService->resolveSession($session);

        $acrs = TeacherAcr::query()
            ->where('teacher_id', $teacherId)
            ->where('session', $resolvedSession)
            ->get(['id', 'status']);

        if ($acrs->isEmpty()) {
            return;
        }

        $hasFinalizedAcr = false;
        foreach ($acrs as $acr) {
            if ($acr->status === TeacherAcr::STATUS_FINALIZED) {
                $hasFinalizedAcr = true;
                continue;
            }

            $this->teacherAcrService->refreshCalculatedFields((int) $acr->id);
        }

        if ($hasFinalizedAcr) {
            $this->teacherAcrService->markNeedsRefreshIfFinalized($teacherId, $resolvedSession);
        }
    }
}
