<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreTeacherDeviceDeclarationRequest;
use App\Models\Teacher;
use App\Models\TeacherDeviceDeclaration;
use App\Services\TeacherDeviceDeclarationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeacherDeviceDeclarationController extends Controller
{
    public function index(): View
    {
        $teacher = $this->teacherFromAuth();

        $declarations = TeacherDeviceDeclaration::query()
            ->with(['assetUnit:id,serial_number,brand,model,status'])
            ->where('teacher_id', $teacher->id)
            ->latest('id')
            ->paginate(15);

        return view('teacher.my-inventory.devices.index', [
            'declarations' => $declarations,
        ]);
    }

    public function create(): View
    {
        return view('teacher.my-inventory.devices.create');
    }

    public function store(
        StoreTeacherDeviceDeclarationRequest $request,
        TeacherDeviceDeclarationService $service
    ): RedirectResponse {
        $teacher = $this->teacherFromAuth();
        $service->submitDeclaration($teacher->id, $request->validated());

        return redirect()
            ->route('teacher.my-inventory.devices.index')
            ->with('success', 'Device declaration submitted successfully.');
    }

    private function teacherFromAuth(): Teacher
    {
        $teacher = Auth::user()?->teacher;
        abort_if($teacher === null, 403, 'Teacher profile not found.');

        return $teacher;
    }
}
