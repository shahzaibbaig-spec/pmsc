<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FeeChallan;
use App\Models\FeeDefaulter;
use App\Models\SchoolClass;
use App\Modules\Fees\Services\FeeDefaulterService;
use App\Modules\Fees\Services\FeeManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;

class FeeDefaulterController extends Controller
{
    public function __construct(
        private readonly FeeDefaulterService $defaulterService,
        private readonly FeeManagementService $feeManagementService,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->tablesAvailable()) {
            return redirect()
                ->route('principal.fees.reports.arrears')
                ->with('error', 'Fee defaulter tables are missing. Please run migrations (php artisan migrate --force).');
        }

        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'status' => ['nullable', 'in:active,cleared,all'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $selectedSession = trim((string) ($filters['session'] ?? ''));
        if ($selectedSession === '') {
            $selectedSession = $this->defaulterService->sessionFromDate();
        }

        $syncSummary = $this->defaulterService->processSession($selectedSession);

        $selectedClassId = $filters['class_id'] ?? '';
        $status = (string) ($filters['status'] ?? 'active');
        $search = trim((string) ($filters['search'] ?? ''));

        $query = FeeDefaulter::query()
            ->with([
                'student:id,name,student_id,class_id,status',
                'student.classRoom:id,name,section',
            ])
            ->where('session', $selectedSession)
            ->when($status === 'active', fn ($builder) => $builder->where('is_active', true))
            ->when($status === 'cleared', fn ($builder) => $builder->where('is_active', false))
            ->when($selectedClassId !== '' && $selectedClassId !== null, function ($builder) use ($selectedClassId): void {
                $builder->whereHas('student', function ($studentQuery) use ($selectedClassId): void {
                    $studentQuery->where('class_id', (int) $selectedClassId);
                });
            })
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->whereHas('student', function ($studentQuery) use ($search): void {
                    $studentQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('student_id', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('is_active')
            ->orderByDesc('total_due')
            ->orderBy('oldest_due_date')
            ->orderBy('id');

        $defaulters = $query->paginate(20)->withQueryString();
        $visibleRows = $defaulters->getCollection();

        $summary = [
            'rows' => $visibleRows->count(),
            'active_rows' => $visibleRows->where('is_active', true)->count(),
            'cleared_rows' => $visibleRows->where('is_active', false)->count(),
            'visible_due_total' => round((float) $visibleRows->where('is_active', true)->sum('total_due'), 2),
            'session_active_total' => (int) ($syncSummary['active'] ?? 0),
            'session_marked' => (int) ($syncSummary['marked'] ?? 0),
            'session_cleared' => (int) ($syncSummary['cleared'] ?? 0),
        ];

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        return view('modules.principal.fees.defaulters.index', [
            'defaulters' => $defaulters,
            'classes' => $classes,
            'sessions' => $this->feeManagementService->sessionOptions(),
            'filters' => [
                'session' => $selectedSession,
                'class_id' => $selectedClassId,
                'status' => $status,
                'search' => $search,
            ],
            'summary' => $summary,
            'blockTypeOptions' => [
                'result_card' => 'Result Card',
                'admit_card' => 'Admit Card',
                'id_card' => 'ID Card',
            ],
            'canOverride' => $request->user()?->hasAnyRole(['Admin', 'Principal']) ?? false,
        ]);
    }

    public function sendReminder(Request $request, FeeDefaulter $feeDefaulter): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $result = $this->defaulterService->sendInAppReminder(
                (int) $feeDefaulter->student_id,
                (string) $feeDefaulter->session,
                (int) ($request->user()?->id ?? 0),
                null,
                $validated['title'] ?? null,
                $validated['message'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with(
            'status',
            sprintf('Fee reminder sent successfully to %d user(s).', (int) ($result['notified_users'] ?? 0))
        );
    }

    public function addNote(Request $request, FeeDefaulter $feeDefaulter): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $feeDefaulter->forceFill([
            'remarks' => trim((string) ($validated['remarks'] ?? '')) ?: null,
        ])->save();

        return back()->with('status', 'Defaulter note updated successfully.');
    }

    public function createOverride(Request $request, FeeDefaulter $feeDefaulter): RedirectResponse
    {
        $validated = $request->validate([
            'block_type' => ['required', 'in:result_card,admit_card,id_card'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->defaulterService->upsertOverride(
                (int) $feeDefaulter->student_id,
                (string) $feeDefaulter->session,
                (string) $validated['block_type'],
                true,
                $validated['reason'] ?? null,
                (int) ($request->user()?->id ?? 0),
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Manual block override saved successfully.');
    }

    public function waiveLateFee(Request $request, FeeDefaulter $feeDefaulter): RedirectResponse
    {
        $challans = FeeChallan::query()
            ->where('student_id', (int) $feeDefaulter->student_id)
            ->where('session', (string) $feeDefaulter->session)
            ->whereIn('status', [
                FeeManagementService::STATUS_UNPAID,
                FeeManagementService::STATUS_PARTIAL,
                'partially_paid',
            ])
            ->where('late_fee', '>', 0)
            ->get();

        if ($challans->isEmpty()) {
            return back()->with('error', 'No unpaid challans with late fee were found for this student.');
        }

        $waivedCount = 0;
        foreach ($challans as $challan) {
            try {
                $this->feeManagementService->waiveLateFee($challan, (int) ($request->user()?->id ?? 0));
                $waivedCount++;
            } catch (RuntimeException) {
                continue;
            }
        }

        $this->defaulterService->syncStudentForSession((int) $feeDefaulter->student_id, (string) $feeDefaulter->session);

        if ($waivedCount === 0) {
            return back()->with('error', 'No late fee could be waived for this student.');
        }

        return back()->with('status', sprintf('Late fee waived on %d challan(s).', $waivedCount));
    }

    private function tablesAvailable(): bool
    {
        return Schema::hasTable('fee_defaulters')
            && Schema::hasTable('fee_reminders')
            && Schema::hasTable('fee_block_overrides');
    }
}
