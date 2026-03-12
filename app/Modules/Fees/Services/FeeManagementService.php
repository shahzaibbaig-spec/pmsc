<?php

namespace App\Modules\Fees\Services;

use App\Models\FeeChallan;
use App\Models\FeeChallanItem;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentFeeAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FeeManagementService
{
    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

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

    public function lateFeeAmount(): float
    {
        return round(max((float) config('fees.late_fee_amount', 200), 0), 2);
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

        $feeStructures = FeeStructure::query()
            ->where('session', $session)
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->orderByDesc('is_monthly')
            ->orderBy('title')
            ->get(['id', 'title', 'amount', 'fee_type', 'is_monthly']);

        if ($feeStructures->isEmpty()) {
            throw new RuntimeException('No active fee structures found for selected class and session.');
        }

        $studentIds = $students->pluck('id');
        $oneTimeStructureIds = $feeStructures->where('is_monthly', false)->pluck('id');

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

        $existingChallanKeys = FeeChallan::query()
            ->where('session', $session)
            ->where('month', $month)
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'session', 'month'])
            ->mapWithKeys(fn (FeeChallan $challan): array => [
                $challan->student_id.'|'.$challan->session.'|'.$challan->month => true,
            ]);

        $arrearsByStudent = FeeChallan::query()
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
        $skippedExistingCount = 0;
        $skippedNoItemsCount = 0;
        $totalArrearsAdded = 0.0;

        DB::transaction(function () use (
            $students,
            $feeStructures,
            $session,
            $month,
            $dueDate,
            $generatedBy,
            $classId,
            $alreadyBilledOneTime,
            $existingChallanKeys,
            $arrearsByStudent,
            &$createdCount,
            &$skippedExistingCount,
            &$skippedNoItemsCount,
            &$totalArrearsAdded
        ): void {
            foreach ($students as $student) {
                $challanKey = $student->id.'|'.$session.'|'.$month;
                if ($existingChallanKeys->has($challanKey)) {
                    $skippedExistingCount++;
                    continue;
                }

                $blockedOneTime = collect($alreadyBilledOneTime->get($student->id, []))
                    ->map(fn ($id): int => (int) $id)
                    ->flip();

                $items = [];
                $totalAmount = 0.0;

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
                            'assigned_by' => $generatedBy,
                        ]
                    );

                    if (! $assignment->is_active) {
                        continue;
                    }

                    if (! $structure->is_monthly && $blockedOneTime->has((int) $structure->id)) {
                        continue;
                    }

                    $lineAmount = $assignment->custom_amount !== null
                        ? (float) $assignment->custom_amount
                        : (float) $structure->amount;

                    if ($lineAmount <= 0) {
                        continue;
                    }

                    $items[] = [
                        'fee_structure_id' => $structure->id,
                        'title' => $structure->title,
                        'fee_type' => $structure->fee_type,
                        'amount' => round($lineAmount, 2),
                    ];
                    $totalAmount += $lineAmount;
                }

                $baseFeeAmount = round($totalAmount, 2);
                $arrearsAmount = round((float) ($arrearsByStudent->get($student->id, 0) ?? 0), 2);
                $finalTotalAmount = round($baseFeeAmount + $arrearsAmount, 2);

                if (empty($items) && $arrearsAmount <= 0) {
                    $skippedNoItemsCount++;
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
                    'generated_by' => $generatedBy,
                ]);

                foreach ($items as $item) {
                    $challan->items()->create($item);
                }

                $createdCount++;
                $totalArrearsAdded += $arrearsAmount;
            }
        });

        return [
            'class_name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
            'session' => $session,
            'month' => $month,
            'month_label' => $this->monthLabel($month),
            'total_students' => $students->count(),
            'created' => $createdCount,
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

        return DB::transaction(function () use (
            $challan,
            $amountPaid,
            $paymentDate,
            $receivedBy,
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
                'received_by' => $receivedBy,
                'notes' => $notes !== null && $notes !== '' ? $notes : null,
            ]);

            $paidAfter = round($paidBefore + $amountPaid, 2);
            $status = $this->statusFromPaidAndTotal($paidAfter, $total);
            $isPaid = $status === self::STATUS_PAID;

            $locked->forceFill([
                'status' => $status,
                'paid_at' => $isPaid ? now() : null,
            ])->save();

            return $payment;
        });
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
}
