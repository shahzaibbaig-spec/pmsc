<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ReviewTeacherDeviceDeclarationRequest;
use App\Models\InventoryAssetUnit;
use App\Models\TeacherDeviceDeclaration;
use App\Services\TeacherDeviceDeclarationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceDeclarationReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $declarations = TeacherDeviceDeclaration::query()
            ->with(['teacher.user:id,name,email', 'assetUnit:id,serial_number,brand,model,status'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('serial_number', 'like', "%{$search}%")
                        ->orWhereHas('teacher.user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.device-declarations.index', [
            'declarations' => $declarations,
            'status' => $status,
            'search' => $search,
            'statusOptions' => ['submitted', 'verified', 'rejected', 'linked'],
        ]);
    }

    public function show(TeacherDeviceDeclaration $declaration): View
    {
        $declaration->load([
            'teacher.user:id,name,email',
            'assetUnit:id,serial_number,brand,model,status,issued_to_teacher_id',
            'reviewer:id,name',
        ]);

        $matchedAssets = InventoryAssetUnit::query()
            ->whereRaw('LOWER(serial_number) = ?', [strtolower((string) $declaration->serial_number)])
            ->orWhere('serial_number', 'like', '%'.trim((string) $declaration->serial_number).'%')
            ->orderBy('serial_number')
            ->limit(10)
            ->get(['id', 'serial_number', 'brand', 'model', 'status', 'issued_to_teacher_id']);

        return view('inventory.device-declarations.show', [
            'declaration' => $declaration,
            'matchedAssets' => $matchedAssets,
        ]);
    }

    public function review(
        ReviewTeacherDeviceDeclarationRequest $request,
        TeacherDeviceDeclaration $declaration,
        TeacherDeviceDeclarationService $service
    ): RedirectResponse {
        $payload = $request->validated();
        $action = (string) $payload['action'];
        $userId = (int) $request->user()->id;
        $adminNote = $payload['admin_note'] ?? null;

        if ($action === 'reject') {
            $service->rejectDeclaration($declaration, $userId, (string) $adminNote);
        } elseif ($action === 'link') {
            $service->autoLinkBySerial(
                $declaration,
                $userId,
                isset($payload['asset_unit_id']) ? (int) $payload['asset_unit_id'] : null,
                $adminNote
            );
        } else {
            $service->verifyDeclaration($declaration, $userId, $adminNote);
        }

        return redirect()
            ->route('inventory.device-declarations.show', $declaration)
            ->with('success', 'Declaration review updated successfully.');
    }
}
