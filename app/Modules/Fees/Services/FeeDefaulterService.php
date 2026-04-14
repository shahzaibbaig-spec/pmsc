<?php

namespace App\Modules\Fees\Services;

use App\Models\FeeBlockOverride;
use App\Models\FeeChallan;
use App\Models\FeeChallanItem;
use App\Models\FeeDefaulter;
use App\Models\FeeInstallment;
use App\Models\FeeReminder;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentArrear;
use App\Models\User;
use App\Notifications\FeeReminderNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FeeDefaulterService
{
    public function __construct(private readonly FeeManagementService $feeManagementService)
    {
    }

    public function sessionFromDate(?Carbon $date = null): string
    {
        $target = ($date?->copy() ?? now())->startOfDay();
        $startYear = $target->month >= 7 ? $target->year : ($target->year - 1);

        return $startYear.'-'.($startYear + 1);
    }

    /**
     * @return array{
     *   session:string,
     *   as_of:string,
     *   scanned:int,
     *   active:int,
     *   marked:int,
     *   cleared:int,
     *   updated:int
     * }
     */
    public function processSession(string $session, ?Carbon $asOf = null): array
    {
        $asOfDate = ($asOf?->copy() ?? now())->startOfDay();
        $resolvedSession = trim($session) !== '' ? trim($session) : $this->sessionFromDate($asOfDate);

        if (! $this->feeDefaulterTableExists()) {
            return [
                'session' => $resolvedSession,
                'as_of' => $asOfDate->toDateString(),
                'scanned' => 0,
                'active' => 0,
                'marked' => 0,
                'cleared' => 0,
                'updated' => 0,
            ];
        }

        $this->feeManagementService->processLateFees($asOfDate);

        [$dueByStudent, $oldestDueByStudent] = $this->buildDueMaps($resolvedSession, $asOfDate);

        $existing = FeeDefaulter::query()
            ->where('session', $resolvedSession)
            ->get()
            ->keyBy(fn (FeeDefaulter $record): int => (int) $record->student_id);

        $studentIds = collect(array_keys($dueByStudent))
            ->merge($existing->keys()->all())
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $marked = 0;
        $cleared = 0;
        $updated = 0;

        DB::transaction(function () use (
            $studentIds,
            $dueByStudent,
            $oldestDueByStudent,
            $existing,
            $resolvedSession,
            &$marked,
            &$cleared,
            &$updated
        ): void {
            foreach ($studentIds as $studentId) {
                $totalDue = round((float) ($dueByStudent[$studentId] ?? 0), 2);
                $oldestDueDate = $oldestDueByStudent[$studentId] ?? null;

                /** @var FeeDefaulter|null $record */
                $record = $existing->get($studentId);

                if ($totalDue > 0) {
                    if (! $record) {
                        FeeDefaulter::query()->create([
                            'student_id' => $studentId,
                            'session' => $resolvedSession,
                            'total_due' => $totalDue,
                            'oldest_due_date' => $oldestDueDate,
                            'is_active' => true,
                            'marked_at' => now(),
                            'cleared_at' => null,
                        ]);
                        $marked++;
                        continue;
                    }

                    $wasActive = (bool) $record->is_active && round((float) $record->total_due, 2) > 0;
                    $record->forceFill([
                        'total_due' => $totalDue,
                        'oldest_due_date' => $oldestDueDate,
                        'is_active' => true,
                        'marked_at' => $wasActive ? $record->marked_at : now(),
                        'cleared_at' => null,
                    ])->save();

                    if ($wasActive) {
                        $updated++;
                    } else {
                        $marked++;
                    }

                    continue;
                }

                if (! $record) {
                    continue;
                }

                $wasActive = (bool) $record->is_active && round((float) $record->total_due, 2) > 0;
                $record->forceFill([
                    'total_due' => 0,
                    'oldest_due_date' => null,
                    'is_active' => false,
                    'cleared_at' => $wasActive ? now() : $record->cleared_at,
                ])->save();

                if ($wasActive) {
                    $cleared++;
                } else {
                    $updated++;
                }
            }
        });

        return [
            'session' => $resolvedSession,
            'as_of' => $asOfDate->toDateString(),
            'scanned' => $studentIds->count(),
            'active' => FeeDefaulter::query()
                ->where('session', $resolvedSession)
                ->where('is_active', true)
                ->count(),
            'marked' => $marked,
            'cleared' => $cleared,
            'updated' => $updated,
        ];
    }

    public function syncStudentForSession(int $studentId, string $session, ?Carbon $asOf = null): ?FeeDefaulter
    {
        if (! $this->feeDefaulterTableExists()) {
            return null;
        }

        $resolvedSession = trim($session) !== '' ? trim($session) : $this->sessionFromDate($asOf);
        $asOfDate = ($asOf?->copy() ?? now())->startOfDay();

        $this->feeManagementService->processLateFees($asOfDate);

        $breakdown = $this->dueBreakdownForStudent($studentId, $resolvedSession, $asOfDate);
        $totalDue = round((float) ($breakdown['total_due'] ?? 0), 2);
        $oldestDueDate = $breakdown['oldest_due_date'] ?? null;

        $record = FeeDefaulter::query()->firstOrNew([
            'student_id' => $studentId,
            'session' => $resolvedSession,
        ]);

        $wasActive = $record->exists && (bool) $record->is_active && round((float) $record->total_due, 2) > 0;

        if ($totalDue > 0) {
            $record->forceFill([
                'total_due' => $totalDue,
                'oldest_due_date' => $oldestDueDate,
                'is_active' => true,
                'marked_at' => $wasActive ? $record->marked_at : now(),
                'cleared_at' => null,
            ])->save();

            return $record->fresh();
        }

        if (! $record->exists) {
            return null;
        }

        $record->forceFill([
            'total_due' => 0,
            'oldest_due_date' => null,
            'is_active' => false,
            'cleared_at' => $wasActive ? now() : $record->cleared_at,
        ])->save();

        return $record->fresh();
    }

    /**
     * @return array{
     *   challan_due:float,
     *   installment_due:float,
     *   arrears_due:float,
     *   total_due:float,
     *   oldest_due_date:string|null
     * }
     */
    public function dueBreakdownForStudent(int $studentId, string $session, ?Carbon $asOf = null): array
    {
        $asOfDate = ($asOf?->copy() ?? now())->startOfDay();
        $resolvedSession = trim($session) !== '' ? trim($session) : $this->sessionFromDate($asOfDate);

        $oldestDueDate = null;
        $challanDue = 0.0;
        $installmentDue = 0.0;
        $arrearsDue = 0.0;

        $overdueChallans = FeeChallan::query()
            ->withSum('payments as paid_total', 'amount_paid')
            ->where('student_id', $studentId)
            ->where('session', $resolvedSession)
            ->whereIn('status', $this->feeManagementService->pendingStatuses())
            ->whereDate('due_date', '<', $asOfDate->toDateString())
            ->get(['id', 'due_date', 'total_amount']);

        foreach ($overdueChallans as $challan) {
            $remaining = $this->lineRemainingAmount((float) $challan->total_amount, (float) ($challan->paid_total ?? 0));
            if ($remaining <= 0) {
                continue;
            }

            $challanDue = round($challanDue + $remaining, 2);
            $oldestDueDate = $this->olderDate($oldestDueDate, optional($challan->due_date)->toDateString());
        }

        if ($this->installmentTablesExist()) {
            $installments = FeeInstallment::query()
                ->where('student_id', $studentId)
                ->whereIn('status', [
                    FeeManagementService::INSTALLMENT_STATUS_PENDING,
                    FeeManagementService::INSTALLMENT_STATUS_PARTIAL,
                ])
                ->whereDate('due_date', '<', $asOfDate->toDateString())
                ->whereHas('plan', function ($query) use ($resolvedSession): void {
                    $query->where('is_active', true)->where('session', $resolvedSession);
                })
                ->get(['due_date', 'amount', 'paid_amount']);

            foreach ($installments as $installment) {
                $remaining = $this->lineRemainingAmount((float) $installment->amount, (float) $installment->paid_amount);
                if ($remaining <= 0) {
                    continue;
                }

                $installmentDue = round($installmentDue + $remaining, 2);
                $oldestDueDate = $this->olderDate($oldestDueDate, optional($installment->due_date)->toDateString());
            }
        }

        if ($this->arrearsTableExists()) {
            $linkedArrearIds = $this->linkedArrearIdsFromOverduePendingChallans(
                $resolvedSession,
                $asOfDate,
                $studentId
            );

            $arrears = StudentArrear::query()
                ->where('student_id', $studentId)
                ->whereIn('status', [
                    FeeManagementService::ARREAR_STATUS_PENDING,
                    FeeManagementService::ARREAR_STATUS_PARTIAL,
                ])
                ->when($linkedArrearIds->isNotEmpty(), function ($query) use ($linkedArrearIds): void {
                    $query->whereNotIn('id', $linkedArrearIds->all());
                })
                ->where(function ($query) use ($resolvedSession): void {
                    $query->whereNull('session')
                        ->orWhere('session', $resolvedSession);
                })
                ->get(['due_date', 'amount', 'paid_amount']);

            foreach ($arrears as $arrear) {
                $remaining = $this->lineRemainingAmount((float) $arrear->amount, (float) $arrear->paid_amount);
                if ($remaining <= 0) {
                    continue;
                }

                $arrearsDue = round($arrearsDue + $remaining, 2);
                $oldestDueDate = $this->olderDate($oldestDueDate, optional($arrear->due_date)->toDateString());
            }
        }

        return [
            'challan_due' => $challanDue,
            'installment_due' => $installmentDue,
            'arrears_due' => $arrearsDue,
            'total_due' => round($challanDue + $installmentDue + $arrearsDue, 2),
            'oldest_due_date' => $oldestDueDate,
        ];
    }

    public function isStudentBlocked(string $blockType, int $studentId, ?string $session = null): bool
    {
        if (! $this->feeDefaulterTableExists()) {
            return false;
        }

        if (! $this->isBlockEnabled($blockType)) {
            return false;
        }

        $resolvedSession = trim((string) $session) !== '' ? trim((string) $session) : $this->sessionFromDate();
        $this->syncStudentForSession($studentId, $resolvedSession);

        if ($this->hasAllowedOverride($studentId, $resolvedSession, $blockType)) {
            return false;
        }

        return FeeDefaulter::query()
            ->where('student_id', $studentId)
            ->where('session', $resolvedSession)
            ->where('is_active', true)
            ->where('total_due', '>', 0)
            ->exists();
    }

    /**
     * @return Collection<int, array{id:int,name:string,student_id:string,total_due:float}>
     */
    public function blockedStudentsForClass(int $classId, string $session, string $blockType): Collection
    {
        if (! $this->feeDefaulterTableExists() || ! $this->isBlockEnabled($blockType)) {
            return collect();
        }

        $rows = DB::table('fee_defaulters as fd')
            ->join('students as s', 's.id', '=', 'fd.student_id')
            ->leftJoin('fee_block_overrides as fbo', function ($join) use ($blockType): void {
                $join->on('fbo.student_id', '=', 'fd.student_id')
                    ->on('fbo.session', '=', 'fd.session')
                    ->where('fbo.block_type', '=', $blockType)
                    ->where('fbo.is_allowed', '=', 1);
            })
            ->where('fd.session', $session)
            ->where('fd.is_active', 1)
            ->where('fd.total_due', '>', 0)
            ->where('s.class_id', $classId)
            ->whereNull('s.deleted_at')
            ->whereNull('fbo.id')
            ->orderByDesc('fd.total_due')
            ->orderBy('s.name')
            ->get([
                's.id',
                's.name',
                's.student_id',
                'fd.total_due',
            ]);

        return collect($rows)->map(fn ($row): array => [
            'id' => (int) $row->id,
            'name' => (string) $row->name,
            'student_id' => (string) ($row->student_id ?? ''),
            'total_due' => round((float) $row->total_due, 2),
        ])->values();
    }

    public function upsertOverride(
        int $studentId,
        string $session,
        string $blockType,
        bool $isAllowed,
        ?string $reason,
        ?int $approvedBy
    ): FeeBlockOverride {
        if (! in_array($blockType, $this->blockTypes(), true)) {
            throw new RuntimeException('Invalid block type selected.');
        }

        return FeeBlockOverride::query()->updateOrCreate(
            [
                'student_id' => $studentId,
                'session' => trim($session),
                'block_type' => $blockType,
            ],
            [
                'is_allowed' => $isAllowed,
                'reason' => trim((string) $reason) !== '' ? trim((string) $reason) : null,
                'approved_by' => $approvedBy ?: null,
            ]
        );
    }

    /**
     * @return array{reminder:FeeReminder,notified_users:int}
     */
    public function sendInAppReminder(
        int $studentId,
        string $session,
        ?int $sentBy,
        ?int $challanId = null,
        ?string $title = null,
        ?string $message = null
    ): array {
        if (! $this->feeReminderTableExists()) {
            throw new RuntimeException('Fee reminders table is missing. Please run migrations.');
        }

        $student = Student::query()
            ->with('classRoom:id,name,section')
            ->find($studentId, ['id', 'name', 'student_id', 'class_id']);

        if (! $student) {
            throw new RuntimeException('Student not found.');
        }

        $resolvedSession = trim($session) !== '' ? trim($session) : $this->sessionFromDate();
        $defaulter = $this->syncStudentForSession((int) $student->id, $resolvedSession);
        $totalDue = round((float) ($defaulter?->total_due ?? 0), 2);

        $notificationTitle = trim((string) $title) !== ''
            ? trim((string) $title)
            : 'Fee Reminder';

        $defaultMessage = sprintf(
            'Fee reminder for %s (%s). Outstanding dues: PKR %s for session %s.',
            $student->name,
            $student->student_id ?: 'N/A',
            number_format($totalDue, 2),
            $resolvedSession
        );

        $notificationMessage = trim((string) $message) !== ''
            ? trim((string) $message)
            : $defaultMessage;

        $payload = [
            'student_id' => (int) $student->id,
            'student_name' => (string) $student->name,
            'student_code' => (string) ($student->student_id ?? ''),
            'session' => $resolvedSession,
            'total_due' => $totalDue,
            'title' => $notificationTitle,
            'message' => $notificationMessage,
            'url' => route('principal.fees.defaulters.index', [
                'session' => $resolvedSession,
                'search' => $student->student_id ?: $student->name,
            ]),
        ];

        $recipients = User::role(['Admin', 'Principal', 'Accountant'])
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhere('status', 'active');
            })
            ->get(['id', 'name', 'email']);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new FeeReminderNotification($payload));
        }

        $reminder = FeeReminder::query()->create([
            'student_id' => (int) $student->id,
            'challan_id' => $challanId,
            'session' => $resolvedSession,
            'channel' => 'in_app',
            'title' => $notificationTitle,
            'message' => $notificationMessage,
            'sent_by' => $sentBy ?: null,
            'sent_at' => now(),
        ]);

        return [
            'reminder' => $reminder,
            'notified_users' => $recipients->count(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function blockTypes(): array
    {
        return [
            FeeBlockOverride::TYPE_RESULT_CARD,
            FeeBlockOverride::TYPE_ADMIT_CARD,
            FeeBlockOverride::TYPE_ID_CARD,
        ];
    }

    private function hasAllowedOverride(int $studentId, string $session, string $blockType): bool
    {
        if (! $this->feeBlockOverridesTableExists()) {
            return false;
        }

        return FeeBlockOverride::query()
            ->where('student_id', $studentId)
            ->where('session', $session)
            ->where('block_type', $blockType)
            ->where('is_allowed', true)
            ->exists();
    }

    private function isBlockEnabled(string $blockType): bool
    {
        $settings = SchoolSetting::cached();
        if (! $settings) {
            return false;
        }

        return match ($blockType) {
            FeeBlockOverride::TYPE_RESULT_CARD => (bool) $settings->block_results_for_defaulters,
            FeeBlockOverride::TYPE_ADMIT_CARD => (bool) $settings->block_admit_card_for_defaulters,
            FeeBlockOverride::TYPE_ID_CARD => (bool) $settings->block_id_card_for_defaulters,
            default => false,
        };
    }

    /**
     * @return array{0: array<int, float>, 1: array<int, string>}
     */
    private function buildDueMaps(string $session, Carbon $asOfDate): array
    {
        $dueByStudent = [];
        $oldestDueByStudent = [];

        $overdueChallans = FeeChallan::query()
            ->withSum('payments as paid_total', 'amount_paid')
            ->where('session', $session)
            ->whereIn('status', $this->feeManagementService->pendingStatuses())
            ->whereDate('due_date', '<', $asOfDate->toDateString())
            ->whereHas('student', function ($query): void {
                $query->where('status', 'active');
            })
            ->get(['id', 'student_id', 'due_date', 'total_amount']);

        foreach ($overdueChallans as $challan) {
            $remaining = $this->lineRemainingAmount((float) $challan->total_amount, (float) ($challan->paid_total ?? 0));
            if ($remaining <= 0) {
                continue;
            }

            $studentId = (int) $challan->student_id;
            $this->trackDue(
                $dueByStudent,
                $oldestDueByStudent,
                $studentId,
                $remaining,
                optional($challan->due_date)->toDateString()
            );
        }

        if ($this->installmentTablesExist()) {
            $overdueInstallments = FeeInstallment::query()
                ->whereIn('status', [
                    FeeManagementService::INSTALLMENT_STATUS_PENDING,
                    FeeManagementService::INSTALLMENT_STATUS_PARTIAL,
                ])
                ->whereDate('due_date', '<', $asOfDate->toDateString())
                ->whereHas('student', function ($query): void {
                    $query->where('status', 'active');
                })
                ->whereHas('plan', function ($query) use ($session): void {
                    $query->where('session', $session)
                        ->where('is_active', true);
                })
                ->get(['student_id', 'due_date', 'amount', 'paid_amount']);

            foreach ($overdueInstallments as $installment) {
                $remaining = $this->lineRemainingAmount((float) $installment->amount, (float) $installment->paid_amount);
                if ($remaining <= 0) {
                    continue;
                }

                $studentId = (int) $installment->student_id;
                $this->trackDue(
                    $dueByStudent,
                    $oldestDueByStudent,
                    $studentId,
                    $remaining,
                    optional($installment->due_date)->toDateString()
                );
            }
        }

        if ($this->arrearsTableExists()) {
            $linkedArrearIds = $this->linkedArrearIdsFromOverduePendingChallans($session, $asOfDate);

            $arrears = StudentArrear::query()
                ->whereIn('status', [
                    FeeManagementService::ARREAR_STATUS_PENDING,
                    FeeManagementService::ARREAR_STATUS_PARTIAL,
                ])
                ->when($linkedArrearIds->isNotEmpty(), function ($query) use ($linkedArrearIds): void {
                    $query->whereNotIn('id', $linkedArrearIds->all());
                })
                ->where(function ($query) use ($session): void {
                    $query->whereNull('session')
                        ->orWhere('session', $session);
                })
                ->whereHas('student', function ($query): void {
                    $query->where('status', 'active');
                })
                ->get(['student_id', 'due_date', 'amount', 'paid_amount']);

            foreach ($arrears as $arrear) {
                $remaining = $this->lineRemainingAmount((float) $arrear->amount, (float) $arrear->paid_amount);
                if ($remaining <= 0) {
                    continue;
                }

                $studentId = (int) $arrear->student_id;
                $this->trackDue(
                    $dueByStudent,
                    $oldestDueByStudent,
                    $studentId,
                    $remaining,
                    optional($arrear->due_date)->toDateString()
                );
            }
        }

        return [$dueByStudent, $oldestDueByStudent];
    }

    /**
     * @param array<int, float> $dueByStudent
     * @param array<int, string> $oldestDueByStudent
     */
    private function trackDue(
        array &$dueByStudent,
        array &$oldestDueByStudent,
        int $studentId,
        float $amount,
        ?string $dueDate
    ): void {
        if ($amount <= 0) {
            return;
        }

        $dueByStudent[$studentId] = round((float) ($dueByStudent[$studentId] ?? 0) + $amount, 2);

        if ($dueDate === null || trim($dueDate) === '') {
            return;
        }

        $oldestDueByStudent[$studentId] = $this->olderDate(
            $oldestDueByStudent[$studentId] ?? null,
            $dueDate
        ) ?? $dueDate;
    }

    private function olderDate(?string $left, ?string $right): ?string
    {
        $l = trim((string) $left);
        $r = trim((string) $right);

        if ($l === '' && $r === '') {
            return null;
        }

        if ($l === '') {
            return $r;
        }

        if ($r === '') {
            return $l;
        }

        return $l <= $r ? $l : $r;
    }

    private function lineRemainingAmount(float $totalAmount, float $paidAmount): float
    {
        return round(max(round($totalAmount, 2) - round($paidAmount, 2), 0), 2);
    }

    private function feeDefaulterTableExists(): bool
    {
        return Schema::hasTable('fee_defaulters');
    }

    private function feeReminderTableExists(): bool
    {
        return Schema::hasTable('fee_reminders');
    }

    private function feeBlockOverridesTableExists(): bool
    {
        return Schema::hasTable('fee_block_overrides');
    }

    private function installmentTablesExist(): bool
    {
        return Schema::hasTable('fee_installment_plans') && Schema::hasTable('fee_installments');
    }

    private function arrearsTableExists(): bool
    {
        return Schema::hasTable('student_arrears');
    }

    private function challanItemLinkColumnsExist(): bool
    {
        if (! Schema::hasTable('fee_challan_items')) {
            return false;
        }

        return Schema::hasColumn('fee_challan_items', 'student_arrear_id');
    }

    /**
     * @return Collection<int, int>
     */
    private function linkedArrearIdsFromOverduePendingChallans(
        string $session,
        Carbon $asOfDate,
        ?int $studentId = null
    ): Collection {
        if (! $this->challanItemLinkColumnsExist()) {
            return collect();
        }

        return FeeChallanItem::query()
            ->join('fee_challans', 'fee_challans.id', '=', 'fee_challan_items.fee_challan_id')
            ->where('fee_challans.session', $session)
            ->whereIn('fee_challans.status', $this->feeManagementService->pendingStatuses())
            ->whereDate('fee_challans.due_date', '<', $asOfDate->toDateString())
            ->when($studentId !== null, function ($query) use ($studentId): void {
                $query->where('fee_challans.student_id', $studentId);
            })
            ->whereNotNull('fee_challan_items.student_arrear_id')
            ->pluck('fee_challan_items.student_arrear_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();
    }
}
