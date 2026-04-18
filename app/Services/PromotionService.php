<?php

namespace App\Services;

use App\Models\ClassSection;
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
    private const PROMOTION_STAGE_FLOW = [
        'playgroup' => 'nursery',
        'nursery' => 'prep',
        'prep' => '1',
        '1' => '2',
        '2' => '3',
        '3' => '4',
        '4' => '5',
        '5' => '6',
        '6' => '7',
        '7' => '8',
        '8' => '9',
        '9' => '10',
        '10' => '11',
        '11' => '12',
        '12' => null,
    ];

    public function __construct(private readonly ResultService $resultService)
    {
    }

    /**
     * @param array{from_session:string,to_session:string,class_id:int} $payload
     */
    public function createTeacherCampaign(array $payload, User $user): PromotionCampaign
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

    public function createCampaign(string $fromSession, string $toSession, int $classId, int $userId): PromotionCampaign
    {
        return $this->createPrincipalGroupCampaign($fromSession, $toSession, $classId, $userId);
    }

    public function createPrincipalGroupCampaign(
        string $fromSession,
        string $toSession,
        int $classId,
        int $principalUserId
    ): PromotionCampaign {
        $normalizedFromSession = trim($fromSession);
        $normalizedToSession = trim($toSession);
        $normalizedClassId = (int) $classId;

        if ($normalizedClassId <= 0 || $normalizedFromSession === '' || $normalizedToSession === '') {
            throw new RuntimeException('Class and sessions are required to create promotion campaign.');
        }

        $principal = $this->findUserOrFail($principalUserId);
        $this->ensurePrincipalOrAdmin($principal);
        $this->assertSessionTransition($normalizedFromSession, $normalizedToSession);

        return DB::transaction(function () use (
            $normalizedFromSession,
            $normalizedToSession,
            $normalizedClassId,
            $principal
        ): PromotionCampaign {
            $campaign = PromotionCampaign::query()->firstOrCreate(
                [
                    'from_session' => $normalizedFromSession,
                    'to_session' => $normalizedToSession,
                    'class_id' => $normalizedClassId,
                ],
                [
                    'created_by' => (int) $principal->id,
                    'status' => PromotionCampaign::STATUS_DRAFT,
                ]
            );

            if ($campaign->status === PromotionCampaign::STATUS_EXECUTED) {
                throw new RuntimeException('This campaign is already executed and cannot be modified.');
            }

            if ((int) $campaign->created_by !== (int) $principal->id) {
                $campaign->created_by = (int) $principal->id;
                $campaign->save();
            }

            if (in_array($campaign->status, [PromotionCampaign::STATUS_REJECTED, PromotionCampaign::STATUS_APPROVED], true)) {
                $this->resetCampaignApprovalState($campaign);
            }

            $this->syncStudentPromotions($campaign, allowWithoutFinalExam: true);

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
     *     passed_out:int,
     *     conditional_promoted:int,
     *     retained:int,
     *     pending:int
     *   },
     *   is_terminal_class:bool,
     *   next_class_id:?int,
     *   next_class_label:?string
     * }
     */
    public function loadEligibleStudents(PromotionCampaign|int $campaign, ?string $fromSession = null): array
    {
        if (is_int($campaign)) {
            $normalizedSession = trim((string) $fromSession);
            if ($normalizedSession === '') {
                throw new RuntimeException('From session is required to load eligible students.');
            }

            $nextClassId = $this->resolveNextClassId($campaign);

            return [
                'students' => $this->loadEligibleStudentsForClassAndSession($campaign, $normalizedSession),
                'next_class_id' => $nextClassId,
                'next_class_label' => $nextClassId !== null ? $this->classLabel($nextClassId) : null,
                'is_terminal_class' => $nextClassId === null,
            ];
        }

        $this->syncStudentPromotions(
            $campaign,
            allowWithoutFinalExam: $this->canSkipFinalExamRequirementForCampaign($campaign)
        );

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
        $isTerminalClass = $nextClassId === null;
        $passedOutCount = $rows->filter(function (StudentPromotion $row) use ($isTerminalClass): bool {
            $decision = $row->principal_decision ?? $row->teacher_decision;

            return $isTerminalClass
                && $decision === StudentPromotion::DECISION_PROMOTE
                && (bool) $row->is_passed;
        })->count();
        $promotedCount = $rows->filter(function (StudentPromotion $row) use ($isTerminalClass): bool {
            $decision = $row->principal_decision ?? $row->teacher_decision;

            return $decision === StudentPromotion::DECISION_PROMOTE
                && (! $isTerminalClass || ! (bool) $row->is_passed);
        })->count();

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
                'promoted' => $promotedCount,
                'passed_out' => $passedOutCount,
                'conditional_promoted' => $rows->filter(
                    fn (StudentPromotion $row): bool => ($row->principal_decision ?? $row->teacher_decision) === StudentPromotion::DECISION_CONDITIONAL_PROMOTE
                )->count(),
                'retained' => $rows->filter(
                    fn (StudentPromotion $row): bool => ($row->principal_decision ?? $row->teacher_decision) === StudentPromotion::DECISION_RETAIN
                )->count(),
                'pending' => $rows->filter(
                    fn (StudentPromotion $row): bool => ($row->principal_decision ?? $row->teacher_decision) === null
                )->count(),
            ],
            'is_terminal_class' => $isTerminalClass,
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

                if ($decision === StudentPromotion::DECISION_CONDITIONAL_PROMOTE && $nextClassId === null) {
                    throw new RuntimeException('Terminal classes cannot use conditional promotion.');
                }

                if ($decision === StudentPromotion::DECISION_PROMOTE && $record->to_class_id === null
                    && ! $this->isTerminalPassedOutDecision($decision, (bool) $record->is_passed, $nextClassId)) {
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

                if ($decision === StudentPromotion::DECISION_CONDITIONAL_PROMOTE && $nextClassId === null) {
                    throw new RuntimeException('Terminal classes cannot use conditional promotion.');
                }

                if ($decision === StudentPromotion::DECISION_PROMOTE && $row->to_class_id === null
                    && ! $this->isTerminalPassedOutDecision($decision, (bool) $row->is_passed, $nextClassId)) {
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

        if (in_array($campaign->status, [PromotionCampaign::STATUS_EXECUTED, PromotionCampaign::STATUS_REJECTED], true)) {
            throw new RuntimeException('This campaign is locked and cannot be reviewed.');
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

                    $this->assertMappedDecisionForPrincipal(
                        $principalDecision,
                        $nextClassId,
                        (bool) $record->is_passed
                    );
                }

                $record->principal_decision = $principalDecision;
                $record->principal_note = $principalNote;
                $record->final_status = StudentPromotion::STATUS_PENDING;
                $record->save();
            }

            if ($campaign->status === PromotionCampaign::STATUS_APPROVED) {
                $this->resetCampaignApprovalState($campaign);
            }
        });

        return $campaign->fresh([
            'classRoom:id,name,section',
            'creator:id,name',
            'approver:id,name',
        ]) ?? $campaign;
    }

    public function applyPrincipalGroupPromotion(
        int $campaignId,
        array $studentIds,
        string $decision,
        int $principalUserId,
        ?string $note = null
    ): void {
        $principal = $this->findUserOrFail($principalUserId);
        $this->ensurePrincipalOrAdmin($principal);

        $campaign = $this->findCampaignOrFail($campaignId);
        if ($campaign->status === PromotionCampaign::STATUS_EXECUTED) {
            throw new RuntimeException('Executed campaign cannot be modified.');
        }

        $normalizedDecision = $this->normalizeDecision($decision);
        if ($normalizedDecision === null || ! in_array($normalizedDecision, StudentPromotion::DECISIONS, true)) {
            throw new RuntimeException('Invalid group action decision.');
        }

        $normalizedNote = $this->normalizeNote($note);
        $this->assertDecisionNote($normalizedDecision, $normalizedNote, 'Principal note is required');

        $nextClassId = $this->resolveNextClassId((int) $campaign->class_id);

        $studentIdList = collect($studentIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($studentIdList->isEmpty()) {
            throw new RuntimeException('Select at least one student for group action.');
        }

        DB::transaction(function () use (
            $campaign,
            $studentIdList,
            $normalizedDecision,
            $normalizedNote,
            $nextClassId
        ): void {
            $this->syncStudentPromotions(
                $campaign,
                allowWithoutFinalExam: $this->canSkipFinalExamRequirementForCampaign($campaign)
            );

            $rows = StudentPromotion::query()
                ->where('promotion_campaign_id', (int) $campaign->id)
                ->whereIn('student_id', $studentIdList)
                ->get();

            if ($rows->isEmpty()) {
                throw new RuntimeException('No matching campaign rows found for selected students.');
            }

            foreach ($rows as $row) {
                $this->assertMappedDecisionForPrincipal(
                    $normalizedDecision,
                    $nextClassId,
                    (bool) $row->is_passed
                );

                $row->principal_decision = $normalizedDecision;
                $row->principal_note = $normalizedNote;
                $row->to_class_id = $this->targetClassIdForDecision(
                    $normalizedDecision,
                    (int) $row->from_class_id,
                    $nextClassId
                );
                $row->final_status = StudentPromotion::STATUS_PENDING;
                $row->save();
            }

            if (in_array($campaign->status, [PromotionCampaign::STATUS_APPROVED, PromotionCampaign::STATUS_REJECTED], true)) {
                $this->resetCampaignApprovalState($campaign);
            }
        });
    }

    public function approveCampaign(int $campaignId, int $principalUserId, ?string $note = null): PromotionCampaign
    {
        $principal = $this->findUserOrFail($principalUserId);
        $this->ensurePrincipalOrAdmin($principal);

        $campaign = $this->findCampaignOrFail($campaignId);

        if (! in_array($campaign->status, [PromotionCampaign::STATUS_DRAFT, PromotionCampaign::STATUS_SUBMITTED], true)) {
            throw new RuntimeException('Only draft or submitted campaigns can be approved.');
        }

        DB::transaction(function () use ($campaign, $principal, $note): void {
            $this->syncStudentPromotions(
                $campaign,
                allowWithoutFinalExam: $this->canSkipFinalExamRequirementForCampaign($campaign)
            );

            $rows = StudentPromotion::query()
                ->with('student:id,name,student_id')
                ->where('promotion_campaign_id', (int) $campaign->id)
                ->orderBy('id')
                ->get();

            if ($rows->isEmpty()) {
                throw new RuntimeException('No student promotion rows found for this campaign.');
            }

            $studentsWithPendingDecision = $rows
                ->filter(fn (StudentPromotion $row): bool => ($row->principal_decision ?? $row->teacher_decision) === null)
                ->map(function (StudentPromotion $row): string {
                    return sprintf(
                        '%s (%s)',
                        (string) ($row->student?->name ?? 'Student'),
                        (string) ($row->student?->student_id ?? $row->student_id)
                    );
                })
                ->values();

            if ($studentsWithPendingDecision->isNotEmpty()) {
                throw new RuntimeException(sprintf(
                    'Campaign approval failed because decisions are pending for: %s.',
                    $studentsWithPendingDecision->implode(', ')
                ));
            }

            $nextClassId = $this->resolveNextClassId((int) $campaign->class_id);

            foreach ($rows as $row) {
                $decision = $row->principal_decision ?? $row->teacher_decision;

                $effectiveNote = $this->normalizeNote($row->principal_note) ?? $this->normalizeNote($row->teacher_note);
                $this->assertDecisionNote($decision, $effectiveNote, 'Decision note is required');
                $this->assertMappedDecisionForPrincipal(
                    $decision,
                    $nextClassId,
                    (bool) $row->is_passed
                );

                $row->principal_decision = $decision;
                if ($this->normalizeNote($row->principal_note) === null) {
                    $row->principal_note = $effectiveNote;
                }
                $row->to_class_id = $this->targetClassIdForDecision(
                    $decision,
                    (int) $row->from_class_id,
                    $nextClassId
                );
                $row->final_status = StudentPromotion::STATUS_APPROVED;
                $row->save();
            }

            $campaign->status = PromotionCampaign::STATUS_APPROVED;
            $campaign->approved_by = (int) $principal->id;
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

    public function executeCampaign(int $campaignId, int $principalUserId): PromotionCampaign
    {
        $principal = $this->findUserOrFail($principalUserId);
        $this->ensurePrincipalOrAdmin($principal);

        $campaign = $this->findCampaignOrFail($campaignId);

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

                $isPassedOut = $this->isTerminalPassedOutDecision(
                    $decision,
                    (bool) $row->is_passed,
                    $nextClassId
                );
                $this->assertMappedDecisionForPrincipal(
                    $decision,
                    $nextClassId,
                    (bool) $row->is_passed
                );

                $targetClassId = $row->to_class_id ?: $this->targetClassIdForDecision(
                    $decision,
                    (int) $row->from_class_id,
                    $nextClassId
                );

                if ($targetClassId === null && ! $isPassedOut) {
                    throw new RuntimeException('Class promotion mapping is missing. Add mapping before execution.');
                }

                $student = $row->student;
                if (! $student) {
                    continue;
                }

                if (! $isPassedOut) {
                    $this->assertNoTargetSessionHistory((int) $student->id, (string) $campaign->to_session, (string) $student->name);
                }

                $this->syncClassHistoryForExecution(
                    student: $student,
                    fromClassId: (int) $row->from_class_id,
                    toClassId: $targetClassId !== null ? (int) $targetClassId : null,
                    fromSession: (string) $campaign->from_session,
                    toSession: (string) $campaign->to_session,
                    decision: (string) $decision,
                    isPassedOut: $isPassedOut
                );

                if ($isPassedOut) {
                    $student->status = 'inactive';
                } else {
                    $this->ensureTargetClassSectionForPromotion(
                        (int) $row->from_class_id,
                        (int) $targetClassId
                    );
                    $student->class_id = (int) $targetClassId;
                }
                $student->save();

                $row->to_class_id = $isPassedOut ? null : (int) $targetClassId;
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

    /**
     * @return array{
     *   campaigns_undone:int,
     *   approved_campaigns_undone:int,
     *   executed_campaigns_undone:int,
     *   student_rows_reset:int,
     *   students_reverted:int
     * }
     */
    public function undoApprovedAndExecutedCampaigns(int $principalUserId): array
    {
        $principal = $this->findUserOrFail($principalUserId);
        $this->ensurePrincipalOrAdmin($principal);

        $campaigns = PromotionCampaign::query()
            ->whereIn('status', [PromotionCampaign::STATUS_APPROVED, PromotionCampaign::STATUS_EXECUTED])
            ->orderByDesc('id')
            ->get();

        if ($campaigns->isEmpty()) {
            throw new RuntimeException('No approved or executed promotion campaigns found to undo.');
        }

        $approvedUndone = 0;
        $executedUndone = 0;
        $studentsReverted = 0;
        $studentRowsReset = 0;

        DB::transaction(function () use (
            $campaigns,
            &$approvedUndone,
            &$executedUndone,
            &$studentsReverted,
            &$studentRowsReset
        ): void {
            foreach ($campaigns as $campaign) {
                if ($campaign->status === PromotionCampaign::STATUS_EXECUTED) {
                    $studentsReverted += $this->rollbackExecutedCampaignState($campaign);
                    $executedUndone++;
                } else {
                    $approvedUndone++;
                }

                $studentRowsReset += StudentPromotion::query()
                    ->where('promotion_campaign_id', (int) $campaign->id)
                    ->whereIn('final_status', [StudentPromotion::STATUS_APPROVED, StudentPromotion::STATUS_EXECUTED])
                    ->update([
                        'final_status' => StudentPromotion::STATUS_PENDING,
                    ]);

                $this->resetCampaignAfterUndo($campaign);
            }
        });

        return [
            'campaigns_undone' => $approvedUndone + $executedUndone,
            'approved_campaigns_undone' => $approvedUndone,
            'executed_campaigns_undone' => $executedUndone,
            'student_rows_reset' => $studentRowsReset,
            'students_reverted' => $studentsReverted,
        ];
    }

    public function resolveNextClassId(int $fromClassId): ?int
    {
        $mappings = ClassPromotionMapping::query()
            ->where('from_class_id', $fromClassId)
            ->pluck('to_class_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($mappings->count() > 1) {
            throw new RuntimeException('Multiple promotion mappings found for one class. Keep one mapping per class.');
        }

        $mappedNextClassId = $mappings->first();
        if ($mappedNextClassId !== null) {
            return (int) $mappedNextClassId;
        }

        $resolvedByFlow = $this->resolveNextClassIdFromConfiguredFlow($fromClassId);
        if ($resolvedByFlow !== null) {
            ClassPromotionMapping::query()->updateOrCreate(
                ['from_class_id' => $fromClassId],
                ['to_class_id' => $resolvedByFlow]
            );
        }

        return $resolvedByFlow;
    }

    private function rollbackExecutedCampaignState(PromotionCampaign $campaign): int
    {
        $rows = StudentPromotion::query()
            ->with('student:id,class_id,status,created_at')
            ->where('promotion_campaign_id', (int) $campaign->id)
            ->where('final_status', StudentPromotion::STATUS_EXECUTED)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $nextClassId = $this->resolveNextClassId((int) $campaign->class_id);
        $studentsReverted = 0;

        foreach ($rows as $row) {
            $student = $row->student;
            if (! $student) {
                continue;
            }

            $decision = $row->principal_decision ?? $row->teacher_decision;
            if ($decision === null) {
                throw new RuntimeException('Unable to undo executed campaign because one or more decisions are missing.');
            }

            $isPassedOut = $this->isTerminalPassedOutDecision(
                $decision,
                (bool) $row->is_passed,
                $nextClassId
            );

            $targetClassId = $row->to_class_id ?: $this->targetClassIdForDecision(
                $decision,
                (int) $row->from_class_id,
                $nextClassId
            );

            if (! $isPassedOut && $targetClassId === null) {
                throw new RuntimeException('Unable to undo executed campaign because target class mapping is missing.');
            }

            $this->rollbackClassHistoryForUndo(
                (int) $student->id,
                (int) $row->from_class_id,
                $targetClassId !== null ? (int) $targetClassId : null,
                (string) $campaign->from_session,
                (string) $campaign->to_session,
                $isPassedOut
            );

            $student->class_id = (int) $row->from_class_id;
            if ($isPassedOut) {
                $student->status = 'active';
            }
            $student->save();

            $studentsReverted++;
        }

        return $studentsReverted;
    }

    private function rollbackClassHistoryForUndo(
        int $studentId,
        int $fromClassId,
        ?int $toClassId,
        string $fromSession,
        string $toSession,
        bool $isPassedOut
    ): void {
        if (! $isPassedOut && $toClassId !== null) {
            StudentClassHistory::query()
                ->where('student_id', $studentId)
                ->where('class_id', $toClassId)
                ->where('session', $toSession)
                ->delete();
        }

        $fromHistory = StudentClassHistory::query()
            ->where('student_id', $studentId)
            ->where('class_id', $fromClassId)
            ->where('session', $fromSession)
            ->first();

        if (! $fromHistory) {
            return;
        }

        $fromHistory->status = StudentClassHistory::STATUS_ACTIVE;
        $fromHistory->left_on = null;
        $fromHistory->save();
    }

    /**
     * @return Collection<int, array{
     *   student_id:int,
     *   student_name:string,
     *   student_code:string,
     *   class_id:int,
     *   class_name:string,
     *   final_percentage:?float,
     *   final_grade:?string,
     *   is_passed:bool
     * }>
     */
    private function loadEligibleStudentsForClassAndSession(int $classId, string $fromSession): Collection
    {
        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'student_id', 'name', 'class_id']);

        if ($students->isEmpty()) {
            return collect();
        }

        $finalExamIds = Exam::query()
            ->where('class_id', $classId)
            ->where('session', $fromSession)
            ->where('exam_type', ExamType::FinalTerm->value)
            ->pluck('id');

        $marks = $finalExamIds->isNotEmpty()
            ? DB::table('marks')
                ->selectRaw('student_id, SUM(obtained_marks) as obtained_total, SUM(total_marks) as total_total')
                ->whereIn('exam_id', $finalExamIds)
                ->whereIn('student_id', $students->pluck('id'))
                ->groupBy('student_id')
                ->get()
                ->keyBy(fn ($row): int => (int) $row->student_id)
            : collect();

        return $students->map(function (Student $student) use ($marks): array {
            $markRow = $marks->get((int) $student->id);
            $obtainedTotal = (float) ($markRow->obtained_total ?? 0);
            $totalTotal = (float) ($markRow->total_total ?? 0);
            $percentage = $totalTotal > 0
                ? round(($obtainedTotal / $totalTotal) * 100, 2)
                : null;
            $grade = $percentage !== null
                ? $this->resultService->computeGrade($percentage)
                : null;

            return [
                'student_id' => (int) $student->id,
                'student_name' => (string) $student->name,
                'student_code' => (string) $student->student_id,
                'class_id' => (int) $student->class_id,
                'class_name' => trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? '')),
                'final_percentage' => $percentage,
                'final_grade' => $grade,
                'is_passed' => $percentage !== null && $percentage >= self::PASS_PERCENTAGE,
            ];
        })->values();
    }

    private function syncStudentPromotions(PromotionCampaign $campaign, bool $allowWithoutFinalExam = false): void
    {
        $finalExamIds = Exam::query()
            ->where('class_id', (int) $campaign->class_id)
            ->where('session', (string) $campaign->from_session)
            ->where('exam_type', ExamType::FinalTerm->value)
            ->pluck('id');

        if ($finalExamIds->isEmpty() && ! $allowWithoutFinalExam) {
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
        $marks = $finalExamIds->isNotEmpty()
            ? DB::table('marks')
                ->selectRaw('student_id, SUM(obtained_marks) as obtained_total, SUM(total_marks) as total_total')
                ->whereIn('exam_id', $finalExamIds)
                ->whereIn('student_id', $studentIds)
                ->groupBy('student_id')
                ->get()
                ->keyBy(fn ($row): int => (int) $row->student_id)
            : collect();

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

    private function findUserOrFail(int $userId): User
    {
        $user = User::query()->find($userId);
        if (! $user) {
            throw new RuntimeException('User account not found.');
        }

        return $user;
    }

    private function findCampaignOrFail(int $campaignId): PromotionCampaign
    {
        $campaign = PromotionCampaign::query()->find($campaignId);
        if (! $campaign) {
            throw new RuntimeException('Promotion campaign not found.');
        }

        return $campaign;
    }

    private function canSkipFinalExamRequirementForCampaign(PromotionCampaign $campaign): bool
    {
        $creator = User::query()->find((int) $campaign->created_by);

        return $creator?->hasAnyRole(['Admin', 'Principal']) ?? false;
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

    private function assertMappedDecisionForPrincipal(string $decision, ?int $nextClassId, bool $isPassed): void
    {
        if ($decision === StudentPromotion::DECISION_CONDITIONAL_PROMOTE && $nextClassId === null) {
            throw new RuntimeException('Terminal classes cannot use conditional promotion.');
        }

        if ($decision === StudentPromotion::DECISION_PROMOTE && $nextClassId === null && ! $isPassed) {
            throw new RuntimeException('Terminal class students can only be promoted when marked passed. Use retain for failed students.');
        }
    }

    private function resetCampaignApprovalState(PromotionCampaign $campaign): void
    {
        $campaign->status = PromotionCampaign::STATUS_DRAFT;
        $campaign->submitted_at = null;
        $campaign->approved_at = null;
        $campaign->approved_by = null;
        $campaign->principal_note = null;
        $campaign->save();
    }

    private function resetCampaignAfterUndo(PromotionCampaign $campaign): void
    {
        $campaign->status = PromotionCampaign::STATUS_DRAFT;
        $campaign->submitted_at = null;
        $campaign->approved_at = null;
        $campaign->approved_by = null;
        $campaign->executed_at = null;
        $campaign->principal_note = null;
        $campaign->save();
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

    private function resolveNextClassIdFromConfiguredFlow(int $fromClassId): ?int
    {
        $fromClass = SchoolClass::query()->find($fromClassId, ['id', 'name', 'section']);
        if (! $fromClass) {
            return null;
        }

        $fromStage = $this->classStageKey((string) $fromClass->name);
        if ($fromStage === null) {
            return null;
        }

        $nextStage = self::PROMOTION_STAGE_FLOW[$fromStage] ?? null;
        if ($nextStage === null) {
            return null;
        }

        $nextClasses = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section'])
            ->filter(fn (SchoolClass $classRoom): bool => $this->classStageKey((string) $classRoom->name) === $nextStage)
            ->values();

        if ($nextClasses->isEmpty()) {
            return null;
        }

        $sourceSection = $this->sectionKey($fromClass);
        if ($sourceSection !== null) {
            $sectionMatchedClass = $nextClasses->first(
                fn (SchoolClass $classRoom): bool => $this->sectionKey($classRoom) === $sourceSection
            );
            if ($sectionMatchedClass) {
                return (int) $sectionMatchedClass->id;
            }
        }

        return (int) $nextClasses->first()->id;
    }

    private function classStageKey(string $rawName): ?string
    {
        $normalized = strtolower(trim($rawName));
        if ($normalized === '') {
            return null;
        }

        if (preg_match('/\b(?:pg|play\s*group|playgroup)\b/i', $normalized) === 1) {
            return 'playgroup';
        }

        if (preg_match('/\bnursery\b/i', $normalized) === 1) {
            return 'nursery';
        }

        if (preg_match('/\bprep\b|\bpreparatory\b/i', $normalized) === 1) {
            return 'prep';
        }

        if (preg_match('/\b(?:class|grade)\s*(\d{1,2})\b/i', $normalized, $matches) === 1) {
            return $this->numericStageKey((int) $matches[1]);
        }

        if (preg_match('/^(\d{1,2})(?:\s*[- ]?\s*[a-z])?$/i', $normalized, $matches) === 1) {
            return $this->numericStageKey((int) $matches[1]);
        }

        if (preg_match('/\b(\d{1,2})\b/', $normalized, $matches) === 1) {
            return $this->numericStageKey((int) $matches[1]);
        }

        return null;
    }

    private function numericStageKey(int $value): ?string
    {
        if ($value < 1 || $value > 12) {
            return null;
        }

        return (string) $value;
    }

    private function sectionKey(SchoolClass $classRoom): ?string
    {
        $explicitSection = strtoupper(trim((string) ($classRoom->section ?? '')));
        if ($explicitSection !== '') {
            return $explicitSection;
        }

        $name = trim((string) $classRoom->name);
        if ($name === '') {
            return null;
        }

        if (preg_match('/(?:^|\b)(?:pg|play\s*group|playgroup|prep|nursery|class\s*\d{1,2}|grade\s*\d{1,2}|\d{1,2})\s*[- ]\s*([a-z])$/i', $name, $matches) === 1) {
            return strtoupper((string) $matches[1]);
        }

        if (preg_match('/^\s*\d{1,2}\s*([a-z])\s*$/i', $name, $matches) === 1) {
            return strtoupper((string) $matches[1]);
        }

        return null;
    }

    private function ensureTargetClassSectionForPromotion(int $fromClassId, int $toClassId): void
    {
        $fromClass = SchoolClass::query()->find($fromClassId, ['id', 'name', 'section']);

        $section = $fromClass ? $this->sectionKey($fromClass) : null;
        if ($section === null) {
            $section = 'A';
        }

        ClassSection::query()->firstOrCreate([
            'class_id' => $toClassId,
            'section_name' => $section,
        ]);
    }

    private function assertNoTargetSessionHistory(int $studentId, string $toSession, string $studentName): void
    {
        $existingHistory = StudentClassHistory::query()
            ->where('student_id', $studentId)
            ->where('session', $toSession)
            ->first();

        if ($existingHistory) {
            throw new RuntimeException(sprintf(
                'Execution blocked for %s because a class history row already exists for session %s.',
                $studentName !== '' ? $studentName : ('Student #'.$studentId),
                $toSession
            ));
        }
    }

    private function syncClassHistoryForExecution(
        Student $student,
        int $fromClassId,
        ?int $toClassId,
        string $fromSession,
        string $toSession,
        string $decision,
        bool $isPassedOut = false
    ): void {
        $fromStatus = match (true) {
            $isPassedOut => StudentClassHistory::STATUS_COMPLETED,
            $decision === StudentPromotion::DECISION_PROMOTE => StudentClassHistory::STATUS_PROMOTED,
            $decision === StudentPromotion::DECISION_CONDITIONAL_PROMOTE => StudentClassHistory::STATUS_CONDITIONAL_PROMOTED,
            $decision === StudentPromotion::DECISION_RETAIN => StudentClassHistory::STATUS_RETAINED,
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

        if ($toClassId === null || $isPassedOut) {
            return;
        }

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

    private function isTerminalPassedOutDecision(?string $decision, bool $isPassed, ?int $nextClassId): bool
    {
        return $decision === StudentPromotion::DECISION_PROMOTE
            && $isPassed
            && $nextClassId === null;
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
