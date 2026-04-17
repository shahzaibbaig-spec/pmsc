<?php

namespace App\Modules\Medical\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MedicalReferral;
use App\Modules\Medical\Requests\MedicalReferralListRequest;
use App\Modules\Medical\Requests\MedicalReportRequest;
use App\Modules\Medical\Requests\StoreMedicalReferralRequest;
use App\Modules\Medical\Requests\UpdateMedicalReferralRequest;
use App\Modules\Medical\Services\MedicalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class MedicalReferralController extends Controller
{
    public function __construct(private readonly MedicalService $service)
    {
    }

    public function principalIndex(): View
    {
        return view('modules.principal.medical.referrals', [
            'availableDoctors' => $this->service->listAvailableDoctors(),
        ]);
    }

    public function doctorIndex(): View
    {
        $user = auth()->user();

        return view('modules.doctor.medical.referrals', [
            'unreadNotifications' => $user?->unreadNotifications()->latest()->limit(10)->get() ?? collect(),
        ]);
    }

    public function doctorNotifications(): JsonResponse
    {
        $user = auth()->user();
        $notifications = $user?->unreadNotifications()->latest()->limit(10)->get() ?? collect();

        return response()->json([
            'data' => $notifications->map(static function ($notification): array {
                return [
                    'id' => (string) $notification->id,
                    'message' => (string) ($notification->data['message'] ?? 'New medical referral'),
                    'student_name' => (string) ($notification->data['student_name'] ?? '-'),
                    'referral_id' => (int) ($notification->data['referral_id'] ?? 0),
                    'created_at' => optional($notification->created_at)->format('Y-m-d H:i'),
                ];
            })->values()->all(),
        ]);
    }

    public function searchStudents(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $results = $this->service->studentSearch((string) $request->input('q', ''));

        return response()->json(['data' => $results]);
    }

    public function principalData(MedicalReferralListRequest $request): JsonResponse
    {
        $paginator = $this->service->referralsForPrincipal($request->validated());

        return response()->json($this->service->mapPaginator($paginator));
    }

    public function doctorData(MedicalReferralListRequest $request): JsonResponse
    {
        $paginator = $this->service->referralsForDoctor((int) auth()->id(), $request->validated());

        return response()->json($this->service->mapPaginator($paginator));
    }

    public function store(StoreMedicalReferralRequest $request): JsonResponse
    {
        try {
            $referral = $this->service->createReferral((int) auth()->id(), $request->validated());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Medical referral submitted successfully.',
            'referral_id' => $referral->id,
        ], 201);
    }

    public function update(UpdateMedicalReferralRequest $request, MedicalReferral $medicalReferral): JsonResponse
    {
        try {
            $this->service->updateByDoctor((int) auth()->id(), $medicalReferral, $request->validated());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Medical record updated successfully.']);
    }

    public function reportsIndex(): View
    {
        return view('modules.medical.reports.index');
    }

    public function reportsData(MedicalReportRequest $request): JsonResponse
    {
        $payload = $this->service->reportListData($request->user(), $request->validated());

        return response()->json($payload);
    }
}
