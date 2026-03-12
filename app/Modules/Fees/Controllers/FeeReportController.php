<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeChallan;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Modules\Fees\Services\FeeManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FeeReportController extends Controller
{
    public function __construct(private readonly FeeManagementService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->service->processLateFees();

        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $baseQuery = $this->applyFilters(FeeChallan::query(), $filters);
        $totalChallans = (clone $baseQuery)->count();
        $totalBilled = (float) (clone $baseQuery)->sum('total_amount');
        $paidChallans = (clone $baseQuery)->where('status', 'paid')->count();
        $pendingChallans = (clone $baseQuery)->whereIn('status', ['unpaid', 'partial', 'partially_paid'])->count();

        $challans = (clone $baseQuery)
            ->get(['id', 'class_id', 'session', 'month', 'total_amount']);

        $challanIds = $challans->pluck('id');
        $paymentTotals = FeePayment::query()
            ->whereIn('fee_challan_id', $challanIds)
            ->selectRaw('fee_challan_id, SUM(amount_paid) as total_paid')
            ->groupBy('fee_challan_id')
            ->pluck('total_paid', 'fee_challan_id');

        $totalCollected = (float) $paymentTotals->sum();
        $totalPending = round(max($totalBilled - $totalCollected, 0), 2);

        $classIds = $challans->pluck('class_id')->unique()->values();
        $classMap = SchoolClass::query()
            ->whereIn('id', $classIds)
            ->get(['id', 'name', 'section'])
            ->mapWithKeys(fn (SchoolClass $classRoom): array => [
                $classRoom->id => trim($classRoom->name.' '.($classRoom->section ?? '')),
            ]);

        $breakdown = $challans
            ->groupBy(fn (FeeChallan $challan): string => $challan->class_id.'|'.$challan->session.'|'.$challan->month)
            ->map(function (Collection $rows) use ($paymentTotals, $classMap): array {
                $first = $rows->first();
                $billed = (float) $rows->sum('total_amount');
                $collected = (float) $rows->sum(fn (FeeChallan $challan) => (float) ($paymentTotals->get($challan->id) ?? 0));

                return [
                    'class_name' => (string) ($classMap->get($first->class_id) ?? 'Class'),
                    'session' => (string) $first->session,
                    'month' => (string) $first->month,
                    'month_label' => $this->service->monthLabel((string) $first->month),
                    'challans_count' => $rows->count(),
                    'billed_amount' => round($billed, 2),
                    'collected_amount' => round($collected, 2),
                    'pending_amount' => round(max($billed - $collected, 0), 2),
                ];
            })
            ->sortByDesc(fn (array $row): string => $row['session'].'-'.$row['month'].'-'.$row['class_name'])
            ->values()
            ->all();

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $sessions = FeeChallan::query()
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->values()
            ->all();
        if (empty($sessions)) {
            $sessions = FeeStructure::query()
                ->select('session')
                ->distinct()
                ->orderByDesc('session')
                ->pluck('session')
                ->values()
                ->all();
        }
        if (empty($sessions)) {
            $sessions = $this->service->sessionOptions();
        }

        return view('modules.principal.fees.reports.index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'filters' => [
                'session' => $filters['session'] ?? '',
                'class_id' => $filters['class_id'] ?? '',
                'month' => $filters['month'] ?? '',
            ],
            'summary' => [
                'total_challans' => $totalChallans,
                'total_billed' => round($totalBilled, 2),
                'total_collected' => round($totalCollected, 2),
                'total_pending' => $totalPending,
                'paid_challans' => $paidChallans,
                'pending_challans' => $pendingChallans,
            ],
            'breakdown' => $breakdown,
        ]);
    }

    public function arrears(Request $request): View
    {
        $this->service->processLateFees();

        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $today = now()->toDateString();

        $challans = FeeChallan::query()
            ->with([
                'student:id,name,student_id',
                'classRoom:id,name,section',
            ])
            ->withSum('payments as paid_amount', 'amount_paid')
            ->whereIn('status', ['unpaid', 'partial', 'partially_paid'])
            ->when(($filters['session'] ?? null) !== null && $filters['session'] !== '', function ($builder) use ($filters): void {
                $builder->where('session', (string) $filters['session']);
            })
            ->when(($filters['class_id'] ?? null) !== null && $filters['class_id'] !== '', function ($builder) use ($filters): void {
                $builder->where('class_id', (int) $filters['class_id']);
            })
            ->when(($filters['search'] ?? null) !== null && trim((string) $filters['search']) !== '', function ($builder) use ($filters): void {
                $search = trim((string) $filters['search']);
                $builder->whereHas('student', function ($studentQuery) use ($search): void {
                    $studentQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('student_id', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->get([
                'id',
                'student_id',
                'class_id',
                'session',
                'month',
                'due_date',
                'total_amount',
                'arrears',
                'late_fee',
            ]);

        $outstandingRows = $challans
            ->map(function (FeeChallan $challan): ?array {
                $total = round((float) $challan->total_amount, 2);
                $paid = round((float) ($challan->paid_amount ?? 0), 2);
                $remaining = round(max($total - $paid, 0), 2);

                if ($remaining <= 0) {
                    return null;
                }

                return [
                    'challan_id' => (int) $challan->id,
                    'student_id' => (int) $challan->student_id,
                    'student_name' => $challan->student?->name ?? 'Student',
                    'student_code' => $challan->student?->student_id ?? '-',
                    'class_name' => trim(($challan->classRoom?->name ?? 'Class').' '.($challan->classRoom?->section ?? '')),
                    'session' => (string) $challan->session,
                    'month' => (string) $challan->month,
                    'month_label' => $this->service->monthLabel((string) $challan->month),
                    'due_date' => optional($challan->due_date)->toDateString(),
                    'arrears' => round((float) ($challan->arrears ?? 0), 2),
                    'late_fee' => round((float) ($challan->late_fee ?? 0), 2),
                    'remaining' => $remaining,
                ];
            })
            ->filter()
            ->values();

        $studentRows = $outstandingRows
            ->groupBy('student_id')
            ->map(function (Collection $rows) use ($today): array {
                $first = $rows->first();
                $dueDates = $rows->pluck('due_date')->filter()->sort()->values();

                return [
                    'student_id' => (int) ($first['student_id'] ?? 0),
                    'student_name' => (string) ($first['student_name'] ?? 'Student'),
                    'student_code' => (string) ($first['student_code'] ?? '-'),
                    'class_name' => (string) ($first['class_name'] ?? 'Class'),
                    'unpaid_challans' => $rows->count(),
                    'overdue_challans' => $rows->filter(fn (array $row): bool => (string) ($row['due_date'] ?? '') !== '' && (string) $row['due_date'] < $today)->count(),
                    'total_arrears' => round((float) $rows->sum('remaining'), 2),
                    'earliest_due_date' => $dueDates->first(),
                    'latest_due_date' => $dueDates->last(),
                ];
            })
            ->sortByDesc('total_arrears')
            ->values();

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $sessions = FeeChallan::query()
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->values()
            ->all();
        if (empty($sessions)) {
            $sessions = FeeStructure::query()
                ->select('session')
                ->distinct()
                ->orderByDesc('session')
                ->pluck('session')
                ->values()
                ->all();
        }
        if (empty($sessions)) {
            $sessions = $this->service->sessionOptions();
        }

        return view('modules.principal.fees.reports.arrears', [
            'classes' => $classes,
            'sessions' => $sessions,
            'filters' => [
                'session' => $filters['session'] ?? '',
                'class_id' => $filters['class_id'] ?? '',
                'search' => $filters['search'] ?? '',
            ],
            'summary' => [
                'students_with_arrears' => $studentRows->count(),
                'total_arrears' => round((float) $studentRows->sum('total_arrears'), 2),
                'total_unpaid_challans' => (int) $studentRows->sum('unpaid_challans'),
                'total_overdue_challans' => (int) $studentRows->sum('overdue_challans'),
            ],
            'rows' => $studentRows,
        ]);
    }

    private function applyFilters($query, array $filters)
    {
        return $query
            ->when(($filters['session'] ?? null) !== null && $filters['session'] !== '', function ($builder) use ($filters): void {
                $builder->where('session', (string) $filters['session']);
            })
            ->when(($filters['class_id'] ?? null) !== null && $filters['class_id'] !== '', function ($builder) use ($filters): void {
                $builder->where('class_id', (int) $filters['class_id']);
            })
            ->when(($filters['month'] ?? null) !== null && $filters['month'] !== '', function ($builder) use ($filters): void {
                $builder->where('month', (string) $filters['month']);
            });
    }
}
