<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\CreatePromotionCampaignRequest;
use App\Http\Requests\Promotions\SaveTeacherPromotionDecisionsRequest;
use App\Http\Requests\Promotions\SubmitPromotionCampaignRequest;
use App\Models\Exam;
use App\Models\PromotionCampaign;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\PromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class TeacherPromotionController extends Controller
{
    public function __construct(private readonly PromotionService $promotionService)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $teacher = $this->teacherForUser((int) ($request->user()?->id ?? 0));
        if (! $teacher) {
            return redirect()
                ->route('teacher.dashboard')
                ->with('error', 'Teacher profile was not found for this account.');
        }

        $sessions = $this->sessionOptions();
        $selectedFromSession = $request->filled('from_session')
            ? (string) $request->query('from_session')
            : ($sessions[0] ?? now()->year.'-'.(now()->year + 1));
        $selectedToSession = $request->filled('to_session')
            ? (string) $request->query('to_session')
            : $this->nextSession($selectedFromSession);

        $classAssignments = TeacherAssignment::query()
            ->with('classRoom:id,name,section')
            ->where('teacher_id', (int) $teacher->id)
            ->where('is_class_teacher', true)
            ->where('session', $selectedFromSession)
            ->orderBy('class_id')
            ->get(['id', 'class_id', 'session'])
            ->unique('class_id')
            ->values();

        $campaigns = PromotionCampaign::query()
            ->with([
                'classRoom:id,name,section',
                'creator:id,name',
                'approver:id,name',
            ])
            ->where('created_by', (int) ($request->user()?->id ?? 0))
            ->when($selectedFromSession !== '', function ($query) use ($selectedFromSession): void {
                $query->where('from_session', $selectedFromSession);
            })
            ->orderByRaw("
                CASE status
                    WHEN 'submitted' THEN 1
                    WHEN 'draft' THEN 2
                    WHEN 'rejected' THEN 3
                    WHEN 'approved' THEN 4
                    WHEN 'executed' THEN 5
                    ELSE 6
                END
            ")
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('modules.teacher.promotions.index', [
            'sessions' => $sessions,
            'selectedFromSession' => $selectedFromSession,
            'selectedToSession' => $selectedToSession,
            'classAssignments' => $classAssignments,
            'campaigns' => $campaigns,
        ]);
    }

    public function store(CreatePromotionCampaignRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $campaign = $this->promotionService->createCampaign([
                'from_session' => (string) $validated['from_session'],
                'to_session' => (string) $validated['to_session'],
                'class_id' => (int) $validated['class_id'],
            ], $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        } catch (Throwable) {
            return back()->with('error', 'Unable to create promotion campaign.')->withInput();
        }

        return redirect()
            ->route('teacher.promotions.show', $campaign)
            ->with('status', 'Promotion campaign is ready. Save decisions and submit to principal.');
    }

    public function show(Request $request, PromotionCampaign $promotionCampaign): View|RedirectResponse
    {
        $this->authorizeTeacherCampaignAccess((int) ($request->user()?->id ?? 0), $promotionCampaign);

        try {
            $payload = $this->promotionService->loadEligibleStudents($promotionCampaign);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('teacher.promotions.index', [
                    'from_session' => $promotionCampaign->from_session,
                    'to_session' => $promotionCampaign->to_session,
                ])
                ->with('error', $exception->getMessage());
        }

        $campaign = $payload['campaign'];

        return view('modules.teacher.promotions.show', [
            'campaign' => $campaign,
            'rows' => $payload['rows'],
            'summary' => $payload['summary'],
            'isTerminalClass' => (bool) ($payload['is_terminal_class'] ?? false),
            'nextClassLabel' => $payload['next_class_label'],
            'isEditable' => in_array($campaign->status, [PromotionCampaign::STATUS_DRAFT, PromotionCampaign::STATUS_REJECTED], true),
        ]);
    }

    public function update(
        SaveTeacherPromotionDecisionsRequest $request,
        PromotionCampaign $promotionCampaign
    ): RedirectResponse {
        $this->authorizeTeacherCampaignAccess((int) ($request->user()?->id ?? 0), $promotionCampaign);

        try {
            $this->promotionService->saveTeacherDecisions(
                $promotionCampaign,
                $request->validated('rows', []),
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        } catch (Throwable) {
            return back()->with('error', 'Unable to save teacher decisions.')->withInput();
        }

        return back()->with('status', 'Teacher decisions saved successfully.');
    }

    public function submit(SubmitPromotionCampaignRequest $request, PromotionCampaign $promotionCampaign): RedirectResponse
    {
        $this->authorizeTeacherCampaignAccess((int) ($request->user()?->id ?? 0), $promotionCampaign);

        try {
            $this->promotionService->submitToPrincipal($promotionCampaign, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable) {
            return back()->with('error', 'Unable to submit campaign to principal.');
        }

        return back()->with('status', 'Promotion campaign submitted to principal for review.');
    }

    private function teacherForUser(int $userId): ?Teacher
    {
        if ($userId <= 0) {
            return null;
        }

        return Teacher::query()
            ->where('user_id', $userId)
            ->first(['id', 'user_id', 'teacher_id']);
    }

    private function authorizeTeacherCampaignAccess(int $userId, PromotionCampaign $campaign): void
    {
        $teacher = $this->teacherForUser($userId);
        if (! $teacher) {
            abort(403, 'Teacher profile not found.');
        }

        $allowed = TeacherAssignment::query()
            ->where('teacher_id', (int) $teacher->id)
            ->where('class_id', (int) $campaign->class_id)
            ->where('session', (string) $campaign->from_session)
            ->where('is_class_teacher', true)
            ->exists();

        if (! $allowed) {
            abort(403, 'Only assigned class teacher can access this campaign.');
        }
    }

    /**
     * @return array<int, string>
     */
    private function sessionOptions(): array
    {
        $sessions = Exam::query()
            ->where('exam_type', 'final_term')
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->filter(fn ($session): bool => trim((string) $session) !== '')
            ->values()
            ->all();

        if ($sessions !== []) {
            return $sessions;
        }

        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);
        $fallback = [];
        for ($year = $startYear - 1; $year <= $startYear + 2; $year++) {
            $fallback[] = $year.'-'.($year + 1);
        }

        return array_reverse($fallback);
    }

    private function nextSession(string $fromSession): string
    {
        if (preg_match('/^(\d{4})-(\d{4})$/', $fromSession, $matches) === 1) {
            $startYear = (int) $matches[2];

            return $startYear.'-'.($startYear + 1);
        }

        $year = (int) now()->year;

        return $year.'-'.($year + 1);
    }
}
