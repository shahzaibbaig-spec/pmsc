<?php

namespace App\Http\Controllers\Warden;

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
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'issue_type' => ['nullable', Rule::in(array_keys(StudentSportsObservation::ISSUE_LABELS))],
            'status' => ['nullable', Rule::in(StudentSportsObservation::STATUSES)],
            'severity' => ['nullable', Rule::in(StudentSportsObservation::SEVERITIES)],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
        ]);

        return view('warden.sports-observations.index', $this->sportsObservationService->getDailyObservationsForPrincipal($validated));
    }

    public function acknowledge(StudentSportsObservation $observation, Request $request): RedirectResponse
    {
        $this->sportsObservationService->markAcknowledged($observation, $request->user());

        return back()->with('success', 'Observation marked as acknowledged.');
    }
}
