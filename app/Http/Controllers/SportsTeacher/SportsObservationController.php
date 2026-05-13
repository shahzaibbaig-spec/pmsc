<?php

namespace App\Http\Controllers\SportsTeacher;

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
        $validated = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::in(StudentSportsObservation::STATUSES)],
            'severity' => ['nullable', Rule::in(StudentSportsObservation::SEVERITIES)],
            'issue_type' => ['nullable', Rule::in(array_keys(StudentSportsObservation::ISSUE_LABELS))],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
        ]);

        return view('sports-teacher.observations.index', $this->sportsObservationService->getObservationsForSportsTeacher(
            $request->user(),
            $validated
        ));
    }

    public function create(Request $request): View
    {
        $validated = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'date' => ['nullable', 'date'],
        ]);

        return view('sports-teacher.observations.create', $this->sportsObservationService->getObservationsForSportsTeacher(
            $request->user(),
            [
                'session' => $validated['session'] ?? null,
                'date' => $validated['date'] ?? null,
                'per_page' => 10,
            ]
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'session' => ['nullable', 'string', 'max:20'],
            'observation_date' => ['required', 'date'],
            'issue_types' => ['required_without:issue_type', 'array', 'min:1'],
            'issue_types.*' => [Rule::in(array_keys(StudentSportsObservation::ISSUE_LABELS))],
            'issue_type' => ['nullable', Rule::in(array_keys(StudentSportsObservation::ISSUE_LABELS))],
            'severity' => ['required', Rule::in(StudentSportsObservation::SEVERITIES)],
            'custom_note' => ['nullable', 'string', 'max:500'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ]);

        $observation = $this->sportsObservationService->createObservation($validated, $request->user());

        return redirect()
            ->route('sports-teacher.observations.show', $observation)
            ->with('success', 'Sports observation submitted and notifications sent successfully.');
    }

    public function show(StudentSportsObservation $observation, Request $request): View
    {
        if ((int) $observation->sports_teacher_id !== (int) $request->user()->id) {
            abort(403, 'You are not allowed to view this observation.');
        }

        $observation->load([
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'sportsTeacher:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
            'resolvedBy:id,name',
            'issues:id,student_sports_observation_id,issue_type,issue_label,auto_message',
        ]);

        return view('sports-teacher.observations.show', [
            'observation' => $observation,
            'issueOptions' => StudentSportsObservation::ISSUE_LABELS,
            'severityOptions' => StudentSportsObservation::SEVERITIES,
            'statusOptions' => StudentSportsObservation::STATUSES,
        ]);
    }
}
