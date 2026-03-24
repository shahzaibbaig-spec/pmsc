<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExamRoom;
use App\Models\ExamSeatAssignment;
use App\Models\ExamSeatingPlan;
use App\Models\ExamSession;
use App\Models\SchoolClass;
use App\Modules\Exams\Services\ExamSeatingPlanService;
use App\Modules\Reports\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ExamSeatingPlanController extends Controller
{
    public function __construct(
        private readonly ExamSeatingPlanService $seatingPlanService,
        private readonly ReportService $reportService,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->seatingTablesReady()) {
            return redirect()
                ->route('dashboard')
                ->with('error', $this->missingTablesMessage());
        }

        $filters = $request->validate([
            'exam_session_id' => ['nullable', 'integer', 'exists:exam_sessions,id'],
        ]);

        $examSessions = ExamSession::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'name', 'session', 'start_date', 'end_date']);

        $selectedExamSessionId = (int) ($filters['exam_session_id'] ?? 0);
        if ($selectedExamSessionId <= 0) {
            $selectedExamSessionId = (int) ($examSessions->first()->id ?? 0);
        }

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $classMap = $classes->mapWithKeys(function (SchoolClass $classRoom): array {
            $name = trim((string) $classRoom->name.' '.(string) ($classRoom->section ?? ''));

            return [(int) $classRoom->id => ($name !== '' ? $name : 'Class '.(int) $classRoom->id)];
        });

        $rooms = ExamRoom::query()
            ->orderBy('name')
            ->get(['id', 'name', 'capacity', 'is_active']);

        $plans = ExamSeatingPlan::query()
            ->with([
                'examSession:id,name,session,start_date,end_date',
                'generator:id,name',
            ])
            ->when($selectedExamSessionId > 0, function ($query) use ($selectedExamSessionId): void {
                $query->where('exam_session_id', $selectedExamSessionId);
            })
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('modules.principal.exams.seating-plans.index', [
            'examSessions' => $examSessions,
            'selectedExamSessionId' => $selectedExamSessionId > 0 ? $selectedExamSessionId : '',
            'classes' => $classes,
            'classMap' => $classMap,
            'rooms' => $rooms,
            'plans' => $plans,
        ]);
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        if (! $this->seatingTablesReady()) {
            return back()->with('error', $this->missingTablesMessage());
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'capacity' => ['required', 'integer', 'min:1', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $this->seatingPlanService->createRoom([
                'name' => $validated['name'],
                'capacity' => (int) $validated['capacity'],
                'is_active' => $request->boolean('is_active'),
            ]);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        } catch (Throwable) {
            return back()
                ->with('error', 'Unable to create exam room. Ensure room name is unique.')
                ->withInput();
        }

        return back()->with('status', 'Exam room created successfully.');
    }

    public function generate(Request $request): RedirectResponse
    {
        if (! $this->seatingTablesReady()) {
            return back()->with('error', $this->missingTablesMessage());
        }

        $validated = $request->validate([
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'class_ids' => ['required', 'array', 'min:1'],
            'class_ids.*' => ['integer', 'exists:school_classes,id'],
            'is_randomized' => ['nullable', 'boolean'],
        ]);

        try {
            $plan = $this->seatingPlanService->generatePlan(
                (int) $validated['exam_session_id'],
                $validated['class_ids'],
                $request->boolean('is_randomized'),
                (int) ($request->user()?->id ?? 0) ?: null
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        } catch (Throwable) {
            return back()->with('error', 'Unable to generate seating plan. Please try again.')->withInput();
        }

        return redirect()
            ->route('principal.exams.seating-plans.show', $plan)
            ->with('status', 'Seating plan generated successfully.');
    }

    public function show(ExamSeatingPlan $examSeatingPlan): View|RedirectResponse
    {
        if (! $this->seatingTablesReady()) {
            return redirect()
                ->route('principal.exams.seating-plans.index')
                ->with('error', $this->missingTablesMessage());
        }

        $examSeatingPlan->load([
            'examSession:id,name,session,start_date,end_date',
            'generator:id,name',
            'seatAssignments' => function ($query): void {
                $query->with([
                    'student:id,name,student_id,father_name,class_id',
                    'classRoom:id,name,section',
                    'room:id,name,capacity',
                ]);
            },
        ]);

        $classLabels = $this->classLabelsFromIds((array) $examSeatingPlan->class_ids);
        $roomGroups = $this->groupAssignmentsByRoom($examSeatingPlan);

        return view('modules.principal.exams.seating-plans.show', [
            'plan' => $examSeatingPlan,
            'classLabels' => $classLabels,
            'roomGroups' => $roomGroups,
        ]);
    }

    public function print(ExamSeatingPlan $examSeatingPlan): View|RedirectResponse
    {
        if (! $this->seatingTablesReady()) {
            return redirect()
                ->route('principal.exams.seating-plans.index')
                ->with('error', $this->missingTablesMessage());
        }

        $examSeatingPlan->load([
            'examSession:id,name,session,start_date,end_date',
            'generator:id,name',
            'seatAssignments' => function ($query): void {
                $query->with([
                    'student:id,name,student_id,class_id,father_name',
                    'classRoom:id,name,section',
                    'room:id,name,capacity',
                ]);
            },
        ]);

        return view('modules.reports.exam-seating-plan', [
            'school' => $this->reportService->schoolMeta(),
            'plan' => $examSeatingPlan,
            'classLabels' => $this->classLabelsFromIds((array) $examSeatingPlan->class_ids),
            'roomGroups' => $this->groupAssignmentsByRoom($examSeatingPlan),
        ]);
    }

    public function seatSlip(ExamSeatingPlan $examSeatingPlan, ExamSeatAssignment $examSeatAssignment): View|RedirectResponse
    {
        if (! $this->seatingTablesReady()) {
            return redirect()
                ->route('principal.exams.seating-plans.index')
                ->with('error', $this->missingTablesMessage());
        }

        if ((int) $examSeatAssignment->exam_seating_plan_id !== (int) $examSeatingPlan->id) {
            abort(404);
        }

        $examSeatingPlan->loadMissing([
            'examSession:id,name,session,start_date,end_date',
            'generator:id,name',
        ]);

        $examSeatAssignment->load([
            'student:id,name,student_id,father_name,class_id',
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'room:id,name,capacity',
        ]);

        return view('modules.reports.exam-seat-slip', [
            'school' => $this->reportService->schoolMeta(),
            'plan' => $examSeatingPlan,
            'assignment' => $examSeatAssignment,
        ]);
    }

    /**
     * @return Collection<int, array{room:\App\Models\ExamRoom|null,used_seats:int,capacity:int,assignments:Collection<int, ExamSeatAssignment>}>
     */
    private function groupAssignmentsByRoom(ExamSeatingPlan $plan): Collection
    {
        return $plan->seatAssignments
            ->groupBy('exam_room_id')
            ->map(function (Collection $rows): array {
                /** @var ExamSeatAssignment|null $first */
                $first = $rows->first();
                $room = $first?->room;

                return [
                    'room' => $room,
                    'used_seats' => $rows->count(),
                    'capacity' => (int) ($room?->capacity ?? 0),
                    'assignments' => $rows->sortBy('seat_number')->values(),
                ];
            })
            ->sortBy(fn (array $row): string => strtolower((string) ($row['room']?->name ?? '')))
            ->values();
    }

    /**
     * @param array<int, mixed> $classIds
     * @return array<int, string>
     */
    private function classLabelsFromIds(array $classIds): array
    {
        $normalizedIds = collect($classIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($normalizedIds->isEmpty()) {
            return [];
        }

        return SchoolClass::query()
            ->whereIn('id', $normalizedIds->all())
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section'])
            ->map(fn (SchoolClass $classRoom): string => trim((string) $classRoom->name.' '.(string) ($classRoom->section ?? '')))
            ->map(fn (string $label): string => $label !== '' ? $label : 'Class')
            ->values()
            ->all();
    }

    private function seatingTablesReady(): bool
    {
        return Schema::hasTable('exam_sessions')
            && Schema::hasTable('exam_rooms')
            && Schema::hasTable('exam_seating_plans')
            && Schema::hasTable('exam_seat_assignments');
    }

    private function missingTablesMessage(): string
    {
        return 'Exam seating plan tables are missing on server. Please run latest migrations: php artisan migrate --force';
    }
}
