<?php

namespace App\Http\Controllers\CareerCounselor\Kcat;

use App\Http\Controllers\Controller;
use App\Models\KcatAssignment;
use App\Models\KcatAttempt;
use App\Models\KcatTest;
use Illuminate\View\View;

class KcatDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('career-counselor.kcat.dashboard', [
            'activeTests' => KcatTest::query()->where('status', 'active')->count(),
            'assignedTests' => KcatAssignment::query()->where('status', 'assigned')->count(),
            'completedAttempts' => KcatAttempt::query()->whereIn('status', ['submitted', 'reviewed'])->count(),
            'averageScore' => round((float) KcatAttempt::query()->whereIn('status', ['submitted', 'reviewed'])->avg('percentage'), 2),
            'needsSupport' => KcatAttempt::query()->where('band', 'needs_support')->count(),
            'recentAttempts' => KcatAttempt::query()->with(['student.classRoom', 'test'])->latest('submitted_at')->limit(8)->get(),
        ]);
    }
}
