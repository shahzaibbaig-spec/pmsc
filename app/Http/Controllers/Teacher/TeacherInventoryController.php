<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\InventoryDemand;
use App\Models\Teacher;
use App\Models\TeacherDeviceDeclaration;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeacherInventoryController extends Controller
{
    public function index(): View
    {
        $teacher = $this->teacherFromAuth();

        $demandStats = InventoryDemand::query()
            ->where('teacher_id', $teacher->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentDemands = InventoryDemand::query()
            ->where('teacher_id', $teacher->id)
            ->latest('request_date')
            ->latest('id')
            ->limit(5)
            ->get();

        $recentDeclarations = TeacherDeviceDeclaration::query()
            ->where('teacher_id', $teacher->id)
            ->latest('id')
            ->limit(5)
            ->get();

        return view('teacher.my-inventory.index', [
            'teacher' => $teacher,
            'demandStats' => $demandStats,
            'recentDemands' => $recentDemands,
            'recentDeclarations' => $recentDeclarations,
        ]);
    }

    private function teacherFromAuth(): Teacher
    {
        $teacher = Auth::user()?->teacher;
        abort_if($teacher === null, 403, 'Teacher profile not found.');

        return $teacher;
    }
}
