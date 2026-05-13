<?php

namespace App\Http\Controllers\Principal;

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

        return view('principal.sports-observations.index', $this->sportsObservationService->getDailyObservationsForPrincipal($validated));
    }

    public function daily(Request $request): View
    {
        $validated = $this->validatedFilters($request);

        if (! isset($validated['date'])) {
            $validated['date'] = now()->toDateString();
        }

        return view('principal.sports-observations.daily', $this->sportsObservationService->getDailyObservationsForPrincipal($validated));
    }

    public function print(Request $request): View
    {
        $validated = $this->validatedFilters($request);

        if (! isset($validated['date'])) {
            $validated['date'] = now()->toDateString();
        }

        $payload = $this->sportsObservationService->getDailyObservationsForPrincipal([
            ...$validated,
            'paginate' => false,
        ]);

        return view('principal.sports-observations.print', [
            ...$payload,
            'generated_by' => $request->user(),
            'generated_at' => now(),
        ]);
    }

    public function acknowledge(StudentSportsObservation $observation, Request $request): RedirectResponse
    {
        $this->sportsObservationService->markAcknowledged($observation, $request->user());

        return back()->with('success', 'Observation marked as acknowledged.');
    }

    public function resolve(StudentSportsObservation $observation, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->sportsObservationService->markResolved($observation, $validated, $request->user());

        return back()->with('success', 'Observation marked as resolved.');
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
