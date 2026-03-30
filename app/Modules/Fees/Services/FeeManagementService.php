<?php

namespace App\Modules\Fees\Services;

use App\Models\FeeChallan;
use App\Models\FeeChallanItem;
use App\Models\FeeInstallment;
use App\Models\FeeInstallmentPlan;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentArrear;
use App\Models\StudentFeeAssignment;
use App\Models\StudentFeeStructure;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class FeeManagementService
{
    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STUDENT_CUSTOM_FEE_TUITION = 'tuition_fee';

    public const STUDENT_CUSTOM_FEE_COMPUTER = 'computer_fee';

    public const STUDENT_CUSTOM_FEE_EXAM = 'exam_fee';

    public const INSTALLMENT_STATUS_PENDING = 'pending';

    public const INSTALLMENT_STATUS_PARTIAL = 'partial';

    public const INSTALLMENT_STATUS_PAID = 'paid';

    public const ARREAR_STATUS_PENDING = 'pending';

    public const ARREAR_STATUS_PARTIAL = 'partial';

    public const ARREAR_STATUS_PAID = 'paid';

    public function sessionOptions(int $backward = 1, int $forward = 3): array
    {
        $now = now();
        $currentStartYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        $sessions = [];
        for ($year = $currentStartYear - $backward; $year <= $currentStartYear + $forward; $year++) {
            $sessions[] = $year.'-'.($year + 1);
        }

        return $sessions;
    }

    public function monthLabel(string $month): string
    {
        try {
            return Carbon::createFromFormat('Y-m', $month)->format('F Y');
        } catch (\Throwable) {
            return $month;
        }
    }

    public function pendingStatuses(): array
    {
        return [
            self::STATUS_UNPAID,
            self::STATUS_PARTIAL,
            'partially_paid',
        ];
    }

    public function normalizeStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            self::STATUS_PAID => self::STATUS_PAID,
            self::STATUS_PARTIAL, 'partially_paid' => self::STATUS_PARTIAL,
            default => self::STATUS_UNPAID,
        };
    }

    public function normalizeInstallmentStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            self::INSTALLMENT_STATUS_PAID => self::INSTALLMENT_STATUS_PAID,
            self::INSTALLMENT_STATUS_PARTIAL, self::STATUS_PARTIAL => self::INSTALLMENT_STATUS_PARTIAL,
            self::STATUS_UNPAID => self::INSTALLMENT_STATUS_PENDING,
            default => self::INSTALLMENT_STATUS_PENDING,
        };
    }

    public function normalizeArrearStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            self::ARREAR_STATUS_PAID => self::ARREAR_STATUS_PAID,
            self::ARREAR_STATUS_PARTIAL, self::STATUS_PARTIAL => self::ARREAR_STATUS_PARTIAL,
            self::STATUS_UNPAID => self::ARREAR_STATUS_PENDING,
            default => self::ARREAR_STATUS_PENDING,
        };
    }

    public function lateFeeAmount(): float
    {
        return round(max((float) config('fees.late_fee_amount', 200), 0), 2);
    }

    /**
     * @param array{
     *   session:string,
     *   class_id:int,
     *   title:string,
     *   amount:float|int|string,
     *   fee_type:string,
     *   is_monthly:bool,
     *   is_active:bool
     * } $attributes
     */
    public function createFeeStructure(array $attributes, ?int $createdBy): FeeStructure
    {
        $resolvedCreatedBy = $this->resolveUserId($createdBy);

        return $this->executeWithUserForeignKeyFallback(
            $resolvedCreatedBy,
            function (?int $safeCreatedBy) use ($attributes): FeeStructure {
                return FeeStructure::query()->create([
                    'session' => (string) $attributes['session'],
                    'class_id' => (int) $attributes['class_id'],
                    'title' => trim((string) $attributes['title']),
                    'amount' => round((float) $attributes['amount'], 2),
                    'fee_type' => trim((string) $attributes['fee_type']),
                    'is_monthly' => (bool) $attributes['is_monthly'],
                    'is_active' => (bool) $attributes['is_active'],
                    'created_by' => $safeCreatedBy,
                ]);
            }
        );
    }

    /**
     * @param array{
     *   student_id:int,
     *   session:string,
     *   tuition_fee:float|int|string,
     *   computer_fee:float|int|string,
     *   exam_fee:float|int|string,
     *   is_active?:bool
     * } $attributes
     */
    public function upsertStudentCustomFee(array $attributes, ?int $createdBy): StudentFeeStructure
    {
        if (! $this->studentCustomFeeTableExists()) {
            throw new RuntimeException('Student custom fee table is missing. Please run migrations.');
        }

        $resolvedCreatedBy = $this->resolveUserId($createdBy);

        return $this->executeWithUserForeignKeyFallback(
            $resolvedCreatedBy,
            function (?int $safeCreatedBy) use ($attributes): StudentFeeStructure {
                return DB::transaction(function () use ($attributes, $safeCreatedBy): StudentFeeStructure {
                    $record = StudentFeeStructure::query()->firstOrNew([
                        'student_id' => (int) $attributes['student_id'],
                        'session' => (string) $attributes['session'],
                    ]);

                    if (! $record->exists) {
                        $record->created_by = $safeCreatedBy;
                    } elseif ($record->created_by === null && $safeCreatedBy !== null) {
                        $record->created_by = $safeCreatedBy;
                    }

                    $record->forceFill([
                        'tuition_fee' => round((float) $attributes['tuition_fee'], 2),
                        'computer_fee' => round((float) $attributes['computer_fee'], 2),
                        'exam_fee' => round((float) $attributes['exam_fee'], 2),
                        'is_active' => (bool) ($attributes['is_active'] ?? true),
                    ])->save();

                    return $record->fresh() ?? $record;
                });
            }
        );
    }

    /**
     * @return array<int, string>
     */
    public function studentCustomFeeFields(): array
    {
        return [
            self::STUDENT_CUSTOM_FEE_TUITION,
            self::STUDENT_CUSTOM_FEE_COMPUTER,
            self::STUDENT_CUSTOM_FEE_EXAM,
        ];
    }

    /**
     * @param Collection<int, FeeStructure> $structures
     * @return array{tuition_fee:float,computer_fee:float,exam_fee:float}
     */
    public function customFeeBreakdownFromStructures(Collection $structures): array
    {
        $breakdown = [
            self::STUDENT_CUSTOM_FEE_TUITION => 0.0,
            self::STUDENT_CUSTOM_FEE_COMPUTER => 0.0,
            self::STUDENT_CUSTOM_FEE_EXAM => 0.0,
        ];

        foreach ($structures as $structure) {
            $field = $this->customFeeFieldFromFeeHead((string) $structure->fee_type, (string) $structure->title);
            if ($field === null) {
                continue;
            }

            $breakdown[$field] = round($breakdown[$field] + (float) $structure->amount, 2);
        }

        return $breakdown;
    }

    public function customFeeFieldFromFeeHead(?string $feeType, ?string $title = null): ?string
    {
        $normalizedFeeType = strtolower(trim((string) $feeType));
        $normalizedTitle = strtolower(trim((string) $title));

        if (
            $this->matchesCustomFeeKeyword($normalizedFeeType, ['tuition'])
            || $this->matchesCustomFeeKeyword($normalizedTitle, ['tuition'])
        ) {
            return self::STUDENT_CUSTOM_FEE_TUITION;
        }

        if (
            $this->matchesCustomFeeKeyword($normalizedFeeType, ['computer'])
            || $this->matchesCustomFeeKeyword($normalizedTitle, ['computer'])
        ) {
            return self::STUDENT_CUSTOM_FEE_COMPUTER;
        }

        if (
            $this->matchesCustomFeeKeyword($normalizedFeeType, ['exam', 'examination'])
            || $this->matchesCustomFeeKeyword($normalizedTitle, ['exam', 'examination'])
        ) {
            return self::STUDENT_CUSTOM_FEE_EXAM;
        }

        return null;
    }

    public function customFeeTotal(float|int|string $tuitionFee, float|int|string $computerFee, float|int|string $examFee): float
    {
        return round((float) $tuitionFee + (float) $computerFee + (float) $examFee, 2);
    }

    /**
     * @return array<int, string>
     */
    public function installmentPlanTypes(): array
    {
        return [
            FeeInstallmentPlan::TYPE_MONTHLY,
            FeeInstallmentPlan::TYPE_QUARTERLY,
            FeeInstallmentPlan::TYPE_CUSTOM,
        ];
    }

    /**
     * @param array{
     *   student_id:int,
     *   session:string,
     *   plan_name?:string|null,
     *   plan_type:string,
     *   total_amount:float|int|string,
     *   number_of_installments:int,
     *   first_due_date:string,
     *   custom_interval_days?:int|null,
     *   notes?:string|null,
     *   is_active?:bool,
     *   deactivate_existing?:bool
     * } $attributes
     */
    public function createInstallmentPlan(array $attributes, ?int $createdBy): FeeInstallmentPlan
    {
        if (! $this->installmentPlanTablesExist()) {
            throw new RuntimeException('Installment tables are missing. Please run migrations.');
        }

        $planType = strtolower(trim((string) $attributes['plan_type']));
        if (! in_array($planType, $this->installmentPlanTypes(), true)) {
            throw new RuntimeException('Invalid installment plan type.');
        }

        $totalAmount = round((float) $attributes['total_amount'], 2);
        if ($totalAmount <= 0) {
            throw new RuntimeException('Total amount must be greater than zero.');
        }

        $installmentCount = max((int) $attributes['number_of_installments'], 1);
        $customIntervalDays = isset($attributes['custom_interval_days'])
            ? (int) $attributes['custom_interval_days']
            : null;

        if ($planType === FeeInstallmentPlan::TYPE_CUSTOM && ($customIntervalDays === null || $customIntervalDays <= 0)) {
            throw new RuntimeException('Custom plan requires an interval in days.');
        }

        $resolvedCreatedBy = $this->resolveUserId($createdBy);

        return $this->executeWithUserForeignKeyFallback(
            $resolvedCreatedBy,
            function (?int $safeCreatedBy) use (
                $attributes,
                $planType,
                $totalAmount,
                $installmentCount,
                $customIntervalDays
            ): FeeInstallmentPlan {
                return DB::transaction(function () use (
                    $attributes,
                    $safeCreatedBy,
                    $planType,
                    $totalAmount,
                    $installmentCount,
                    $customIntervalDays
                ): FeeInstallmentPlan {
                    $studentId = (int) $attributes['student_id'];
                    $session = (string) $attributes['session'];
                    $deactivateExisting = (bool) ($attributes['deactivate_existing'] ?? true);

                    if ($deactivateExisting) {
                        FeeInstallmentPlan::query()
                            ->where('student_id', $studentId)
                            ->where('session', $session)
                            ->where('is_active', true)
                            ->update(['is_active' => false]);
                    }

                    $plan = FeeInstallmentPlan::query()->create([
                        'student_id' => $studentId,
                        'session' => $session,
                        'plan_name' => trim((string) ($attributes['plan_name'] ?? '')) ?: null,
                        'plan_type' => $planType,
                        'total_amount' => $totalAmount,
                        'number_of_installments' => $installmentCount,
                        'first_due_date' => (string) $attributes['first_due_date'],
                        'custom_interval_days' => $planType === FeeInstallmentPlan::TYPE_CUSTOM ? $customIntervalDays : null,
                        'is_active' => (bool) ($attributes['is_active'] ?? true),
                        'notes' => trim((string) ($attributes['notes'] ?? '')) ?: null,
                        'created_by' => $safeCreatedBy,
                    ]);

                    $this->generateInstallmentsForPlan($plan);

                    return $plan->load('installments');
                });
            }
        );
    }

    /**
     * @param array{
     *   student_id:int,
     *   session?:string|null,
     *   title:string,
     *   amount:float|int|string,
     *   due_date?:string|null,
     *   notes?:string|null
     * } $attributes
     */
    public function addStudentArrear(array $attributes, ?int $addedBy): StudentArrear
    {
        if (! $this->studentArrearTableExists()) {
            throw new RuntimeException('Student arrears table is missing. Please run migrations.');
        }

        $amount = round((float) $attributes['amount'], 2);
        if ($amount <= 0) {
            throw new RuntimeException('Arrear amount must be greater than zero.');
        }

        $resolvedAddedBy = $this->resolveUserId($addedBy);

        return $this->executeWithUserForeignKeyFallback(
            $resolvedAddedBy,
            function (?int $safeAddedBy) use ($attributes, $amount): StudentArrear {
                return StudentArrear::query()->create([
                    'student_id' => (int) $attributes['student_id'],
                    'session' => trim((string) ($attributes['session'] ?? '')) ?: null,
                    'title' => trim((string) $attributes['title']),
                    'amount' => $amount,
                    'paid_amount' => 0,
                    'status' => self::ARREAR_STATUS_PENDING,
                    'due_date' => trim((string) ($attributes['due_date'] ?? '')) ?: null,
                    'notes' => trim((string) ($attributes['notes'] ?? '')) ?: null,
                    'added_by' => $safeAddedBy,
                    'resolved_at' => null,
                ]);
            }
        );
    }

    public function recordInstallmentPayment(FeeInstallment $installment, float $amountPaid): FeeInstallment
    {
        if (! $this->installmentPlanTablesExist()) {
            throw new RuntimeException('Installment tables are missing. Please run migrations.');
        }

        if ($amountPaid <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($installment, $amountPaid): FeeInstallment {
            $locked = FeeInstallment::query()->lockForUpdate()->find($installment->id);
            if (! $locked) {
                throw new RuntimeException('Installment not found.');
            }

            $remaining = $this->lineRemainingAmount((float) $locked->amount, (float) $locked->paid_amount);
            if ($remaining <= 0) {
                throw new RuntimeException('Installment is already fully paid.');
            }

            if ($amountPaid > $remaining) {
                throw new RuntimeException('Payment amount cannot exceed remaining installment balance.');
            }

            $newPaid = round((float) $locked->paid_amount + $amountPaid, 2);
            $status = $this->statusFromPaidAndTotal($newPaid, (float) $locked->amount);

            $locked->forceFill([
                'paid_amount' => $newPaid,
                'status' => $this->normalizeInstallmentStatus($status),
                'paid_at' => $status === self::INSTALLMENT_STATUS_PAID ? now() : null,
            ])->save();

            return $locked->fresh() ?? $locked;
        });
    }

    public function recordArrearPayment(StudentArrear $arrear, float $amountPaid): StudentArrear
    {
        if (! $this->studentArrearTableExists()) {
            throw new RuntimeException('Student arrears table is missing. Please run migrations.');
        }

        if ($amountPaid <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($arrear, $amountPaid): StudentArrear {
            $locked = StudentArrear::query()->lockForUpdate()->find($arrear->id);
            if (! $locked) {
                throw new RuntimeException('Arrear not found.');
            }

            $remaining = $this->lineRemainingAmount((float) $locked->amount, (float) $locked->paid_amount);
            if ($remaining <= 0) {
                throw new RuntimeException('Arrear is already fully paid.');
            }

            if ($amountPaid > $remaining) {
                throw new RuntimeException('Payment amount cannot exceed remaining arrear balance.');
            }

            $newPaid = round((float) $locked->paid_amount + $amountPaid, 2);
            $status = $this->statusFromPaidAndTotal($newPaid, (float) $locked->amount);

            $locked->forceFill([
                'paid_amount' => $newPaid,
                'status' => $this->normalizeArrearStatus($status),
                'resolved_at' => $status === self::ARREAR_STATUS_PAID ? now() : null,
            ])->save();

            return $locked->fresh() ?? $locked;
        });
    }

    /**
     * @return array{installment_due:float,arrears_due:float,total_due:float}
     */
    public function dueSummaryForStudent(int $studentId, ?string $session = null): array
    {
        $installmentDue = 0.0;
        if ($this->installmentPlanTablesExist()) {
            $installments = FeeInstallment::query()
                ->where('student_id', $studentId)
                ->whereIn('status', [
                    self::INSTALLMENT_STATUS_PENDING,
                    self::INSTALLMENT_STATUS_PARTIAL,
                ])
                ->whereHas('plan', function ($query) use ($session): void {
                    $query->where('is_active', true);

                    if ($session !== null && $session !== '') {
                        $query->where('session', $session);
                    }
                })
                ->get(['amount', 'paid_amount']);

            $installmentDue = round((float) $installments->sum(function (FeeInstallment $installment): float {
                return $this->lineRemainingAmount((float) $installment->amount, (float) $installment->paid_amount);
            }), 2);
        }

        $arrearsDue = 0.0;
        if ($this->studentArrearTableExists()) {
            $arrears = StudentArrear::query()
                ->where('student_id', $studentId)
                ->whereIn('status', [
                    self::ARREAR_STATUS_PENDING,
                    self::ARREAR_STATUS_PARTIAL,
                ])
                ->when($session !== null && $session !== '', function ($query) use ($session): void {
                    $query->where(function ($nested) use ($session): void {
                        $nested->whereNull('session')
                            ->orWhere('session', $session);
                    });
                })
                ->get(['amount', 'paid_amount']);

            $arrearsDue = round((float) $arrears->sum(function (StudentArrear $arrear): float {
                return $this->lineRemainingAmount((float) $arrear->amount, (float) $arrear->paid_amount);
            }), 2);
        }

        return [
            'installment_due' => $installmentDue,
            'arrears_due' => $arrearsDue,
            'total_due' => round($installmentDue + $arrearsDue, 2),
        ];
    }

    /**
     * @return Collection<int, FeeInstallment>
     */
    public function installmentScheduleForStudent(int $studentId): Collection
    {
        if (! $this->installmentPlanTablesExist()) {
            return collect();
        }

        return FeeInstallment::query()
            ->with('plan:id,session,plan_name,plan_type,is_active')
            ->where('student_id', $studentId)
            ->orderBy('due_date')
            ->orderBy('installment_no')
            ->get([
                'id',
                'fee_installment_plan_id',
                'student_id',
                'installment_no',
                'title',
                'due_date',
                'amount',
                'paid_amount',
                'status',
                'paid_at',
            ]);
    }

    /**
     * @return Collection<int, StudentArrear>
     */
    public function arrearsForStudent(int $studentId): Collection
    {
        if (! $this->studentArrearTableExists()) {
            return collect();
        }

        return StudentArrear::query()
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get([
                'id',
                'student_id',
                'session',
                'title',
                'amount',
                'paid_amount',
                'status',
                'due_date',
                'notes',
                'resolved_at',
            ]);
    }

    public function graceDays(): int
    {
        return max((int) config('fees.grace_days', 3), 0);
    }

    public function processLateFees(?Carbon $asOf = null): array
    {
        $asOfDate = ($asOf?->copy() ?? now())->startOfDay();
        $lateFeeAmount = $this->lateFeeAmount();
        $graceDays = $this->graceDays();

        if ($lateFeeAmount <= 0) {
            return [
                'as_of' => $asOfDate->toDateString(),
                'scanned' => 0,
                'applied' => 0,
                'skipped' => 0,
                'late_fee_amount' => $lateFeeAmount,
                'grace_days' => $graceDays,
            ];
        }

        $lastAllowedDueDate = $asOfDate->copy()->subDays($graceDays)->toDateString();
        $candidates = FeeChallan::query()
            ->withSum('payments as paid_amount', 'amount_paid')
            ->whereIn('status', $this->pendingStatuses())
            ->whereDate('due_date', '<=', $lastAllowedDueDate)
            ->where(function ($query): void {
                $query->whereNull('late_fee')
                    ->orWhere('late_fee', '<=', 0);
            })
            ->whereNull('late_fee_waived_at')
            ->orderBy('id')
            ->get([
                'id',
                'due_date',
                'status',
                'total_amount',
                'late_fee',
                'late_fee_waived_at',
            ]);

        $applied = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $candidates,
            $asOfDate,
            $graceDays,
            $lateFeeAmount,
            &$applied,
            &$skipped
        ): void {
            foreach ($candidates as $candidate) {
                $locked = FeeChallan::query()
                    ->withSum('payments as paid_amount', 'amount_paid')
                    ->lockForUpdate()
                    ->find($candidate->id, [
                        'id',
                        'status',
                        'due_date',
                        'total_amount',
                        'late_fee',
                        'late_fee_waived_at',
                    ]);

                if (! $locked) {
                    $skipped++;
                    continue;
                }

                $paidAmount = (float) ($locked->paid_amount ?? 0);
                if (! $this->isEligibleForLateFee($locked, $paidAmount, $asOfDate, $graceDays, $lateFeeAmount)) {
                    $skipped++;
                    continue;
                }

                $previousLateFee = round((float) ($locked->late_fee ?? 0), 2);
                $newLateFee = round($previousLateFee + $lateFeeAmount, 2);
                $newTotal = round((float) $locked->total_amount + $lateFeeAmount, 2);

                $locked->forceFill([
                    'late_fee' => $newLateFee,
                    'total_amount' => $newTotal,
                    'status' => $this->normalizeStatus($locked->status),
                ])->save();

                Log::info('Late fee applied to challan.', [
                    'challan_id' => $locked->id,
                    'due_date' => optional($locked->due_date)->toDateString(),
                    'late_fee_added' => $lateFeeAmount,
                    'late_fee_before' => $previousLateFee,
                    'late_fee_after' => $newLateFee,
                    'total_amount_after' => $newTotal,
                    'as_of' => $asOfDate->toDateString(),
                ]);

                $applied++;
            }
        });

        return [
            'as_of' => $asOfDate->toDateString(),
            'scanned' => $candidates->count(),
            'applied' => $applied,
            'skipped' => $skipped,
            'late_fee_amount' => $lateFeeAmount,
            'grace_days' => $graceDays,
        ];
    }

    public function generateClassChallans(
        string $session,
        int $classId,
        string $month,
        string $dueDate,
        int $generatedBy
    ): array {
        // Keep overdue balances current before computing arrears for the new run.
        $this->processLateFees();

        $classRoom = SchoolClass::query()->find($classId, ['id', 'name', 'section']);
        if (! $classRoom) {
            throw new RuntimeException('Class not found.');
        }

        $students = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'student_id']);

        if ($students->isEmpty()) {
            throw new RuntimeException('No active students found for selected class.');
        }

        $studentIds = $students->pluck('id');
        $activePlansByStudent = collect();
        if ($this->installmentPlanTablesExist()) {
            $activePlansByStudent = FeeInstallmentPlan::query()
                ->where('session', $session)
                ->where('is_active', true)
                ->whereIn('student_id', $studentIds)
                ->orderByDesc('id')
                ->get([
                    'id',
                    'student_id',
                    'session',
                    'plan_type',
                    'is_active',
                ])
                ->groupBy('student_id')
                ->map(fn (Collection $rows): ?FeeInstallmentPlan => $rows->first());
        }

        $monthEndDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        $installmentsByPlan = collect();
        $planIds = $activePlansByStudent->pluck('id')->filter()->values();
        if ($planIds->isNotEmpty() && $this->installmentPlanTablesExist()) {
            $installmentsByPlan = FeeInstallment::query()
                ->whereIn('fee_installment_plan_id', $planIds)
                ->whereIn('status', [
                    self::INSTALLMENT_STATUS_PENDING,
                    self::INSTALLMENT_STATUS_PARTIAL,
                ])
                ->whereDate('due_date', '<=', $monthEndDate)
                ->orderBy('due_date')
                ->orderBy('installment_no')
                ->get([
                    'id',
                    'fee_installment_plan_id',
                    'student_id',
                    'installment_no',
                    'title',
                    'due_date',
                    'amount',
                    'paid_amount',
                    'status',
                ])
                ->groupBy('fee_installment_plan_id');
        }

        $manualArrearsByStudent = collect();
        if ($this->studentArrearTableExists()) {
            $manualArrearsByStudent = StudentArrear::query()
                ->whereIn('student_id', $studentIds)
                ->whereIn('status', [
                    self::ARREAR_STATUS_PENDING,
                    self::ARREAR_STATUS_PARTIAL,
                ])
                ->where(function ($query) use ($session): void {
                    $query->whereNull('session')
                        ->orWhere('session', $session);
                })
                ->orderBy('due_date')
                ->orderBy('id')
                ->get([
                    'id',
                    'student_id',
                    'title',
                    'amount',
                    'paid_amount',
                    'due_date',
                    'status',
                ])
                ->groupBy('student_id');
        }

        $feeStructures = FeeStructure::query()
            ->where('session', $session)
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->orderByDesc('is_monthly')
            ->orderBy('title')
            ->get(['id', 'title', 'amount', 'fee_type', 'is_monthly']);

        if ($feeStructures->isEmpty() && $activePlansByStudent->isEmpty()) {
            throw new RuntimeException('No active fee structures or installment plans found for selected class and session.');
        }

        $oneTimeStructureIds = $feeStructures->where('is_monthly', false)->pluck('id');
        $studentCustomFeeByStudent = collect();
        if ($this->studentCustomFeeTableExists()) {
            $studentCustomFeeByStudent = StudentFeeStructure::query()
                ->where('session', $session)
                ->where('is_active', true)
                ->whereIn('student_id', $studentIds)
                ->get(['student_id', 'tuition_fee', 'computer_fee', 'exam_fee', 'is_active'])
                ->keyBy('student_id');
        }

        $alreadyBilledOneTime = collect();
        if ($oneTimeStructureIds->isNotEmpty()) {
            $alreadyBilledOneTime = FeeChallanItem::query()
                ->join('fee_challans', 'fee_challans.id', '=', 'fee_challan_items.fee_challan_id')
                ->where('fee_challans.session', $session)
                ->whereIn('fee_challans.student_id', $studentIds)
                ->whereIn('fee_challan_items.fee_structure_id', $oneTimeStructureIds)
                ->get([
                    'fee_challans.student_id',
                    'fee_challan_items.fee_structure_id',
                ])
                ->groupBy('student_id')
                ->map(fn (Collection $rows) => $rows->pluck('fee_structure_id')->map(fn ($id) => (int) $id)->unique()->values()->all());
        }

        $existingChallansByKey = FeeChallan::query()
            ->where('session', $session)
            ->where('month', $month)
            ->whereIn('student_id', $studentIds)
            ->get(['id', 'student_id', 'session', 'month', 'status'])
            ->keyBy(fn (FeeChallan $challan): string => $challan->student_id.'|'.$challan->session.'|'.$challan->month);

        $legacyArrearsByStudent = FeeChallan::query()
            ->withSum('payments as paid_amount', 'amount_paid')
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', $this->pendingStatuses())
            ->where(function ($query) use ($session, $month): void {
                $query->where('session', '!=', $session)
                    ->orWhere('month', '!=', $month);
            })
            ->get([
                'id',
                'student_id',
                'session',
                'month',
                'total_amount',
            ])
            ->groupBy('student_id')
            ->map(fn (Collection $rows): float => round((float) $rows->sum(function (FeeChallan $challan): float {
                $total = (float) $challan->total_amount;
                $paid = (float) ($challan->paid_amount ?? 0);

                return $this->remainingFromTotals($total, $paid);
            }), 2));

        $createdCount = 0;
        $updatedExistingCount = 0;
        $skippedExistingCount = 0;
        $skippedNoItemsCount = 0;
        $totalArrearsAdded = 0.0;
        $resolvedGeneratedBy = $this->resolveUserId($generatedBy);

        $this->executeWithUserForeignKeyFallback(
            $resolvedGeneratedBy,
            function (?int $safeGeneratedBy) use (
                $students,
                $feeStructures,
                $session,
                $month,
                $dueDate,
                $classId,
                $alreadyBilledOneTime,
                $existingChallansByKey,
                $legacyArrearsByStudent,
                $activePlansByStudent,
                $installmentsByPlan,
                $manualArrearsByStudent,
                $studentCustomFeeByStudent,
                &$createdCount,
                &$updatedExistingCount,
                &$skippedExistingCount,
                &$skippedNoItemsCount,
                &$totalArrearsAdded
            ): void {
                DB::transaction(function () use (
                    $students,
                    $feeStructures,
                    $session,
                    $month,
                    $dueDate,
                    $safeGeneratedBy,
                    $classId,
                    $alreadyBilledOneTime,
                    $existingChallansByKey,
                    $legacyArrearsByStudent,
                    $activePlansByStudent,
                    $installmentsByPlan,
                    $manualArrearsByStudent,
                    $studentCustomFeeByStudent,
                    &$createdCount,
                    &$updatedExistingCount,
                    &$skippedExistingCount,
                    &$skippedNoItemsCount,
                    &$totalArrearsAdded
                ): void {
                    foreach ($students as $student) {
                        $challanKey = $student->id.'|'.$session.'|'.$month;
                        $existingChallan = $existingChallansByKey->get($challanKey);

                        $blockedOneTime = collect($alreadyBilledOneTime->get($student->id, []))
                            ->map(fn ($id): int => (int) $id)
                            ->flip();

                        $items = [];
                        $totalAmount = 0.0;
                        $manualArrearAmount = 0.0;
                        $studentPlan = $activePlansByStudent->get($student->id);

                        if ($studentPlan instanceof FeeInstallmentPlan) {
                            $dueInstallments = collect($installmentsByPlan->get($studentPlan->id, collect()));

                            foreach ($dueInstallments as $installment) {
                                $lineAmount = $this->lineRemainingAmount(
                                    (float) $installment->amount,
                                    (float) $installment->paid_amount
                                );

                                if ($lineAmount <= 0) {
                                    continue;
                                }

                                $dueLabel = optional($installment->due_date)->toDateString() ?? '-';
                                $title = trim((string) $installment->title) !== ''
                                    ? (string) $installment->title
                                    : 'Installment '.$installment->installment_no;

                                $items[] = $this->makeChallanItemData(
                                    null,
                                    sprintf('%s (Due: %s)', $title, $dueLabel),
                                    'installment',
                                    $lineAmount,
                                    (int) $installment->id,
                                    null
                                );
                                $totalAmount += $lineAmount;
                            }
                        } else {
                            $studentCustomFee = $studentCustomFeeByStudent->get($student->id);

                            foreach ($feeStructures as $structure) {
                                $assignment = StudentFeeAssignment::query()->firstOrCreate(
                                    [
                                        'student_id' => $student->id,
                                        'fee_structure_id' => $structure->id,
                                        'session' => $session,
                                    ],
                                    [
                                        'custom_amount' => null,
                                        'is_active' => true,
                                        'assigned_by' => $safeGeneratedBy,
                                    ]
                                );

                                if (! $assignment->is_active) {
                                    continue;
                                }

                                if (! $structure->is_monthly && $blockedOneTime->has((int) $structure->id)) {
                                    continue;
                                }

                                $customFeeAmount = $this->lineAmountFromStudentCustomFee($studentCustomFee, $structure);
                                $lineAmount = $customFeeAmount ?? ($assignment->custom_amount !== null
                                    ? (float) $assignment->custom_amount
                                    : (float) $structure->amount);

                                if ($lineAmount <= 0) {
                                    continue;
                                }

                                $items[] = $this->makeChallanItemData(
                                    (int) $structure->id,
                                    (string) $structure->title,
                                    (string) $structure->fee_type,
                                    $lineAmount,
                                    null,
                                    null
                                );
                                $totalAmount += $lineAmount;
                            }
                        }

                        $studentArrears = collect($manualArrearsByStudent->get($student->id, collect()));
                        foreach ($studentArrears as $arrear) {
                            $lineAmount = $this->lineRemainingAmount(
                                (float) $arrear->amount,
                                (float) $arrear->paid_amount
                            );

                            if ($lineAmount <= 0) {
                                continue;
                            }

                            $dueLabel = optional($arrear->due_date)->toDateString();
                            $title = trim((string) $arrear->title) !== ''
                                ? 'Arrear: '.trim((string) $arrear->title)
                                : 'Manual Arrear';
                            if ($dueLabel !== null && $dueLabel !== '') {
                                $title .= ' (Due: '.$dueLabel.')';
                            }

                            $items[] = $this->makeChallanItemData(
                                null,
                                $title,
                                'arrear',
                                $lineAmount,
                                null,
                                (int) $arrear->id
                            );

                            $manualArrearAmount += $lineAmount;
                            $totalAmount += $lineAmount;
                        }

                        $legacyArrearAmount = 0.0;
                        if (! ($studentPlan instanceof FeeInstallmentPlan)) {
                            $legacyArrearAmount = round((float) ($legacyArrearsByStudent->get($student->id, 0) ?? 0), 2);
                        }

                        $baseAndManualAmount = round($totalAmount, 2);
                        $arrearsAmount = round($manualArrearAmount + $legacyArrearAmount, 2);
                        $finalTotalAmount = round($baseAndManualAmount + $legacyArrearAmount, 2);

                        if (empty($items) && $legacyArrearAmount <= 0) {
                            $skippedNoItemsCount++;
                            continue;
                        }

                        if ($existingChallan instanceof FeeChallan) {
                            if (! $this->refreshExistingChallan(
                                $existingChallan,
                                $items,
                                $classId,
                                $session,
                                $month,
                                $dueDate,
                                $arrearsAmount,
                                $finalTotalAmount,
                                $safeGeneratedBy
                            )) {
                                $skippedExistingCount++;
                                continue;
                            }

                            $updatedExistingCount++;
                            $totalArrearsAdded += round($arrearsAmount, 2);

                            continue;
                        }

                        $challan = FeeChallan::query()->create([
                            'challan_number' => $this->challanNumber($session, $month, (int) $student->id),
                            'student_id' => $student->id,
                            'class_id' => $classId,
                            'session' => $session,
                            'month' => $month,
                            'issue_date' => now()->toDateString(),
                            'due_date' => $dueDate,
                            'arrears' => $arrearsAmount,
                            'late_fee' => 0,
                            'late_fee_waived_at' => null,
                            'total_amount' => $finalTotalAmount,
                            'status' => self::STATUS_UNPAID,
                            'generated_by' => $safeGeneratedBy,
                        ]);

                        foreach ($items as $item) {
                            $challan->items()->create($item);
                        }

                        $createdCount++;
                        $totalArrearsAdded += round($arrearsAmount, 2);
                    }
                });
            }
        );

        return [
            'class_name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
            'session' => $session,
            'month' => $month,
            'month_label' => $this->monthLabel($month),
            'total_students' => $students->count(),
            'created' => $createdCount,
            'updated_existing' => $updatedExistingCount,
            'skipped_existing' => $skippedExistingCount,
            'skipped_no_items' => $skippedNoItemsCount,
            'total_arrears_added' => round($totalArrearsAdded, 2),
            'due_date' => Carbon::parse($dueDate)->toDateString(),
        ];
    }

    public function recordPayment(
        FeeChallan $challan,
        float $amountPaid,
        string $paymentDate,
        int $receivedBy,
        ?string $paymentMethod = null,
        ?string $referenceNo = null,
        ?string $notes = null
    ): FeePayment {
        if ($amountPaid <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        $resolvedReceivedBy = $this->resolveUserId($receivedBy);

        return $this->executeWithUserForeignKeyFallback(
            $resolvedReceivedBy,
            function (?int $safeReceivedBy) use (
                $challan,
                $amountPaid,
                $paymentDate,
                $paymentMethod,
                $referenceNo,
                $notes
            ): FeePayment {
                return DB::transaction(function () use (
                    $challan,
                    $amountPaid,
                    $paymentDate,
                    $safeReceivedBy,
                    $paymentMethod,
                    $referenceNo,
                    $notes
                ): FeePayment {
                    $locked = FeeChallan::query()->lockForUpdate()->find($challan->id);
                    if (! $locked) {
                        throw new RuntimeException('Challan not found.');
                    }

                    $normalizedStatus = $this->normalizeStatus($locked->status);
                    if ($normalizedStatus !== $locked->status) {
                        $locked->forceFill(['status' => $normalizedStatus])->save();
                    }

                    $paidBefore = (float) FeePayment::query()
                        ->where('fee_challan_id', $locked->id)
                        ->sum('amount_paid');

                    $this->applyLateFeeIfEligibleToLockedChallan($locked, $paidBefore);

                    $total = (float) $locked->total_amount;
                    $remaining = $this->remainingFromTotals($total, $paidBefore);

                    if ($remaining <= 0) {
                        throw new RuntimeException('This challan is already fully paid.');
                    }

                    if ($amountPaid > $remaining) {
                        throw new RuntimeException('Payment amount cannot exceed remaining balance.');
                    }

                    $payment = FeePayment::query()->create([
                        'fee_challan_id' => $locked->id,
                        'amount_paid' => round($amountPaid, 2),
                        'payment_date' => $paymentDate,
                        'payment_method' => $paymentMethod !== null && $paymentMethod !== '' ? $paymentMethod : null,
                        'reference_no' => $referenceNo !== null && $referenceNo !== '' ? $referenceNo : null,
                        'received_by' => $safeReceivedBy,
                        'notes' => $notes !== null && $notes !== '' ? $notes : null,
                    ]);

                    $paidAfter = round($paidBefore + $amountPaid, 2);
                    $status = $this->statusFromPaidAndTotal($paidAfter, $total);
                    $isPaid = $status === self::STATUS_PAID;

                    $locked->forceFill([
                        'status' => $status,
                        'paid_at' => $isPaid ? now() : null,
                    ])->save();

                    $this->syncLinkedBalancesForChallan($locked);

                    return $payment;
                });
            }
        );
    }

    public function waiveLateFee(FeeChallan $challan, int $waivedBy): FeeChallan
    {
        return DB::transaction(function () use ($challan, $waivedBy): FeeChallan {
            $locked = FeeChallan::query()->lockForUpdate()->find($challan->id);
            if (! $locked) {
                throw new RuntimeException('Challan not found.');
            }

            $lateFee = round((float) ($locked->late_fee ?? 0), 2);
            if ($lateFee <= 0) {
                throw new RuntimeException('No late fee is available to waive for this challan.');
            }

            $paidAmount = (float) FeePayment::query()
                ->where('fee_challan_id', $locked->id)
                ->sum('amount_paid');

            $newTotal = round(max((float) $locked->total_amount - $lateFee, 0), 2);
            $status = $this->statusFromPaidAndTotal($paidAmount, $newTotal);

            $locked->forceFill([
                'late_fee' => 0,
                'late_fee_waived_at' => now(),
                'total_amount' => $newTotal,
                'status' => $status,
                'paid_at' => $status === self::STATUS_PAID ? ($locked->paid_at ?? now()) : null,
            ])->save();

            Log::info('Late fee waived for challan.', [
                'challan_id' => $locked->id,
                'late_fee_removed' => $lateFee,
                'waived_by' => $waivedBy,
                'status_after' => $status,
                'total_after' => $newTotal,
            ]);

            return $locked;
        });
    }

    public function schoolMeta(): array
    {
        $setting = SchoolSetting::cached();

        $logoAbsolutePath = null;
        if ($setting?->logo_path) {
            $absolute = public_path('storage/'.$setting->logo_path);
            if (is_file($absolute)) {
                $logoAbsolutePath = $absolute;
            }
        }

        return [
            'name' => $setting?->school_name ?? 'School Management System',
            'logo_absolute_path' => $logoAbsolutePath,
        ];
    }

    public function challanPayload(FeeChallan $challan): array
    {
        $challan->loadMissing([
            'student:id,name,student_id,class_id',
            'classRoom:id,name,section',
            'items:id,fee_challan_id,title,fee_type,amount',
            'payments:id,fee_challan_id,amount_paid,payment_date',
        ]);

        $paid = (float) $challan->payments->sum('amount_paid');
        $total = (float) $challan->total_amount;
        $arrears = round((float) ($challan->arrears ?? 0), 2);
        $lateFee = round((float) ($challan->late_fee ?? 0), 2);
        $baseFee = $this->baseFeeAmount($challan);

        return [
            'school' => $this->schoolMeta(),
            'challan' => [
                'number' => $challan->challan_number,
                'session' => $challan->session,
                'month' => $challan->month,
                'month_label' => $this->monthLabel($challan->month),
                'issue_date' => optional($challan->issue_date)->format('Y-m-d'),
                'due_date' => optional($challan->due_date)->format('Y-m-d'),
                'status' => $this->normalizeStatus($challan->status),
                'late_fee_waived_at' => optional($challan->late_fee_waived_at)->format('Y-m-d H:i:s'),
            ],
            'student' => [
                'name' => $challan->student?->name ?? 'Student',
                'student_id' => $challan->student?->student_id ?? '-',
                'class' => trim(($challan->classRoom?->name ?? 'Class').' '.($challan->classRoom?->section ?? '')),
            ],
            'items' => $challan->items->map(fn (FeeChallanItem $item): array => [
                'title' => $item->title,
                'fee_type' => $item->fee_type,
                'amount' => (float) $item->amount,
            ])->values()->all(),
            'summary' => [
                'fee_amount' => $baseFee,
                'arrears' => $arrears,
                'late_fee' => $lateFee,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'remaining_amount' => $this->remainingFromTotals($total, $paid),
            ],
        ];
    }

    private function generateInstallmentsForPlan(FeeInstallmentPlan $plan): void
    {
        $totalInstallments = max((int) $plan->number_of_installments, 1);
        $totalAmountCents = (int) round(round((float) $plan->total_amount, 2) * 100);
        $baseAmountCents = intdiv($totalAmountCents, $totalInstallments);
        $remainderCents = $totalAmountCents - ($baseAmountCents * $totalInstallments);
        $firstDueDate = $plan->first_due_date?->copy() ?? Carbon::parse((string) $plan->first_due_date);
        $customIntervalDays = max((int) ($plan->custom_interval_days ?? 0), 1);

        $rows = [];
        for ($index = 1; $index <= $totalInstallments; $index++) {
            $amountCents = $baseAmountCents;
            if ($index === $totalInstallments) {
                $amountCents += $remainderCents;
            }

            $dueDate = $this->installmentDueDateForIndex(
                $firstDueDate,
                (string) $plan->plan_type,
                $customIntervalDays,
                $index
            );

            $rows[] = [
                'fee_installment_plan_id' => (int) $plan->id,
                'student_id' => (int) $plan->student_id,
                'installment_no' => $index,
                'title' => sprintf('Installment %d of %d', $index, $totalInstallments),
                'due_date' => $dueDate,
                'amount' => round($amountCents / 100, 2),
                'paid_amount' => 0,
                'status' => self::INSTALLMENT_STATUS_PENDING,
                'paid_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $plan->installments()->delete();
        FeeInstallment::query()->insert($rows);
    }

    private function installmentDueDateForIndex(
        Carbon $firstDueDate,
        string $planType,
        int $customIntervalDays,
        int $index
    ): string {
        $offset = max($index - 1, 0);
        $baseDate = $firstDueDate->copy()->startOfDay();

        if ($offset === 0) {
            return $baseDate->toDateString();
        }

        return match (strtolower(trim($planType))) {
            FeeInstallmentPlan::TYPE_QUARTERLY => $baseDate->addMonthsNoOverflow($offset * 3)->toDateString(),
            FeeInstallmentPlan::TYPE_CUSTOM => $baseDate->addDays($offset * max($customIntervalDays, 1))->toDateString(),
            default => $baseDate->addMonthsNoOverflow($offset)->toDateString(),
        };
    }

    private function syncLinkedBalancesForChallan(FeeChallan $challan): void
    {
        if (! $this->challanItemLinkColumnsExist()) {
            return;
        }

        $challanId = (int) $challan->id;
        $items = FeeChallanItem::query()
            ->where('fee_challan_id', $challanId)
            ->where(function ($query): void {
                $query->whereNotNull('fee_installment_id')
                    ->orWhereNotNull('student_arrear_id');
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->get([
                'id',
                'amount',
                'paid_amount',
                'fee_installment_id',
                'student_arrear_id',
            ]);

        if ($items->isEmpty()) {
            return;
        }

        $totalPaid = round((float) FeePayment::query()
            ->where('fee_challan_id', $challanId)
            ->sum('amount_paid'), 2);

        $remainingToAllocate = $totalPaid;
        foreach ($items as $item) {
            $lineAmount = round((float) $item->amount, 2);
            $alreadyAllocated = round((float) $item->paid_amount, 2);
            $targetAllocated = round(min($lineAmount, max($remainingToAllocate, 0)), 2);
            $remainingToAllocate = round(max($remainingToAllocate - $targetAllocated, 0), 2);

            $delta = round($targetAllocated - $alreadyAllocated, 2);
            if (abs($delta) < 0.01) {
                continue;
            }

            $item->forceFill([
                'paid_amount' => $targetAllocated,
            ])->save();

            if ($item->fee_installment_id !== null) {
                $this->applyInstallmentPaymentDelta((int) $item->fee_installment_id, $delta);
            }

            if ($item->student_arrear_id !== null) {
                $this->applyArrearPaymentDelta((int) $item->student_arrear_id, $delta);
            }
        }
    }

    private function applyInstallmentPaymentDelta(int $installmentId, float $delta): void
    {
        $installment = FeeInstallment::query()->lockForUpdate()->find($installmentId);
        if (! $installment) {
            return;
        }

        $amount = round((float) $installment->amount, 2);
        $paidBefore = round((float) $installment->paid_amount, 2);
        $newPaid = round($paidBefore + $delta, 2);
        $newPaid = round(min(max($newPaid, 0), $amount), 2);

        $status = $this->normalizeInstallmentStatus($this->statusFromPaidAndTotal($newPaid, $amount));
        $installment->forceFill([
            'paid_amount' => $newPaid,
            'status' => $status,
            'paid_at' => $status === self::INSTALLMENT_STATUS_PAID ? ($installment->paid_at ?? now()) : null,
        ])->save();
    }

    private function applyArrearPaymentDelta(int $arrearId, float $delta): void
    {
        $arrear = StudentArrear::query()->lockForUpdate()->find($arrearId);
        if (! $arrear) {
            return;
        }

        $amount = round((float) $arrear->amount, 2);
        $paidBefore = round((float) $arrear->paid_amount, 2);
        $newPaid = round($paidBefore + $delta, 2);
        $newPaid = round(min(max($newPaid, 0), $amount), 2);

        $status = $this->normalizeArrearStatus($this->statusFromPaidAndTotal($newPaid, $amount));
        $arrear->forceFill([
            'paid_amount' => $newPaid,
            'status' => $status,
            'resolved_at' => $status === self::ARREAR_STATUS_PAID ? ($arrear->resolved_at ?? now()) : null,
        ])->save();
    }

    /**
     * @template TReturn
     * @param callable(?int): TReturn $callback
     * @return TReturn
     */
    private function executeWithUserForeignKeyFallback(?int $userId, callable $callback): mixed
    {
        try {
            return $callback($userId);
        } catch (QueryException $exception) {
            if ($userId !== null && $this->isUserForeignKeyViolation($exception)) {
                Log::warning('User FK failed during fee write; retrying without user reference.', [
                    'user_id' => $userId,
                    'sql_state' => $exception->errorInfo[0] ?? null,
                    'driver_code' => $exception->errorInfo[1] ?? null,
                    'message' => $exception->getMessage(),
                ]);

                return $callback(null);
            }

            throw $exception;
        }
    }

    private function isUserForeignKeyViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        if ($sqlState !== '23000' || $driverCode !== 1452) {
            return false;
        }

        $message = strtolower($exception->getMessage());
        $referencesUsers = str_contains($message, 'references `users` (`id`)');
        if (! $referencesUsers) {
            return false;
        }

        return (str_contains($message, 'fee_structures') && str_contains($message, 'created_by'))
            || (str_contains($message, 'student_fee_structures') && str_contains($message, 'created_by'))
            || (str_contains($message, 'student_fee_assignments') && str_contains($message, 'assigned_by'))
            || (str_contains($message, 'fee_challans') && str_contains($message, 'generated_by'))
            || (str_contains($message, 'fee_payments') && str_contains($message, 'received_by'))
            || (str_contains($message, 'fee_installment_plans') && str_contains($message, 'created_by'))
            || (str_contains($message, 'student_arrears') && str_contains($message, 'added_by'));
    }

    private function resolveUserId(?int $userId): ?int
    {
        if ($userId === null || $userId <= 0) {
            return null;
        }

        return User::query()->useWritePdo()->whereKey($userId)->exists()
            ? $userId
            : null;
    }

    /**
     * Replace an unpaid challan with fresh items so custom-fee changes can be reissued safely.
     *
     * @param array<int, array{
     *   fee_structure_id:int|null,
     *   title:string,
     *   fee_type:string,
     *   amount:float,
     *   fee_installment_id?:int|null,
     *   student_arrear_id?:int|null,
     *   paid_amount?:float|int
     * }> $items
     */
    private function refreshExistingChallan(
        FeeChallan $challan,
        array $items,
        int $classId,
        string $session,
        string $month,
        string $dueDate,
        float $arrearsAmount,
        float $totalAmount,
        ?int $generatedBy
    ): bool {
        $locked = FeeChallan::query()->lockForUpdate()->find($challan->id);
        if (! $locked) {
            return false;
        }

        $paymentCount = (int) FeePayment::query()
            ->where('fee_challan_id', $locked->id)
            ->count();
        $paidAmount = round((float) FeePayment::query()
            ->where('fee_challan_id', $locked->id)
            ->sum('amount_paid'), 2);

        if ($paymentCount > 0 || $paidAmount > 0) {
            return false;
        }

        $locked->items()->delete();
        $locked->forceFill([
            'class_id' => $classId,
            'session' => $session,
            'month' => $month,
            'issue_date' => now()->toDateString(),
            'due_date' => $dueDate,
            'arrears' => round($arrearsAmount, 2),
            'late_fee' => 0,
            'late_fee_waived_at' => null,
            'total_amount' => round($totalAmount, 2),
            'status' => self::STATUS_UNPAID,
            'generated_by' => $generatedBy,
            'paid_at' => null,
        ])->save();

        foreach ($items as $item) {
            $locked->items()->create($item);
        }

        return true;
    }

    private function lineAmountFromStudentCustomFee(?StudentFeeStructure $studentCustomFee, FeeStructure $structure): ?float
    {
        if (! $studentCustomFee || ! $studentCustomFee->is_active) {
            return null;
        }

        $field = $this->customFeeFieldFromFeeHead((string) $structure->fee_type, (string) $structure->title);
        if ($field === null) {
            return null;
        }

        return round((float) ($studentCustomFee->{$field} ?? 0), 2);
    }

    /**
     * @param array<int, string> $keywords
     */
    private function matchesCustomFeeKeyword(string $value, array $keywords): bool
    {
        if ($value === '') {
            return false;
        }

        foreach ($keywords as $keyword) {
            if (str_contains($value, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function applyLateFeeIfEligibleToLockedChallan(FeeChallan $challan, float $paidAmount): void
    {
        $lateFeeAmount = $this->lateFeeAmount();
        $graceDays = $this->graceDays();
        $asOfDate = now()->startOfDay();

        if (! $this->isEligibleForLateFee($challan, $paidAmount, $asOfDate, $graceDays, $lateFeeAmount)) {
            return;
        }

        $currentLateFee = round((float) ($challan->late_fee ?? 0), 2);
        $newLateFee = round($currentLateFee + $lateFeeAmount, 2);
        $newTotal = round((float) $challan->total_amount + $lateFeeAmount, 2);

        $challan->forceFill([
            'late_fee' => $newLateFee,
            'total_amount' => $newTotal,
            'status' => $this->normalizeStatus($challan->status),
        ])->save();

        Log::info('Late fee auto-applied during payment attempt.', [
            'challan_id' => $challan->id,
            'late_fee_added' => $lateFeeAmount,
            'late_fee_after' => $newLateFee,
            'total_after' => $newTotal,
        ]);
    }

    private function isEligibleForLateFee(
        FeeChallan $challan,
        float $paidAmount,
        Carbon $asOfDate,
        int $graceDays,
        float $lateFeeAmount
    ): bool {
        if ($lateFeeAmount <= 0) {
            return false;
        }

        if ($challan->due_date === null) {
            return false;
        }

        if ($challan->late_fee_waived_at !== null) {
            return false;
        }

        if (round((float) ($challan->late_fee ?? 0), 2) > 0) {
            return false;
        }

        $status = $this->normalizeStatus($challan->status);
        if (! in_array($status, [self::STATUS_UNPAID, self::STATUS_PARTIAL], true)) {
            return false;
        }

        $eligibleAfter = $challan->due_date->copy()->addDays($graceDays)->startOfDay();
        if ($asOfDate->lessThanOrEqualTo($eligibleAfter)) {
            return false;
        }

        return $this->remainingFromTotals((float) $challan->total_amount, $paidAmount) > 0;
    }

    private function statusFromPaidAndTotal(float $paidAmount, float $totalAmount): string
    {
        $paid = round($paidAmount, 2);
        $total = round($totalAmount, 2);

        if ($paid >= $total && $total > 0) {
            return self::STATUS_PAID;
        }

        if ($paid > 0) {
            return self::STATUS_PARTIAL;
        }

        return self::STATUS_UNPAID;
    }

    private function remainingFromTotals(float $totalAmount, float $paidAmount): float
    {
        return round(max(round($totalAmount, 2) - round($paidAmount, 2), 0), 2);
    }

    private function lineRemainingAmount(float $totalAmount, float $paidAmount): float
    {
        return round(max(round($totalAmount, 2) - round($paidAmount, 2), 0), 2);
    }

    private function baseFeeAmount(FeeChallan $challan): float
    {
        $base = round((float) $challan->total_amount, 2)
            - round((float) ($challan->arrears ?? 0), 2)
            - round((float) ($challan->late_fee ?? 0), 2);

        return round(max($base, 0), 2);
    }

    private function challanNumber(string $session, string $month, int $studentId): string
    {
        $sessionKey = str_replace('-', '', $session);
        $monthKey = str_replace('-', '', $month);

        return sprintf('CH-%s-%s-%06d', $sessionKey, $monthKey, $studentId);
    }

    /**
     * @return array{
     *   fee_structure_id:int|null,
     *   title:string,
     *   fee_type:string,
     *   amount:float,
     *   fee_installment_id?:int|null,
     *   student_arrear_id?:int|null,
     *   paid_amount?:float|int
     * }
     */
    private function makeChallanItemData(
        ?int $feeStructureId,
        string $title,
        string $feeType,
        float $amount,
        ?int $feeInstallmentId = null,
        ?int $studentArrearId = null
    ): array {
        $data = [
            'fee_structure_id' => $feeStructureId,
            'title' => $title,
            'fee_type' => $feeType,
            'amount' => round($amount, 2),
        ];

        if ($this->challanItemLinkColumnsExist()) {
            $data['fee_installment_id'] = $feeInstallmentId;
            $data['student_arrear_id'] = $studentArrearId;
            $data['paid_amount'] = 0;
        }

        return $data;
    }

    private function installmentPlanTablesExist(): bool
    {
        return Schema::hasTable('fee_installment_plans') && Schema::hasTable('fee_installments');
    }

    private function studentArrearTableExists(): bool
    {
        return Schema::hasTable('student_arrears');
    }

    private function challanItemLinkColumnsExist(): bool
    {
        if (! Schema::hasTable('fee_challan_items')) {
            return false;
        }

        return Schema::hasColumn('fee_challan_items', 'fee_installment_id')
            && Schema::hasColumn('fee_challan_items', 'student_arrear_id')
            && Schema::hasColumn('fee_challan_items', 'paid_amount');
    }

    private function studentCustomFeeTableExists(): bool
    {
        return Schema::hasTable('student_fee_structures');
    }
}
