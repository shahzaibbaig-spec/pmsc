<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\ExecutePromotionCampaignRequest;
use App\Http\Requests\Promotions\PrincipalApproveCampaignRequest;
use App\Http\Requests\Promotions\PrincipalRejectCampaignRequest;
use App\Http\Requests\Promotions\PrincipalReviewPromotionRequest;
use App\Models\PromotionCampaign;
use App\Services\PromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class PrincipalPromotionController extends Controller
{
    public function __construct(private readonly PromotionService $promotionService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:submitted,draft,approved,rejected,executed,all'],
            'session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $selectedStatus = (string) ($filters['status'] ?? 'submitted');
        $selectedSession = trim((string) ($filters['session'] ?? ''));
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
            ->when($selectedSession !== '', function ($query) use ($selectedSession): void {
                $query->where('from_session', $selectedSession);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->whereHas('classRoom', function ($classQuery) use ($search): void {
                        $classQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('section', 'like', '%'.$search.'%');
                    })
                        ->orWhereHas('creator', function ($creatorQuery) use ($search): void {
                            $creatorQuery->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByRaw("
                CASE status
                    WHEN 'submitted' THEN 1
                    WHEN 'approved' THEN 2
                    WHEN 'draft' THEN 3
                    WHEN 'rejected' THEN 4
                    WHEN 'executed' THEN 5
                    ELSE 6
                END
            ")
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $sessionOptions = PromotionCampaign::query()
            ->select('from_session')
            ->distinct()
            ->orderByDesc('from_session')
            ->pluck('from_session')
            ->filter(fn ($session): bool => trim((string) $session) !== '')
            ->values();

        return view('modules.principal.promotions.index', [
            'campaigns' => $campaigns,
            'filters' => [
                'status' => $selectedStatus,
                'session' => $selectedSession,
                'search' => $search,
            ],
            'sessionOptions' => $sessionOptions,
        ]);
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
            'nextClassLabel' => $payload['next_class_label'],
        ]);
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
                $promotionCampaign,
                $request->validated('principal_note'),
                $request->user()
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        } catch (Throwable) {
            return back()->with('error', 'Unable to approve promotion campaign.')->withInput();
        }

        return back()->with('status', 'Promotion campaign approved by principal.');
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
            $this->promotionService->executeCampaign($promotionCampaign, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable) {
            return back()->with('error', 'Unable to execute promotion campaign.');
        }

        return back()->with('status', 'Promotion campaign executed successfully. Student classes and history are updated.');
    }
}
