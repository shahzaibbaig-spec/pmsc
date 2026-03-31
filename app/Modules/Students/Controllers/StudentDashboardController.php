<?php

namespace App\Modules\Students\Controllers;

use App\Models\Student;
use App\Models\CognitiveAssessmentAttempt;
use App\Models\FeeChallan;
use App\Modules\Fees\Services\FeeDefaulterService;
use App\Services\CognitiveAssessmentService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use Illuminate\View\View;
use RuntimeException;

class StudentDashboardController extends Controller
{
    public function __construct(
        private readonly FeeDefaulterService $feeDefaulterService,
        private readonly CognitiveAssessmentService $cognitiveAssessmentService
    )
    {
    }

    public function __invoke(): View
    {
        $user = auth()->user();
        $student = $user ? $this->resolveStudentForUser((string) $user->name, (string) $user->email) : null;
        $cognitiveAssessmentCard = $student ? $this->buildCognitiveAssessmentCard($student) : null;

        if (! $student) {
            return view('modules.student.dashboard', [
                'student' => null,
                'feeStatus' => null,
                'latestChallan' => null,
                'cognitiveAssessmentCard' => null,
                'feeMessage' => 'Student profile is not linked to this login yet. Please ask Admin to align student name or email with a student record.',
            ]);
        }

        if (! Schema::hasTable('fee_defaulters')) {
            return view('modules.student.dashboard', [
                'student' => $student,
                'feeStatus' => null,
                'latestChallan' => null,
                'cognitiveAssessmentCard' => $cognitiveAssessmentCard,
                'feeMessage' => 'Fee status is currently unavailable.',
            ]);
        }

        $session = $this->feeDefaulterService->sessionFromDate();
        $defaulter = $this->feeDefaulterService->syncStudentForSession((int) $student->id, $session);
        $breakdown = $this->feeDefaulterService->dueBreakdownForStudent((int) $student->id, $session);
        $totalDue = round((float) ($breakdown['total_due'] ?? 0), 2);
        $latestChallan = $this->latestChallan((int) $student->id, $session);

        return view('modules.student.dashboard', [
            'student' => $student,
            'feeStatus' => [
                'session' => $session,
                'total_due' => $totalDue,
                'is_defaulter' => (bool) ($defaulter?->is_active ?? false) && $totalDue > 0,
                'oldest_due_date' => $breakdown['oldest_due_date'] ?? null,
            ],
            'latestChallan' => $latestChallan,
            'cognitiveAssessmentCard' => $cognitiveAssessmentCard,
            'feeMessage' => null,
        ]);
    }

    private function latestChallan(int $studentId, string $session): ?array
    {
        $challan = FeeChallan::query()
            ->withSum('payments as paid_total', 'amount_paid')
            ->where('student_id', $studentId)
            ->where('session', $session)
            ->orderByDesc('id')
            ->first([
                'id',
                'challan_number',
                'session',
                'month',
                'issue_date',
                'due_date',
                'total_amount',
                'status',
                'created_at',
            ]);

        if (! $challan) {
            return null;
        }

        $paid = round((float) ($challan->paid_total ?? 0), 2);
        $total = round((float) $challan->total_amount, 2);
        $due = round(max($total - $paid, 0), 2);
        $createdAt = $challan->created_at;
        $isRecentlyGenerated = $createdAt !== null && $createdAt->greaterThanOrEqualTo(now()->subHours(24));

        return [
            'id' => (int) $challan->id,
            'challan_number' => (string) $challan->challan_number,
            'session' => (string) $challan->session,
            'month' => (string) ($challan->month ?? '-'),
            'issue_date' => optional($challan->issue_date)->toDateString(),
            'due_date' => optional($challan->due_date)->toDateString(),
            'status' => (string) $challan->status,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'due_amount' => $due,
            'is_recently_generated' => $isRecentlyGenerated,
        ];
    }

    private function resolveStudentForUser(string $userName, string $email): ?Student
    {
        $normalizedName = mb_strtolower(trim($userName));
        $emailLocal = mb_strtolower(trim(Str::before($email, '@')));

        if ($emailLocal !== '') {
            $byStudentId = Student::query()
                ->whereRaw('LOWER(student_id) = ?', [$emailLocal])
                ->first();

            if ($byStudentId) {
                return $byStudentId;
            }
        }

        if ($normalizedName !== '') {
            $byName = Student::query()
                ->whereRaw('LOWER(name) = ?', [$normalizedName])
                ->orderByDesc('id')
                ->get();

            if ($byName->count() === 1) {
                return $byName->first();
            }
        }

        return null;
    }

    private function buildCognitiveAssessmentCard(Student $student): ?array
    {
        if (
            ! Schema::hasTable('cognitive_assessments')
            || ! Schema::hasTable('cognitive_assessment_attempts')
            || ! Schema::hasTable('cognitive_assessment_student_assignments')
        ) {
            return null;
        }

        try {
            $assessment = $this->cognitiveAssessmentService->resolveAssessment();
        } catch (RuntimeException) {
            return null;
        }

        if (! $this->cognitiveAssessmentService->studentCanAccessAssessment($student, $assessment)) {
            return null;
        }

        /** @var CognitiveAssessmentAttempt|null $attempt */
        $attempt = $student->cognitiveAssessmentAttempts()
            ->where('assessment_id', $assessment->id)
            ->orderByDesc('id')
            ->first();

        if ($attempt && $attempt->status === CognitiveAssessmentAttempt::STATUS_IN_PROGRESS && $attempt->isExpired()) {
            $attempt = $this->cognitiveAssessmentService->submitAttempt($attempt, true);
        }

        $statusLabel = match ($attempt?->status) {
            CognitiveAssessmentAttempt::STATUS_IN_PROGRESS => 'In Progress',
            CognitiveAssessmentAttempt::STATUS_GRADED => 'Completed',
            CognitiveAssessmentAttempt::STATUS_AUTO_SUBMITTED => 'Auto Submitted',
            CognitiveAssessmentAttempt::STATUS_SUBMITTED => 'Submitted',
            CognitiveAssessmentAttempt::STATUS_RESET => 'Reset - Ready to Retake',
            default => 'Not Started',
        };

        $actionUrl = route('student.assessments.cognitive-skills-level-4.index');
        $actionLabel = 'Open Assessment';
        if ($attempt?->status === CognitiveAssessmentAttempt::STATUS_IN_PROGRESS) {
            $actionUrl = route('student.assessments.cognitive-skills-level-4.attempt', $attempt);
            $actionLabel = 'Resume Attempt';
        } elseif ($attempt?->status === CognitiveAssessmentAttempt::STATUS_GRADED) {
            $actionUrl = route('student.assessments.cognitive-skills-level-4.result', $attempt);
            $actionLabel = 'View Result';
        } elseif ($attempt?->status === CognitiveAssessmentAttempt::STATUS_RESET) {
            $actionLabel = 'Start Fresh Attempt';
        }

        return [
            'title' => (string) $assessment->title,
            'description' => (string) ($assessment->description ?? 'Internal school assessment'),
            'status_label' => $statusLabel,
            'overall_percentage' => $attempt?->status === CognitiveAssessmentAttempt::STATUS_RESET ? null : $attempt?->overall_percentage,
            'performance_band' => $attempt?->status === CognitiveAssessmentAttempt::STATUS_RESET ? null : $attempt?->performance_band,
            'submitted_at' => optional($attempt?->submitted_at)?->format('Y-m-d H:i'),
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
        ];
    }
}
