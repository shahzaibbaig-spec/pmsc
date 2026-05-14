<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\LessonObservation;
use App\Models\User;
use App\Services\TeacherObservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LessonObservationController extends Controller
{
    public function __construct(private readonly TeacherObservationService $teacherObservationService)
    {
    }

    public function index(Request $request): View
    {
        $validated = $this->validatedFilters($request);
        $payload = $this->teacherObservationService->getLessonObservationsForObserver($request->user(), $validated);

        return view('principal.lesson-observations.index', $payload);
    }

    public function create(Request $request): View
    {
        $lookup = $this->teacherObservationService->getObservationFormLookups(
            $request->user(),
            $request->query('session')
        );

        return view('principal.lesson-observations.create', [
            'sessions' => $lookup['sessions'],
            'classes' => $lookup['classes'],
            'selected_session' => $lookup['session'],
            'teachers' => User::query()->role('Teacher')->orderBy('name')->get(['id', 'name']),
            'templateItems' => $this->teacherObservationService->lessonStandardTemplate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'observed_teacher_id' => ['required', 'integer', 'exists:users,id'],
            'session' => ['required', 'string', 'max:20'],
            'observation_date' => ['required', 'date'],
            'school' => ['nullable', 'string', 'max:190'],
            'subject_topic' => ['nullable', 'string', 'max:255'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'class_section' => ['nullable', 'string', 'max:80'],
            'no_of_students' => ['nullable', 'integer', 'min:0', 'max:500'],
            'learning_objectives' => ['nullable', 'string'],
            'previous_targets' => ['nullable', 'string'],
            'what_went_well' => ['nullable', 'string'],
            'even_better_if' => ['nullable', 'string'],
            'progress_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'observer_signature_acknowledged' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.area' => ['required', 'string', 'max:120'],
            'items.*.standard_text' => ['required', 'string'],
            'items.*.mark' => ['nullable', 'integer', Rule::in([0, 1])],
            'items.*.max_mark' => ['nullable', 'integer', 'min:1', 'max:1'],
            'items.*.comments' => ['nullable', 'string'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['observer_signature_acknowledged'] = (bool) $request->boolean('observer_signature_acknowledged');

        $observation = $this->teacherObservationService->createLessonObservation($validated, $request->user());

        return redirect()
            ->route('principal.lesson-observations.show', $observation)
            ->with('success', 'Lesson observation submitted and teacher notified for comments.');
    }

    public function show(int $observation, Request $request): View
    {
        $record = $this->teacherObservationService->findLessonObservationForUser($observation, $request->user());

        return view('principal.lesson-observations.show', [
            'observation' => $record,
        ]);
    }

    public function print(int $observation, Request $request): View
    {
        $record = $this->teacherObservationService->findLessonObservationForUser($observation, $request->user());

        return view('principal.lesson-observations.print', [
            'observation' => $record,
            'generated_by' => $request->user(),
            'generated_at' => now(),
        ]);
    }

    public function searchTeachers(Request $request)
    {
        $validated = $request->validate([
            'term' => ['required', 'string', 'min:2', 'max:80'],
            'session' => ['nullable', 'string', 'max:20'],
            'limit' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        return response()->json([
            'data' => $this->teacherObservationService->searchTeachersForObserver(
                $request->user(),
                (string) $validated['term'],
                [
                    'session' => $validated['session'] ?? null,
                    'limit' => $validated['limit'] ?? 20,
                ]
            ),
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
            'status' => ['nullable', Rule::in([LessonObservation::STATUS_SUBMITTED, LessonObservation::STATUS_COMMENTED])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
        ]);
    }
}
