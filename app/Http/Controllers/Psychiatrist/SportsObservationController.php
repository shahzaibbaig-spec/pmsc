<?php

namespace App\Http\Controllers\Psychiatrist;

use App\Http\Controllers\Controller;
use App\Models\StudentSportsObservation;
use App\Services\SportsObservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SportsObservationController extends Controller
{
    public function __construct(private readonly SportsObservationService $sportsObservationService)
    {
    }

    public function index(Request $request): View
    {
        $validated = $this->validatedFilters($request);

        return view('psychiatrist.sports-observations.index', $this->sportsObservationService->getDailyObservationsForPrincipal($validated));
    }

    public function show(StudentSportsObservation $observation): View
    {
        $observation->load([
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'sportsTeacher:id,name',
            'resolvedBy:id,name',
            'psychiatristReviewedBy:id,name',
            'issues:id,student_sports_observation_id,issue_type,issue_label,auto_message',
        ]);

        return view('psychiatrist.sports-observations.show', [
            'observation' => $observation,
        ]);
    }

    public function feedback(StudentSportsObservation $observation, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'psychiatrist_feedback' => ['nullable', 'string', 'max:2500'],
        ]);

        $this->sportsObservationService->updatePsychiatristFeedback($observation, $validated, $request->user());

        return back()->with('success', 'Feedback saved successfully.');
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
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'issue_type' => ['nullable', Rule::in(array_keys(StudentSportsObservation::ISSUE_LABELS))],
            'sports_teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(StudentSportsObservation::STATUSES)],
            'severity' => ['nullable', Rule::in(StudentSportsObservation::SEVERITIES)],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
        ]);
    }
}

