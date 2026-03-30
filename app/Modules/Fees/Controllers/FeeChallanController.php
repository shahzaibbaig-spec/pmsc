<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeChallan;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Modules\Fees\Requests\GenerateFeeChallansRequest;
use App\Modules\Fees\Requests\RecordFeePaymentRequest;
use App\Modules\Fees\Services\FeeManagementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class FeeChallanController extends Controller
{
    public function __construct(private readonly FeeManagementService $service)
    {
    }

    public function create(Request $request): View
    {
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $sessions = $this->service->sessionOptions();

        return view('modules.principal.fees.challans.generate', [
            'classes' => $classes,
            'sessions' => $sessions,
            'defaultSession' => $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1)),
            'defaultMonth' => now()->format('Y-m'),
            'defaultDueDate' => now()->addDays(10)->toDateString(),
            'latestSummary' => session('latest_generation_summary'),
        ]);
    }

    public function index(Request $request): View
    {
        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $sessions = $this->availableSessions();
        $defaultSession = $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1));

        return view('modules.principal.fees.challans.index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'defaultSession' => $defaultSession,
            'defaultMonth' => now()->format('Y-m'),
            'defaultDueDate' => now()->addDays(10)->toDateString(),
            'canGenerateChallans' => $request->user()?->can('generate_fee_challans') ?? false,
            'canRecordPayment' => $request->user()?->can('record_fee_payment') ?? false,
            'canWaiveLateFee' => $request->user()?->can('record_fee_payment') ?? false,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->service->processLateFees();

        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'status' => ['nullable', 'in:unpaid,partial,partially_paid,paid'],
            'search' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = FeeChallan::query()
            ->with([
                'student:id,name,student_id',
                'classRoom:id,name,section',
            ])
            ->withSum('payments as paid_amount', 'amount_paid')
            ->when(($filters['session'] ?? null) !== null && $filters['session'] !== '', fn ($builder) => $builder->where('session', (string) $filters['session']))
            ->when(($filters['class_id'] ?? null) !== null && $filters['class_id'] !== '', fn ($builder) => $builder->where('class_id', (int) $filters['class_id']))
            ->when(($filters['month'] ?? null) !== null && $filters['month'] !== '', fn ($builder) => $builder->where('month', (string) $filters['month']))
            ->when(($filters['status'] ?? null) !== null && $filters['status'] !== '', function ($builder) use ($filters): void {
                $status = (string) $filters['status'];
                if (in_array($status, [FeeManagementService::STATUS_PARTIAL, 'partially_paid'], true)) {
                    $builder->whereIn('status', [FeeManagementService::STATUS_PARTIAL, 'partially_paid']);

                    return;
                }

                $builder->where('status', $status);
            })
            ->when(($filters['search'] ?? null) !== null && trim((string) $filters['search']) !== '', function ($builder) use ($filters): void {
                $search = trim((string) $filters['search']);
                $builder->where(function ($nested) use ($search): void {
                    $nested->where('challan_number', 'like', '%'.$search.'%')
                        ->orWhereHas('student', function ($studentQuery) use ($search): void {
                            $studentQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('student_id', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('issue_date')
            ->orderByDesc('id');

        $challans = $query->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
        $rows = $challans->getCollection()
            ->map(fn (FeeChallan $challan): array => $this->mapChallanRow($challan))
            ->values()
            ->all();

        return response()->json([
            'rows' => $rows,
            'meta' => [
                'current_page' => $challans->currentPage(),
                'last_page' => $challans->lastPage(),
                'per_page' => $challans->perPage(),
                'total' => $challans->total(),
                'from' => $challans->firstItem(),
                'to' => $challans->lastItem(),
            ],
        ]);
    }

    public function feeStructurePreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session' => ['required', 'string', 'max:20'],
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
        ]);

        $structures = FeeStructure::query()
            ->where('session', (string) $validated['session'])
            ->where('class_id', (int) $validated['class_id'])
            ->where('is_active', true)
            ->orderByDesc('is_monthly')
            ->orderBy('title')
            ->get(['id', 'title', 'amount', 'fee_type', 'is_monthly']);

        return response()->json([
            'heads' => $structures->map(fn (FeeStructure $structure): array => [
                'id' => (int) $structure->id,
                'title' => $structure->title,
                'amount' => round((float) $structure->amount, 2),
                'fee_type' => $structure->fee_type,
                'is_monthly' => (bool) $structure->is_monthly,
            ])->values()->all(),
            'summary' => [
                'monthly_total' => round((float) $structures->where('is_monthly', true)->sum('amount'), 2),
                'one_time_total' => round((float) $structures->where('is_monthly', false)->sum('amount'), 2),
                'total_heads' => $structures->count(),
            ],
        ]);
    }

    public function show(FeeChallan $feeChallan): JsonResponse
    {
        $feeChallan->loadMissing([
            'payments.receiver:id,name',
        ]);

        $payload = $this->service->challanPayload($feeChallan);
        $payments = $feeChallan->payments
            ->sortByDesc('payment_date')
            ->values()
            ->map(fn ($payment): array => [
                'id' => (int) $payment->id,
                'amount_paid' => round((float) $payment->amount_paid, 2),
                'payment_date' => optional($payment->payment_date)->format('Y-m-d'),
                'payment_method' => $payment->payment_method,
                'reference_no' => $payment->reference_no,
                'received_by' => $payment->receiver?->name,
                'notes' => $payment->notes,
            ])
            ->all();

        return response()->json([
            'challan_id' => (int) $feeChallan->id,
            'pdf_url' => route('principal.fees.challans.pdf', $feeChallan),
            'payload' => $payload,
            'payment_history' => $payments,
        ]);
    }

    public function store(GenerateFeeChallansRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        try {
            $summary = $this->service->generateClassChallans(
                (string) $validated['session'],
                (int) $validated['class_id'],
                (string) $validated['month'],
                (string) $validated['due_date'],
                (int) $request->user()->id
            );
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        if ($request->expectsJson()) {
            $message = sprintf(
                'Challans generated. Created: %d, Updated existing unpaid: %d, Existing skipped: %d, No billable heads: %d, Arrears added: %.2f.',
                $summary['created'],
                (int) ($summary['updated_existing'] ?? 0),
                $summary['skipped_existing'],
                $summary['skipped_no_items'],
                (float) ($summary['total_arrears_added'] ?? 0)
            );

            return response()->json([
                'message' => $message,
                'summary' => $summary,
            ]);
        }

        $message = sprintf(
            'Challans generated. Created: %d, Updated existing unpaid: %d, Existing skipped: %d, No billable heads: %d, Arrears added: %.2f.',
            $summary['created'],
            (int) ($summary['updated_existing'] ?? 0),
            $summary['skipped_existing'],
            $summary['skipped_no_items'],
            (float) ($summary['total_arrears_added'] ?? 0)
        );

        return redirect()
            ->route('principal.fees.challans.generate')
            ->with('status', $message)
            ->with('latest_generation_summary', $summary);
    }

    public function markPaid(RecordFeePaymentRequest $request, FeeChallan $feeChallan): JsonResponse
    {
        $validated = $request->validated();
        if ((int) $validated['challan_id'] !== (int) $feeChallan->id) {
            return response()->json([
                'message' => 'Selected challan does not match the payment request.',
            ], 422);
        }

        try {
            $this->service->recordPayment(
                $feeChallan,
                (float) $validated['amount_paid'],
                (string) $validated['payment_date'],
                (int) $request->user()->id,
                $validated['payment_method'] ?? null,
                $validated['reference_no'] ?? null,
                $validated['notes'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $fresh = FeeChallan::query()
            ->with(['student:id,name,student_id', 'classRoom:id,name,section'])
            ->withSum('payments as paid_amount', 'amount_paid')
            ->findOrFail($feeChallan->id);

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'row' => $this->mapChallanRow($fresh),
        ]);
    }

    public function waiveLateFee(Request $request, FeeChallan $feeChallan): JsonResponse
    {
        try {
            $this->service->waiveLateFee($feeChallan, (int) $request->user()->id);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $fresh = FeeChallan::query()
            ->with(['student:id,name,student_id', 'classRoom:id,name,section'])
            ->withSum('payments as paid_amount', 'amount_paid')
            ->findOrFail($feeChallan->id);

        return response()->json([
            'message' => 'Late fee waived successfully.',
            'row' => $this->mapChallanRow($fresh),
        ]);
    }

    public function pdf(FeeChallan $feeChallan): Response
    {
        $payload = $this->service->challanPayload($feeChallan);

        $pdf = Pdf::loadView('modules.reports.fee-challan', [
            'data' => $payload,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('fee_challan_'.$feeChallan->challan_number.'.pdf');
    }

    public function classPdf(Request $request): Response
    {
        $validated = $request->validate([
            'session' => ['required', 'string', 'max:20'],
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $challans = FeeChallan::query()
            ->with([
                'student:id,name,student_id,class_id',
                'classRoom:id,name,section',
                'items:id,fee_challan_id,title,fee_type,amount',
                'payments:id,fee_challan_id,amount_paid,payment_date',
            ])
            ->where('session', (string) $validated['session'])
            ->where('class_id', (int) $validated['class_id'])
            ->where('month', (string) $validated['month'])
            ->orderBy('student_id')
            ->orderBy('id')
            ->get();

        if ($challans->isEmpty()) {
            abort(404, 'No challans found for selected class, session, and month.');
        }

        $payloads = $challans
            ->map(fn (FeeChallan $challan): array => $this->service->challanPayload($challan))
            ->values();

        $first = $challans->first();
        $className = trim(($first?->classRoom?->name ?? 'Class').' '.($first?->classRoom?->section ?? ''));

        $pdf = Pdf::loadView('modules.reports.fee-challans-class', [
            'payloads' => $payloads,
            'meta' => [
                'class_name' => $className,
                'session' => (string) $validated['session'],
                'month' => (string) $validated['month'],
                'month_label' => $this->service->monthLabel((string) $validated['month']),
                'total_challans' => $challans->count(),
            ],
        ])->setPaper('a4', 'portrait');

        return $pdf->stream(sprintf(
            'fee_challans_%s_%s_%s.pdf',
            str_replace(' ', '_', strtolower($className)),
            str_replace('-', '', (string) $validated['session']),
            str_replace('-', '', (string) $validated['month'])
        ));
    }

    private function availableSessions(): array
    {
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

        return empty($sessions) ? $this->service->sessionOptions() : $sessions;
    }

    private function mapChallanRow(FeeChallan $challan): array
    {
        $paid = round((float) ($challan->paid_amount ?? 0), 2);
        $total = round((float) $challan->total_amount, 2);
        $remaining = round(max($total - $paid, 0), 2);
        $arrears = round((float) ($challan->arrears ?? 0), 2);
        $lateFee = round((float) ($challan->late_fee ?? 0), 2);
        $feeAmount = round(max($total - $arrears - $lateFee, 0), 2);
        $status = $this->service->normalizeStatus((string) $challan->status);

        return [
            'id' => (int) $challan->id,
            'challan_number' => $challan->challan_number,
            'student_name' => $challan->student?->name ?? 'Student',
            'student_id' => $challan->student?->student_id ?? '-',
            'class_name' => trim(($challan->classRoom?->name ?? 'Class').' '.($challan->classRoom?->section ?? '')),
            'session' => $challan->session,
            'month' => $challan->month,
            'month_label' => $this->service->monthLabel((string) $challan->month),
            'issue_date' => optional($challan->issue_date)->format('Y-m-d'),
            'due_date' => optional($challan->due_date)->format('Y-m-d'),
            'fee_amount' => $feeAmount,
            'arrears' => $arrears,
            'late_fee' => $lateFee,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'status' => $status,
            'late_fee_waived_at' => optional($challan->late_fee_waived_at)->format('Y-m-d H:i:s'),
            'pdf_url' => route('principal.fees.challans.pdf', $challan),
        ];
    }
}
