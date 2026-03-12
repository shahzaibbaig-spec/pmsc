<?php

namespace App\Modules\Medical\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MedicalReferral;
use Illuminate\View\View;

class DoctorDashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $pendingCount = MedicalReferral::query()
            ->where('doctor_id', (int) $user?->id)
            ->where('status', 'pending')
            ->count();

        $completedTodayCount = MedicalReferral::query()
            ->where('doctor_id', (int) $user?->id)
            ->where('status', 'completed')
            ->whereDate('updated_at', now()->toDateString())
            ->count();

        $unreadCount = $user?->unreadNotifications()->count() ?? 0;

        return view('modules.doctor.dashboard', compact('pendingCount', 'unreadCount', 'completedTodayCount'));
    }
}
