<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Modules\Analytics\Services\AnalyticsService;

class AnalyticsReportService
{
    public function __construct(private readonly AnalyticsService $analyticsService)
    {
    }

    public function resolveSession(?string $session): string
    {
        $sessions = $this->analyticsService->sessionOptions();
        $fallback = $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));

        $candidate = trim((string) $session);
        if ($candidate === '') {
            return $fallback;
        }

        if (! preg_match('/^(\d{4})-(\d{4})$/', $candidate, $matches)) {
            return $fallback;
        }

        if ((int) $matches[2] !== ((int) $matches[1] + 1)) {
            return $fallback;
        }

        return $candidate;
    }

    public function resolveExam(?string $exam): ?string
    {
        $candidate = trim((string) $exam);
        if ($candidate === '') {
            return null;
        }

        $allowed = ['class_test', 'bimonthly_test', 'first_term', 'final_term'];

        return in_array($candidate, $allowed, true) ? $candidate : null;
    }

    public function build(string $session, ?string $exam = null, ?int $classId = null): array
    {
        $resolvedSession = $this->resolveSession($session);
        $resolvedExam = $this->resolveExam($exam);

        $className = null;
        if ($classId !== null) {
            $classRoom = SchoolClass::query()->find($classId, ['id', 'name', 'section']);
            if ($classRoom) {
                $className = trim((string) $classRoom->name.' '.(string) ($classRoom->section ?? ''));
            }
        }

        $summary = $this->analyticsService->getDashboardSummary($resolvedSession, $resolvedExam, $classId);
        $topPerformers = $this->analyticsService->getTopPerformers($resolvedSession, $resolvedExam, $classId);
        $weakStudents = $this->analyticsService->getWeakStudents($resolvedSession, $resolvedExam, $classId);
        $subjectPerformance = $this->analyticsService->getSubjectPerformance($resolvedSession, $resolvedExam, $classId);
        $teacherPerformance = $this->analyticsService->getTeacherPerformance($resolvedSession, $resolvedExam, $classId);
        $attendanceTrend = $this->analyticsService->getAttendanceTrend($resolvedSession, $resolvedExam, $classId);
        $feeDefaulterSummary = $this->analyticsService->getFeeDefaulterSummary($resolvedSession, $resolvedExam, $classId);
        $classComparison = $this->analyticsService->getClassComparison($resolvedSession, $resolvedExam, $classId);

        return [
            'filters' => [
                'session' => $resolvedSession,
                'exam' => $resolvedExam,
                'exam_label' => $this->examLabel($resolvedExam),
                'class_id' => $classId,
                'class_name' => $className,
                'generated_at' => now()->format('Y-m-d H:i:s'),
            ],
            'summary' => $summary,
            'top_performers' => $topPerformers,
            'weak_students' => $weakStudents,
            'subject_performance' => $subjectPerformance,
            'teacher_performance' => $teacherPerformance,
            'attendance_trend' => $attendanceTrend,
            'fee_defaulter_summary' => $feeDefaulterSummary,
            'class_comparison' => $classComparison,
        ];
    }

    /**
     * @param array{session?:string|null,exam?:string|null,class_id?:int|string|null} $filters
     * @return array{
     *   filters:array{
     *     session:string,
     *     exam:string|null,
     *     exam_label:string,
     *     class_id:int|null,
     *     class_name:string|null
     *   },
     *   summary:array<string, mixed>,
     *   topPerformers:array<int, array<string, mixed>>,
     *   weakStudents:array<int, array<string, mixed>>,
     *   teacherPerformance:array<int, array<string, mixed>>,
     *   feeDefaulterSummary:array<string, mixed>
     * }
     */
    public function buildBoardSummary(array $filters): array
    {
        $resolvedSession = $this->resolveSession(isset($filters['session']) ? (string) $filters['session'] : null);
        $resolvedExam = $this->resolveExam(isset($filters['exam']) ? (string) $filters['exam'] : null);
        $classId = isset($filters['class_id']) && $filters['class_id'] !== null ? (int) $filters['class_id'] : null;

        $className = null;
        if ($classId !== null) {
            $classRoom = SchoolClass::query()->find($classId, ['id', 'name', 'section']);
            if ($classRoom) {
                $className = trim((string) $classRoom->name.' '.(string) ($classRoom->section ?? ''));
            }
        }

        return [
            'filters' => [
                'session' => $resolvedSession,
                'exam' => $resolvedExam,
                'exam_label' => $this->examLabel($resolvedExam),
                'class_id' => $classId,
                'class_name' => $className,
            ],
            'summary' => $this->analyticsService->getDashboardSummary($resolvedSession, $resolvedExam, $classId),
            'topPerformers' => array_values(array_slice(
                $this->analyticsService->getTopPerformers($resolvedSession, $resolvedExam, $classId),
                0,
                10
            )),
            'weakStudents' => array_values(array_slice(
                $this->analyticsService->getWeakStudents($resolvedSession, $resolvedExam, $classId),
                0,
                10
            )),
            'teacherPerformance' => $this->analyticsService->getTeacherPerformance($resolvedSession, $resolvedExam, $classId),
            'feeDefaulterSummary' => $this->analyticsService->getFeeDefaulterSummary($resolvedSession, $resolvedExam, $classId),
        ];
    }

    private function examLabel(?string $exam): string
    {
        return match ($exam) {
            'class_test' => 'Class Test',
            'bimonthly_test' => 'Bimonthly Test',
            'first_term' => '1st Term',
            'final_term' => 'Final Term',
            default => 'All Exams',
        };
    }
}
