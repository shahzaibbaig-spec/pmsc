<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentArrear;
use App\Modules\Fees\Services\FeeManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;

class StudentArrearController extends Controller
{
    public function __construct(private readonly FeeManagementService $service)
    {
    }

    public function index(Request $request): View
    {
        if (! $this->arrearTableAvailable()) {
            return redirect()
                ->route('principal.fees.reports.arrears')
                ->with('error', 'Student arrears table is missing. Please run migrations (php artisan migrate --force).');
        }

        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'status' => ['nullable', 'in:pending,partial,paid,all'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $sessions = StudentArrear::query()
            ->whereNotNull('session')
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->values()
            ->all();
        if (empty($sessions)) {
            $sessions = $this->service->sessionOptions();
        }

        $selectedSession = trim((string) ($filters['session'] ?? ''));
        $selectedClassId = $filters['class_id'] ?? '';
        $status = (string) ($filters['status'] ?? 'pending');
        $search = trim((string) ($filters['search'] ?? ''));

        $arrears = StudentArrear::query()
            ->with([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
                'addedBy:id,name',
            ])
            ->when($selectedSession !== '', function ($builder) use ($selectedSession): void {
                $builder->where(function ($nested) use ($selectedSession): void {
                    $nested->whereNull('session')
                        ->orWhere('session', $selectedSession);
                });
            })
            ->when($selectedClassId !== '' && $selectedClassId !== null, function ($builder) use ($selectedClassId): void {
                $builder->whereHas('student', function ($studentQuery) use ($selectedClassId): void {
                    $studentQuery->where('class_id', (int) $selectedClassId);
                });
            })
            ->when($status !== 'all', function ($builder) use ($status): void {
                $builder->where('status', $status);
            })
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($nested) use ($search): void {
                    $nested->where('title', 'like', '%'.$search.'%')
                        ->orWhereHas('student', function ($studentQuery) use ($search): void {
                            $studentQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('student_id', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->where('status', 'active')
            ->when($selectedClassId !== '' && $selectedClassId !== null, function ($builder) use ($selectedClassId): void {
                $builder->where('class_id', (int) $selectedClassId);
            })
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($nested) use ($search): void {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('student_id', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->limit(250)
            ->get(['id', 'name', 'student_id', 'class_id']);

        $visibleRows = $arrears->getCollection();
        $summary = [
            'total_amount' => round((float) $visibleRows->sum('amount'), 2),
            'total_paid' => round((float) $visibleRows->sum('paid_amount'), 2),
            'total_due' => round((float) $visibleRows->sum(function (StudentArrear $arrear): float {
                return max(round((float) $arrear->amount, 2) - round((float) $arrear->paid_amount, 2), 0);
            }), 2),
            'rows' => $visibleRows->count(),
        ];

        return view('modules.principal.fees.arrears.index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'students' => $students,
            'arrears' => $arrears,
            'summary' => $summary,
            'filters' => [
                'session' => $selectedSession,
                'class_id' => $selectedClassId,
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->arrearTableAvailable()) {
            return back()->with('error', 'Student arrears table is missing. Please run migrations (php artisan migrate --force).');
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'session' => ['nullable', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->addStudentArrear([
                'student_id' => (int) $validated['student_id'],
                'session' => trim((string) ($validated['session'] ?? '')) ?: null,
                'title' => (string) $validated['title'],
                'amount' => (float) $validated['amount'],
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ], (int) ($request->user()?->id ?? 0));
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('principal.fees.add-arrears.index', $request->only(['session', 'class_id', 'status', 'search']))
            ->with('status', 'Manual arrear added successfully.');
    }

    public function pay(Request $request, StudentArrear $studentArrear): RedirectResponse
    {
        if (! $this->arrearTableAvailable()) {
            return back()->with('error', 'Student arrears table is missing. Please run migrations (php artisan migrate --force).');
        }

        $validated = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->service->recordArrearPayment(
                $studentArrear,
                (float) $validated['amount_paid']
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Arrear payment recorded successfully.');
    }

    private function arrearTableAvailable(): bool
    {
        return Schema::hasTable('student_arrears');
    }
}
