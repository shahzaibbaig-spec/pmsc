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
use RuntimeException;

class FeeManagementService
{
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

    public function generateClassChallans(
        string $session,
        int $classId,
        string $month,
        string $dueDate,
        int $generatedBy
    ): array {
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

        $createdCount = 0;
        $skippedExistingCount = 0;
        $skippedNoItemsCount = 0;

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
            &$createdCount,
            &$skippedExistingCount,
            &$skippedNoItemsCount
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

                if (empty($items)) {
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
                    'total_amount' => round($totalAmount, 2),
                    'status' => 'unpaid',
                    'generated_by' => $generatedBy,
                ]);

                foreach ($items as $item) {
                    $challan->items()->create($item);
                }

                $createdCount++;
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

            $paidBefore = (float) FeePayment::query()
                ->where('fee_challan_id', $locked->id)
                ->sum('amount_paid');

            $total = (float) $locked->total_amount;
            $remaining = round($total - $paidBefore, 2);

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
            $isPaid = $paidAfter >= $total;

            $locked->forceFill([
                'status' => $isPaid ? 'paid' : 'partially_paid',
                'paid_at' => $isPaid ? now() : null,
            ])->save();

            return $payment;
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

        return [
            'school' => $this->schoolMeta(),
            'challan' => [
                'number' => $challan->challan_number,
                'session' => $challan->session,
                'month' => $challan->month,
                'month_label' => $this->monthLabel($challan->month),
                'issue_date' => optional($challan->issue_date)->format('Y-m-d'),
                'due_date' => optional($challan->due_date)->format('Y-m-d'),
                'status' => $challan->status,
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
                'total_amount' => $total,
                'paid_amount' => $paid,
                'remaining_amount' => round(max($total - $paid, 0), 2),
            ],
        ];
    }

    private function challanNumber(string $session, string $month, int $studentId): string
    {
        $sessionKey = str_replace('-', '', $session);
        $monthKey = str_replace('-', '', $month);

        return sprintf('CH-%s-%s-%06d', $sessionKey, $monthKey, $studentId);
    }
}
