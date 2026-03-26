<?php

namespace App\Services;

use App\Models\ClassPromotionMapping;
use App\Models\Exam;
use App\Models\PromotionCampaign;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentPromotion;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Modules\Exams\Enums\ExamType;
use App\Modules\Results\Services\ResultService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PromotionService
{
    private const PASS_PERCENTAGE = 60.0;

    public function __construct(private readonly ResultService $resultService)
    {
    }

    /**
     * @param array{from_session:string,to_session:string,class_id:int} $payload
     */
    public function createCampaign(array $payload, User $user): PromotionCampaign
    {
        $fromSession = trim((string) ($payload['from_session'] ?? ''));
        $toSession = trim((string) ($payload['to_session'] ?? ''));
        $classId = (int) ($payload['class_id'] ?? 0);

        if ($classId <= 0 || $fromSession === '' || $toSession === '') {
            throw new RuntimeException('Class and sessions are required to create promotion campaign.');
        }

        $this->assertSessionTransition($fromSession, $toSession);
        $this->ensureClassTeacherForClassAndSession($user, $classId, $fromSession);

        return DB::transaction(function () use ($fromSession, $toSession, $classId, $user): PromotionCampaign {
            $campaign = PromotionCampaign::query()->firstOrCreate(
                [
                    'from_session' => $fromSession,
                    'to_session' => $toSession,
                    'class_id' => $classId,
                ],
                [
                    'created_by' => (int) $user->id,
                    'status' => PromotionCampaign::STATUS_DRAFT,
                ]
            );

            if ((int) $campaign->created_by !== (int) $user->id && $campaign->status !== PromotionCampaign::STATUS_EXECUTED) {
                $campaign->created_by = (int) $user->id;
                $campaign->save();
            }

            $this->syncStudentPromotions($campaign);

            return $campaign->fresh([
                'classRoom:id,name,section',
                'creator:id,name',
                'approver:id,name',
            ]) ?? $campaign;
        });
    }

    /**
     * @return array{
     *   campaign:PromotionCampaign,
     *   rows:Collection<int,StudentPromotion>,
     *   summary:array{
     *     total_students:int,
     *     passed_students:int,
     *     promoted:int,
     *     conditional_promoted:int,
     *     retained:int
     *   },
     *   next_class_id:?int,
     *   next_class_label:?string
     * }
     */
    public function loadEligibleStudents(PromotionCampaign $campaign): array
    {
        $this->syncStudentPromotions($campaign);

        $rows = StudentPromotion::query()
            ->with([
                'student:id,student_id,name,class_id,status',
                'student.classRoom:id,name,section',
                'fromClass:id,name,section',
                'toClass:id,name,section',
            ])
            ->where('promotion_campaign_id', (int) $campaign->id)
            ->orderByDesc('is_passed')
            ->orderByDesc('final_percentage')
            ->orderBy('student_id')
            ->get();

        $nextClassId = $this->resolveNextClassId((int) $campaign->class_id);
        $nextClassLabel = $nextClassId !== null ? $this->classLabel($nextClassId) : null;

        return [
            'campaign' => $campaign->fresh([
                'classRoom:id,name,section',
                'creator:id,name',
                'approver:id,name',
            ]) ?? $campaign,
            'rows' => $rows,
            'summary' => [
                'total_students' => $rows->count(),
                'passed_students' => $rows->where('is_passed', true)->count(),
                'promoted' => $rows->where('teacher_decision', StudentPromotion::DECISION_PROMOTE)->count(),
                'conditional_promoted' => $rows->where('teacher_decision', StudentPromotion::DECISION_CONDITIONAL_PROMOTE)->count(),
                'retained' => $rows->where('teacher_decision', StudentPromotion::DECISION_RETAIN)->count(),
            ],
            'next_class_id' => $nextClassId,
            'next_class_label' => $nextClassLabel,
        ];
    }

    /**
     * @param array<int,array{id:int,teacher_decision?:string|null,teacher_note?:string|null}> $rows
     */
    public function saveTeacherDecisions(PromotionCampaign $campaign, array $rows, User $user): PromotionCampaign
    {
        $this->ensureClassTeacherForCampaign($user, $campaign);
        $this->assertTeacherEditableCampaign($campaign);

        DB::transaction(function () use ($campaign, $rows): void {
            $this->syncStudentPromotions($campaign);

            $records = StudentPromotion::query()
                ->where('promotion_campaign_id', (int) $campaign->id)
                ->whereIn('id', collect($rows)->pluck('id')->map(fn ($id): int => (int) $id))
                ->get()
                ->keyBy(fn (StudentPromotion $row): int => (int) $row->id);

            $nextClassId = $this->resolveNextClassId((int) $campaign->class_id);

            foreach ($rows as $row) {
                $record = $records->get((int) ($row['id'] ?? 0));
                if (! $record) {
                    continue;
                }

                $decision = $this->normalizeDecision($row['teacher_decision'] ?? null);
                $note = $this->normalizeNote($row['teacher_note'] ?? null);

                if ($decision !== null && ! in_array($decision, StudentPromotion::DECISIONS, true)) {
                    throw new RuntimeException('Invalid teacher decision detected for one or more rows.');
                }

                if ($decision !== null) {
                    $this->assertDecisionNote($decision, $note, 'Teacher note is required');
                }

                $record->teacher_decision = $decision;
                $record->teacher_note = $note;
                $record->final_status = StudentPromotion::STATUS_PENDING;
                $record->to_class_id = $this->targetClassIdForDecision(
                    $decision,
                    (int) $record->from_class_id,
                    $nextClassId
                );

                if (in_array($decision, [StudentPromotion::DECISION_PROMOTE, StudentPromotion::DECISION_CONDITIONAL_PROMOTE], true)
                    && $record->to_class_id === null) {
                    throw new RuntimeException('Class promotion mapping is missing. Configure next class mapping before saving this decision.');
                }

                $record->save();
            }

            if ($campaign->status === PromotionCampaign::STATUS_REJECTED) {
                $campaign->status = PromotionCampaign::STATUS_DRAFT;
                $campaign->submitted_at = null;
                $campaign->approved_at = null;
                $campaign->approved_by = null;
                $campaign->principal_note = null;
                $campaign->save();
            }
        });

        return $campaign->fresh([
            'classRoom:id,name,section',
            'creator:id,name',
            'approver:id,name',
        ]) ?? $campaign;
    }

    public function submitToPrincipal(PromotionCampaign $campaign, User $user): PromotionCampaign
    {
        $this->ensureClassTeacherForCampaign($user, $campaign);
        $this->assertTeacherEditableCampaign($campaign);

        DB::transaction(function () use ($campaign): void {
            $this->syncStudentPromotions($campaign);

            $rows = StudentPromotion::query()
                ->with('student:id,name,student_id')
                ->where('promotion_campaign_id', (int) $campaign->id)
                ->orderBy('id')
                ->get();

            if ($rows->isEmpty()) {
                throw new RuntimeException('No eligible students were found for this campaign.');
            }

            $nextClassId = $this->resolveNextClassId((int) $campaign->class_id);

            foreach ($rows as $row) {
                $decision = $row->teacher_decision;
                if ($decision === null && $row->is_passed) {
                    $decision = StudentPromotion::DECISION_PROMOTE;
                    $row->teacher_decision = $decision;
                }

                if ($decision === null) {
                    throw new RuntimeException(sprintf(
                        'Teacher decision is missing for %s (%s).',
                        (string) ($row->student?->name ?? 'Student'),
                        (string) ($row->student?->student_id ?? $row->student_id)
                    ));
                }

                $this->assertDecisionNote(
                    $decision,
                    $this->normalizeNote($row->teacher_note),
                    'Teacher note is required'
                );

                $row->to_class_id = $this->targetClassIdForDecision(
                    $decision,
                    (int) $row->from_class_id,
                    $nextClassId
                );

                if (in_array($decision, [StudentPromotion::DECISION_PROMOTE, StudentPromotion::DECISION_CONDITIONAL_PROMOTE], true)
                    && $row->to_class_id === null) {
                    throw new RuntimeException('Class promotion mapping is missing for selected class. Add mapping before submit.');
                }

                $row->final_status = StudentPromotion::STATUS_PENDING;
                $row->save();
            }

            $campaign->status = PromotionCampaign::STATUS_SUBMITTED;
            $campaign->submitted_at = now();
            $campaign->approved_at = null;
            $campaign->approved_by = null;
            $campaign->principal_note = null;
            $campaign->save();
        });

        return $campaign->fresh([
            'classRoom:id,name,section',
            'creator:id,name',
            'approver:id,name',
        ]) ?? $campaign;
    }

    /**
     * @param array<int,array{id:int,principal_decision?:string|null,principal_note?:string|null}> $rows
     */
    public function reviewByPrincipal(PromotionCampaign $campaign, array $rows, User $user): PromotionCampaign
    {
        $this->ensurePrincipalOrAdmin($user);

        if ($campaign->status !== PromotionCampaign::STATUS_SUBMITTED) {
            throw new RuntimeException('Only submitted campaigns can be reviewed by principal.');
        }

        DB::transaction(function () use ($campaign, $rows): void {
            $records = StudentPromotion::query()
                ->where('promotion_campaign_id', (int) $campaign->id)
                ->whereIn('id', collect($rows)->pluck('id')->map(fn ($id): int => (int) $id))
                ->get()
                ->keyBy(fn (StudentPromotion $row): int => (int) $row->id);

            $nextClassId = $this->resolveNextClassId((int) $campaign->class_id);

            foreach ($rows as $row) {
                $record = $records->get((int) ($row['id'] ?? 0));
                if (! $record) {
                    continue;
                }

                $principalDecision = $this->normalizeDecision($row['principal_decision'] ?? null);
                $principalNote = $this->normalizeNote($row['principal_note'] ?? null);

                if ($principalDecision !== null && ! in_array($principalDecision, StudentPromotion::DECISIONS, true)) {
                    throw new RuntimeException('Invalid principal decision detected for one or more rows.');
                }

                if ($principalDecision !== null) {
                    $this->assertDecisionNote($principalDecision, $principalNote, 'Principal note is required');
                    $record->to_class_id = $this->targetClassIdForDecision(
                        $principalDecision,
                        (int) $record->from_class_id,
                        $nextClassId
                    );

                    if (in_array($principalDecision, [StudentPromotion::DECISION_PROMOTE, StudentPromotion::DECISION_CONDITIONAL_PROMOTE], true)
                        && $record->to_class_id === null) {
                        throw new RuntimeException('Class promotion mapping is missing. Add mapping before principal review.');
                    }
                }

                $record->principal_decision = $principalDecision;
                $record->principal_note = $principalNote;
                $record->final_status = StudentPromotion::STATUS_PENDING;
                $record->save();
            }
        });

        return $campaign->fresh([
            'classRoom:id,name,section',
            'creator:id,name',
            'approver:id,name',
        ]) ?? $campaign;
    }

    public function approveCampaign(PromotionCampaign $campaign, ?string $note, User $user): PromotionCampaign
    {
        $this->ensurePrincipalOrAdmin($user);

        if ($campaign->status !== PromotionCampaign::STATUS_SUBMITTED) {
            throw new RuntimeException('Only submitted campaigns can be approved.');
        }

        DB::transaction(function () use ($campaign, $note, $user): void {
            $rows = StudentPromotion::query()
                ->where('promotion_campaign_id', (int) $campaign->id)
                ->orderBy('id')
                ->get();

            if ($rows->isEmpty()) {
                throw new RuntimeException('No student promotion rows found for this campaign.');
            }

            $nextClassId = $this->resolveNextClassId((int) $campaign->class_id);

            foreach ($rows as $row) {
                $decision = $row->principal_decision ?? $row->teacher_decision;
                if ($decision === null) {
                    throw new RuntimeException('Principal approval failed because some students have no decision.');
                }

                $effectiveNote = $this->normalizeNote($row->principal_note) ?? $this->normalizeNote($row->teacher_note);
                $this->assertDecisionNote($decision, $effectiveNote, 'Decision note is required');

                $row->principal_decision = $decision;
                if ($row->principal_note === null && $effectiveNote !== null && $row->principal_note !== $row->teacher_note) {
                    $row->principal_note = $effectiveNote;
                }
                $row->to_class_id = $this->targetClassIdForDecision(
                    $decision,
                    (int) $row->from_class_id,
                    $nextClassId
                );

                if (in_array($decision, [StudentPromotion::DECISION_PROMOTE, StudentPromotion::DECISION_CONDITIONAL_PROMOTE], true)
                    && $row->to_class_id === null) {
                    throw new RuntimeException('Class promotion mapping is missing. Add class mapping before approval.');
                }

                $row->final_status = StudentPromotion::STATUS_APPROVED;
                $row->save();
            }

            $campaign->status = PromotionCampaign::STATUS_APPROVED;
            $campaign->approved_by = (int) $user->id;
            $campaign->approved_at = now();
            $campaign->principal_note = $this->normalizeNote($note);
            $campaign->save();
        });

        return $campaign->fresh([
            'classRoom:id,name,section',
            'creator:id,name',
            'approver:id,name',
        ]) ?? $campaign;
    }

    public function rejectCampaign(PromotionCampaign $campaign, string $note, User $user): PromotionCampaign
    {
        $this->ensurePrincipalOrAdmin($user);

        if (! in_array($campaign->status, [PromotionCampaign::STATUS_SUBMITTED, PromotionCampaign::STATUS_APPROVED], true)) {
            throw new RuntimeException('Only submitted or approved campaigns can be rejected.');
        }

        $normalizedNote = $this->normalizeNote($note);
        if ($normalizedNote === null) {
            throw new RuntimeException('Principal rejection note is required.');
        }

        DB::transaction(function () use ($campaign, $normalizedNote, $user): void {
            StudentPromotion::query()
                ->where('promotion_campaign_id', (int) $campaign->id)
                ->update([
                    'final_status' => StudentPromotion::STATUS_REJECTED,
                ]);

            $campaign->status = PromotionCampaign::STATUS_REJECTED;
            $campaign->approved_by = (int) $user->id;
            $campaign->approved_at = now();
            $campaign->principal_note = $normalizedNote;
            $campaign->save();
        });

        return $campaign->fresh([
            'classRoom:id,name,section',
            'creator:id,name',
            'approver:id,name',
        ]) ?? $campaign;
    }

    public function executeCampaign(PromotionCampaign $campaign, User $user): PromotionCampaign
    {
        $this->ensurePrincipalOrAdmin($user);

        if ($campaign->status !== PromotionCampaign::STATUS_APPROVED) {
            throw new RuntimeException('Only approved campaigns can be executed.');
        }

        DB::transaction(function () use ($campaign): void {
            $rows = StudentPromotion::query()
                ->with('student:id,class_id,created_at')
                ->where('promotion_campaign_id', (int) $campaign->id)
                ->where('final_status', StudentPromotion::STATUS_APPROVED)
                ->orderBy('id')
                ->get();

            if ($rows->isEmpty()) {
                throw new RuntimeException('No approved student rows found to execute.');
            }

            $nextClassId = $this->resolveNextClassId((int) $campaign->class_id);

            foreach ($rows as $row) {
                $decision = $row->principal_decision ?? $row->teacher_decision;
                if ($decision === null) {
                    throw new RuntimeException('Execution failed because some students do not have an approved decision.');
                }

                $targetClassId = $row->to_class_id ?: $this->targetClassIdForDecision(
                    $decision,
                    (int) $row->from_class_id,
                    $nextClassId
                );

                if ($targetClassId === null) {
                    throw new RuntimeException('Class promotion mapping is missing. Add mapping before execution.');
                }

                $student = $row->student;
                if (! $student) {
                    continue;
                }

                $this->syncClassHistoryForExecution(
                    student: $student,
                    fromClassId: (int) $row->from_class_id,
                    toClassId: (int) $targetClassId,
                    fromSession: (string) $campaign->from_session,
                    toSession: (string) $campaign->to_session,
                    decision: (string) $decision
                );

                $student->class_id = (int) $targetClassId;
                $student->save();

                $row->to_class_id = (int) $targetClassId;
                $row->final_status = StudentPromotion::STATUS_EXECUTED;
                $row->save();
            }

            $campaign->status = PromotionCampaign::STATUS_EXECUTED;
            $campaign->executed_at = now();
            $campaign->save();
        });

        return $campaign->fresh([
            'classRoom:id,name,section',
            'creator:id,name',
            'approver:id,name',
        ]) ?? $campaign;
    }

    public function resolveNextClassId(int $fromClassId): ?int
    {
        return ClassPromotionMapping::query()
            ->where('from_class_id', $fromClassId)
            ->value('to_class_id');
    }

    private function syncStudentPromotions(PromotionCampaign $campaign): void
    {
        $finalExamIds = Exam::query()
            ->where('class_id', (int) $campaign->class_id)
            ->where('session', (string) $campaign->from_session)
            ->where('exam_type', ExamType::FinalTerm->value)
            ->pluck('id');

        if ($finalExamIds->isEmpty()) {
            throw new RuntimeException('Final Term exam results are required before creating promotion recommendations.');
        }

        $students = Student::query()
            ->where('class_id', (int) $campaign->class_id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'class_id']);

        if ($students->isEmpty()) {
            throw new RuntimeException('No active students found in selected class for this promotion campaign.');
        }

        $studentIds = $students->pluck('id')->map(fn ($id): int => (int) $id)->values();
        $marks = DB::table('marks')
            ->selectRaw('student_id, SUM(obtained_marks) as obtained_total, SUM(total_marks) as total_total')
            ->whereIn('exam_id', $finalExamIds)
            ->whereIn('student_id', $studentIds)
            ->groupBy('student_id')
            ->get()
            ->keyBy(fn ($row): int => (int) $row->student_id);

        $nextClassId = $this->resolveNextClassId((int) $campaign->class_id);

        foreach ($students as $student) {
            $markRow = $marks->get((int) $student->id);
            $obtainedTotal = (float) ($markRow->obtained_total ?? 0);
            $totalTotal = (float) ($markRow->total_total ?? 0);

            $percentage = $totalTotal > 0
                ? round(($obtainedTotal / $totalTotal) * 100, 2)
                : null;
            $grade = $percentage !== null
                ? $this->resultService->computeGrade($percentage)
                : null;
            $isPassed = $percentage !== null && $percentage >= self::PASS_PERCENTAGE;

            $row = StudentPromotion::query()->firstOrNew([
                'promotion_campaign_id' => (int) $campaign->id,
                'student_id' => (int) $student->id,
            ]);

            $row->from_class_id = (int) $campaign->class_id;
            $row->final_percentage = $percentage;
            $row->final_grade = $grade;
            $row->is_passed = $isPassed;
            $row->final_status = $row->final_status ?: StudentPromotion::STATUS_PENDING;

            if ($row->teacher_decision === null && $isPassed) {
                $row->teacher_decision = StudentPromotion::DECISION_PROMOTE;
            }

            $effectiveDecision = $row->principal_decision ?? $row->teacher_decision;
            $row->to_class_id = $this->targetClassIdForDecision(
                $effectiveDecision,
                (int) $campaign->class_id,
                $nextClassId
            );

            $row->save();
        }

        StudentPromotion::query()
            ->where('promotion_campaign_id', (int) $campaign->id)
            ->whereNotIn('student_id', $studentIds)
            ->delete();
    }

    private function ensureClassTeacherForClassAndSession(User $user, int $classId, string $session): void
    {
        $teacherId = $this->teacherIdForUser((int) $user->id);
        if (! $teacherId) {
            throw new RuntimeException('Teacher profile was not found for this account.');
        }

        $isAssigned = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('class_id', $classId)
            ->where('session', $session)
            ->where('is_class_teacher', true)
            ->exists();

        if (! $isAssigned) {
            throw new RuntimeException('Only the assigned class teacher can prepare recommendations for this class/session.');
        }
    }

    private function ensureClassTeacherForCampaign(User $user, PromotionCampaign $campaign): void
    {
        $this->ensureClassTeacherForClassAndSession(
            $user,
            (int) $campaign->class_id,
            (string) $campaign->from_session
        );
    }

    private function ensurePrincipalOrAdmin(User $user): void
    {
        if (! $user->hasAnyRole(['Admin', 'Principal'])) {
            throw new RuntimeException('Only principal or admin can review class promotion campaigns.');
        }
    }

    private function assertTeacherEditableCampaign(PromotionCampaign $campaign): void
    {
        if (! in_array($campaign->status, [PromotionCampaign::STATUS_DRAFT, PromotionCampaign::STATUS_REJECTED], true)) {
            throw new RuntimeException('This campaign is no longer editable by class teacher.');
        }
    }

    private function targetClassIdForDecision(?string $decision, int $fromClassId, ?int $nextClassId): ?int
    {
        return match ($decision) {
            StudentPromotion::DECISION_PROMOTE,
            StudentPromotion::DECISION_CONDITIONAL_PROMOTE => $nextClassId,
            StudentPromotion::DECISION_RETAIN => $fromClassId,
            default => null,
        };
    }

    private function assertDecisionNote(string $decision, ?string $note, string $prefix): void
    {
        if (in_array($decision, [StudentPromotion::DECISION_CONDITIONAL_PROMOTE, StudentPromotion::DECISION_RETAIN], true)
            && $note === null) {
            throw new RuntimeException($prefix.' for conditional promotion or retain decision.');
        }
    }

    private function teacherIdForUser(int $userId): ?int
    {
        return Teacher::query()
            ->where('user_id', $userId)
            ->value('id');
    }

    private function normalizeDecision(mixed $decision): ?string
    {
        $value = trim((string) $decision);

        return $value !== '' ? $value : null;
    }

    private function normalizeNote(mixed $note): ?string
    {
        $value = trim((string) $note);

        return $value !== '' ? $value : null;
    }

    private function classLabel(int $classId): ?string
    {
        $classRoom = SchoolClass::query()
            ->find($classId, ['id', 'name', 'section']);

        if (! $classRoom) {
            return null;
        }

        $label = trim((string) $classRoom->name.' '.(string) ($classRoom->section ?? ''));

        return $label !== '' ? $label : ('Class '.$classRoom->id);
    }

    private function syncClassHistoryForExecution(
        Student $student,
        int $fromClassId,
        int $toClassId,
        string $fromSession,
        string $toSession,
        string $decision
    ): void {
        $fromStatus = match ($decision) {
            StudentPromotion::DECISION_PROMOTE => StudentClassHistory::STATUS_PROMOTED,
            StudentPromotion::DECISION_CONDITIONAL_PROMOTE => StudentClassHistory::STATUS_CONDITIONAL_PROMOTED,
            StudentPromotion::DECISION_RETAIN => StudentClassHistory::STATUS_RETAINED,
            default => StudentClassHistory::STATUS_COMPLETED,
        };

        $fromHistory = StudentClassHistory::query()->firstOrNew([
            'student_id' => (int) $student->id,
            'class_id' => $fromClassId,
            'session' => $fromSession,
        ]);

        if ($fromHistory->joined_on === null) {
            $fromHistory->joined_on = optional($student->created_at)->toDateString() ?: now()->toDateString();
        }

        $fromHistory->status = $fromStatus;
        $fromHistory->left_on = now()->toDateString();
        $fromHistory->save();

        $toHistory = StudentClassHistory::query()->firstOrNew([
            'student_id' => (int) $student->id,
            'class_id' => $toClassId,
            'session' => $toSession,
        ]);

        if ($toHistory->joined_on === null) {
            $toHistory->joined_on = now()->toDateString();
        }

        $toHistory->left_on = null;
        $toHistory->status = StudentClassHistory::STATUS_ACTIVE;
        $toHistory->save();
    }

    private function assertSessionTransition(string $fromSession, string $toSession): void
    {
        $from = $this->parseAcademicSession($fromSession);
        $to = $this->parseAcademicSession($toSession);

        if ($from === null || $to === null) {
            throw new RuntimeException('Invalid session format. Use YYYY-YYYY.');
        }

        if ($to['start'] !== $from['end'] || $to['end'] !== ($to['start'] + 1)) {
            throw new RuntimeException('To session must immediately follow from session.');
        }
    }

    /**
     * @return array{start:int,end:int}|null
     */
    private function parseAcademicSession(string $session): ?array
    {
        if (preg_match('/^(\d{4})-(\d{4})$/', trim($session), $matches) !== 1) {
            return null;
        }

        $start = (int) $matches[1];
        $end = (int) $matches[2];

        if ($end !== ($start + 1)) {
            return null;
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }
}
