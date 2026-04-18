<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\ApplyPrincipalPromotionGroupActionRequest;
use App\Http\Requests\Promotions\CreatePrincipalPromotionCampaignRequest;
use App\Http\Requests\Promotions\ExecutePromotionCampaignRequest;
use App\Http\Requests\Promotions\PrincipalApproveCampaignRequest;
use App\Http\Requests\Promotions\PrincipalRejectCampaignRequest;
use App\Http\Requests\Promotions\PrincipalReviewPromotionRequest;
use App\Models\PromotionCampaign;
use App\Models\SchoolClass;
use App\Services\PromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class PrincipalPromotionController extends Controller
{
    private const REQUIRED_PROMOTION_SESSION = '2026-2027';

    public function __construct(private readonly PromotionService $promotionService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:draft,submitted,approved,rejected,executed,all'],
            'from_session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'to_session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $selectedStatus = (string) ($filters['status'] ?? 'all');
        $fromSession = trim((string) ($filters['from_session'] ?? ''));
        $toSession = trim((string) ($filters['to_session'] ?? ''));
        $classId = isset($filters['class_id']) ? (int) $filters['class_id'] : null;
        $search = trim((string) ($filters['search'] ?? ''));

        $campaigns = PromotionCampaign::query()
            ->with([
                'classRoom:id,name,section',
                'creator:id,name',
                'approver:id,name',
            ])
            ->when($selectedStatus !== 'all', function ($query) use ($selectedStatus): void {
                $query->where('status', $selectedStatus);
            })
            ->when($fromSession !== '', function ($query) use ($fromSession): void {
                $query->where('from_session', $fromSession);
            })
            ->when($toSession !== '', function ($query) use ($toSession): void {
                $query->where('to_session', $toSession);
            })
            ->when($classId !== null, function ($query) use ($classId): void {
                $query->where('class_id', $classId);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->whereHas('classRoom', function ($classQuery) use ($search): void {
                        $classQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('section', 'like', '%'.$search.'%');
                    })
                        ->orWhereHas('creator', function ($creatorQuery) use ($search): void {
                            $creatorQuery->where('name', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('approver', function ($approverQuery) use ($search): void {
                            $approverQuery->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('modules.principal.promotions.index', [
            'campaigns' => $campaigns,
            'filters' => [
                'status' => $selectedStatus,
                'from_session' => $fromSession,
                'to_session' => $toSession,
                'class_id' => $classId,
                'search' => $search,
            ],
            'sessionOptions' => $this->sessionOptions(),
            'classOptions' => $this->classOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        $sessions = $this->sessionOptions();
        $classOptions = $this->classOptions();
        $defaultFromSession = $sessions[0] ?? now()->year.'-'.(now()->year + 1);
        $defaultToSession = $this->nextSession($defaultFromSession);
        $selectedFromSession = trim((string) $request->query('from_session', $defaultFromSession));
        $selectedToSession = trim((string) $request->query('to_session', $defaultToSession));
        $selectedToClassId = $request->filled('to_class_id') ? (int) $request->query('to_class_id') : null;
        $toClassExists = collect($classOptions)->contains(
            fn (array $classOption): bool => (int) $classOption['id'] === $selectedToClassId
        );

        $sessions = $this->ensureSessionPresent($sessions, $selectedFromSession);
        $sessions = $this->ensureSessionPresent($sessions, $selectedToSession);

        return view('modules.principal.promotions.create', [
            'sessionOptions' => $sessions,
            'classOptions' => $classOptions,
            'defaultFromSession' => $selectedFromSession,
            'defaultToSession' => $selectedToSession,
            'defaultToClassId' => $toClassExists ? $selectedToClassId : null,
        ]);
    }

    public function storeClass(Request $request): RedirectResponse
    {
        $normalizedSection = trim((string) $request->input('section'));
        $normalizedSection = $normalizedSection !== '' ? $normalizedSection : null;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('school_classes', 'name')
                    ->where(fn ($query) => $query->where('section', $normalizedSection)),
            ],
            'section' => ['nullable', 'string', 'max:20'],
            'from_session_context' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'to_session_context' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'to_class_context' => ['nullable', 'integer', Rule::exists('school_classes', 'id')],
        ]);

        SchoolClass::query()->create([
            'name' => trim((string) $validated['name']),
            'section' => $normalizedSection,
            'status' => 'active',
        ]);

        $query = [];
        if (isset($validated['from_session_context']) && trim((string) $validated['from_session_context']) !== '') {
            $query['from_session'] = trim((string) $validated['from_session_context']);
        }
        if (isset($validated['to_session_context']) && trim((string) $validated['to_session_context']) !== '') {
            $query['to_session'] = trim((string) $validated['to_session_context']);
        }
        if (isset($validated['to_class_context']) && (int) $validated['to_class_context'] > 0) {
            $query['to_class_id'] = (int) $validated['to_class_context'];
        }

        return redirect()
            ->route('principal.promotions.create', $query)
            ->with('status', 'Class created successfully. You can now select it for promotion campaign.');
    }

    public function storeCampaign(CreatePrincipalPromotionCampaignRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $campaign = $this->promotionService->createPrincipalGroupCampaign(
                (string) $validated['from_session'],
                (string) $validated['to_session'],
                (int) $validated['class_id'],
                (int) $request->user()->id,
                isset($validated['to_class_id']) ? (int) $validated['to_class_id'] : null
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable) {
            return back()->withInput()->with('error', 'Unable to create promotion campaign.');
        }

        return redirect()
            ->route('principal.promotions.show', $campaign)
            ->with('status', 'Promotion campaign created. Apply group actions, review, and execute when ready.');
    }

    public function show(PromotionCampaign $promotionCampaign): View|RedirectResponse
    {
        try {
            $payload = $this->promotionService->loadEligibleStudents($promotionCampaign);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('principal.promotions.index')
                ->with('error', $exception->getMessage());
        }

        $campaign = $payload['campaign'];

        return view('modules.principal.promotions.show', [
            'campaign' => $campaign,
            'rows' => $payload['rows'],
            'summary' => $payload['summary'],
            'isTerminalClass' => (bool) ($payload['is_terminal_class'] ?? false),
            'nextClassLabel' => $payload['next_class_label'],
        ]);
    }

    public function applyGroupAction(
        ApplyPrincipalPromotionGroupActionRequest $request,
        PromotionCampaign $promotionCampaign
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $this->promotionService->applyPrincipalGroupPromotion(
                (int) $promotionCampaign->id,
                $validated['student_ids'],
                (string) $validated['decision'],
                (int) $request->user()->id,
                $validated['note'] ?? null
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable) {
            return back()->withInput()->with('error', 'Unable to apply group action.');
        }

        return back()->with('status', 'Group action applied successfully.');
    }

    public function review(
        PrincipalReviewPromotionRequest $request,
        PromotionCampaign $promotionCampaign
    ): RedirectResponse {
        try {
            $this->promotionService->reviewByPrincipal(
                $promotionCampaign,
                $request->validated('rows', []),
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        } catch (Throwable) {
            return back()->with('error', 'Unable to save principal review decisions.')->withInput();
        }

        return back()->with('status', 'Principal review decisions saved.');
    }

    public function approve(
        PrincipalApproveCampaignRequest $request,
        PromotionCampaign $promotionCampaign
    ): RedirectResponse {
        try {
            $this->promotionService->approveCampaign(
                (int) $promotionCampaign->id,
                (int) $request->user()->id,
                $request->validated('principal_note')
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        } catch (Throwable) {
            return back()->with('error', 'Unable to approve promotion campaign.')->withInput();
        }

        return back()->with('status', 'Promotion campaign approved.');
    }

    public function reject(
        PrincipalRejectCampaignRequest $request,
        PromotionCampaign $promotionCampaign
    ): RedirectResponse {
        try {
            $this->promotionService->rejectCampaign(
                $promotionCampaign,
                (string) $request->validated('principal_note'),
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        } catch (Throwable) {
            return back()->with('error', 'Unable to reject promotion campaign.')->withInput();
        }

        return back()->with('status', 'Promotion campaign rejected.');
    }

    public function execute(
        ExecutePromotionCampaignRequest $request,
        PromotionCampaign $promotionCampaign
    ): RedirectResponse {
        try {
            $this->promotionService->executeCampaign(
                (int) $promotionCampaign->id,
                (int) $request->user()->id
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable) {
            return back()->with('error', 'Unable to execute promotion campaign.');
        }

        return back()->with('status', 'Promotion campaign executed successfully. Student classes and history are updated.');
    }

    public function undoApprovedAndExecuted(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm_undo' => ['required', 'accepted'],
        ]);

        try {
            $summary = $this->promotionService->undoApprovedAndExecutedCampaigns((int) $request->user()->id);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable) {
            return back()->with('error', 'Unable to undo approved and executed promotion campaigns.');
        }

        return back()->with(
            'status',
            sprintf(
                'Undo completed. Campaigns reverted: %d (approved: %d, executed: %d). Student rows reset: %d. Students reverted: %d.',
                (int) ($summary['campaigns_undone'] ?? 0),
                (int) ($summary['approved_campaigns_undone'] ?? 0),
                (int) ($summary['executed_campaigns_undone'] ?? 0),
                (int) ($summary['student_rows_reset'] ?? 0),
                (int) ($summary['students_reverted'] ?? 0)
            )
        );
    }

    /**
     * @return array<int, string>
     */
    private function sessionOptions(): array
    {
        $sessions = PromotionCampaign::query()
            ->select('from_session')
            ->distinct()
            ->pluck('from_session')
            ->merge(
                PromotionCampaign::query()
                    ->select('to_session')
                    ->distinct()
                    ->pluck('to_session')
            )
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => preg_match('/^\d{4}-\d{4}$/', $value) === 1)
            ->unique()
            ->filter(fn ($value): bool => trim((string) $value) !== '')
            ->values()
            ->all();

        if ($sessions !== []) {
            return $this->ensureSessionPresent($sessions, self::REQUIRED_PROMOTION_SESSION);
        }

        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);
        $fallback = [];

        for ($year = $startYear - 1; $year <= $startYear + 2; $year++) {
            $fallback[] = $year.'-'.($year + 1);
        }

        return $this->ensureSessionPresent($fallback, self::REQUIRED_PROMOTION_SESSION);
    }

    private function nextSession(string $fromSession): string
    {
        if (preg_match('/^(\d{4})-(\d{4})$/', trim($fromSession), $matches) === 1) {
            $startYear = (int) $matches[2];

            return $startYear.'-'.($startYear + 1);
        }

        $year = (int) now()->year;

        return $year.'-'.($year + 1);
    }

    /**
     * @param array<int, string> $sessions
     * @return array<int, string>
     */
    private function ensureSessionPresent(array $sessions, string $session): array
    {
        $candidate = trim($session);
        if ($candidate !== '' && preg_match('/^\d{4}-\d{4}$/', $candidate) === 1) {
            $sessions[] = $candidate;
        }

        return collect($sessions)
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => preg_match('/^\d{4}-\d{4}$/', $value) === 1)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function classOptions(): array
    {
        return SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section'])
            ->map(fn (SchoolClass $classRoom): array => [
                'id' => (int) $classRoom->id,
                'name' => trim((string) $classRoom->name.' '.(string) ($classRoom->section ?? '')),
            ])
            ->values()
            ->all();
    }
}
