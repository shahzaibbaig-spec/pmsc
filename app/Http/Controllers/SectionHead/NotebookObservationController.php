<?php

namespace App\Http\Controllers\SectionHead;

use App\Http\Controllers\Controller;
use App\Models\NotebookObservation;
use App\Models\User;
use App\Services\TeacherObservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotebookObservationController extends Controller
{
    public function __construct(private readonly TeacherObservationService $teacherObservationService)
    {
    }

    public function index(Request $request): View
    {
        $validated = $this->validatedFilters($request);
        $payload = $this->teacherObservationService->getNotebookObservationsForObserver($request->user(), $validated);

        return view('principal.notebook-observations.index', [
            ...$payload,
            'routeBase' => 'section-head.notebook-observations',
            'panelLabel' => 'Section Head',
        ]);
    }

    public function create(Request $request): View
    {
        $lookup = $this->teacherObservationService->getObservationFormLookups(
            $request->user(),
            $request->query('session')
        );

        return view('principal.notebook-observations.create', [
            'sessions' => $lookup['sessions'],
            'classes' => $lookup['classes'],
            'subjects' => $lookup['subjects'],
            'selected_session' => $lookup['session'],
            'teachers' => User::query()->role('Teacher')->orderBy('name')->get(['id', 'name']),
            'templateItems' => $this->teacherObservationService->notebookChecklistTemplate(),
            'responses' => NotebookObservation::RESPONSES,
            'routeBase' => 'section-head.notebook-observations',
            'panelLabel' => 'Section Head',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'observed_teacher_id' => ['required', 'integer', 'exists:users,id'],
            'session' => ['required', 'string', 'max:20'],
            'observation_date' => ['required', 'date'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'class_section' => ['nullable', 'string', 'max:80'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'total_students' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'notebooks_provided' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'covered_notebooks' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'uncovered_notebooks' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'well_maintained' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'general_comments' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.checklist_text' => ['required', 'string'],
            'items.*.response' => ['nullable', Rule::in(NotebookObservation::RESPONSES)],
            'items.*.comments' => ['nullable', 'string'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $observation = $this->teacherObservationService->createNotebookObservation($validated, $request->user());

        return redirect()
            ->route('section-head.notebook-observations.show', $observation)
            ->with('success', 'Notebook observation submitted and teacher notified for comments.');
    }

    public function show(int $observation, Request $request): View
    {
        $record = $this->teacherObservationService->findNotebookObservationForUser($observation, $request->user());

        return view('principal.notebook-observations.show', [
            'observation' => $record,
            'responses' => NotebookObservation::RESPONSES,
            'routeBase' => 'section-head.notebook-observations',
            'panelLabel' => 'Section Head',
        ]);
    }

    public function print(int $observation, Request $request): View
    {
        $record = $this->teacherObservationService->findNotebookObservationForUser($observation, $request->user());

        return view('principal.notebook-observations.print', [
            'observation' => $record,
            'generated_by' => $request->user(),
            'generated_at' => now(),
            'routeBase' => 'section-head.notebook-observations',
            'panelLabel' => 'Section Head',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'observed_teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'observer_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in([NotebookObservation::STATUS_SUBMITTED, NotebookObservation::STATUS_COMMENTED])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
        ]);
    }
}
