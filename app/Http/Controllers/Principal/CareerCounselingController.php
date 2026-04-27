<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\CareerCounselingSession;
use App\Models\CareerProfile;
use App\Services\CareerCounselorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerCounselingController extends Controller
{
    public function __construct(private readonly CareerCounselorService $careerCounselorService)
    {
    }

    public function index(Request $request): View
    {
        return view('principal.career-counseling.index', $this->careerCounselorService->getPrincipalRecords($this->filters($request)));
    }

    public function profiles(Request $request): View
    {
        return $this->index($request);
    }

    public function profileShow(CareerProfile $profile): View
    {
        return view('career-counselor.profiles.show', [
            'profile' => $profile->load(['student.classRoom', 'currentClass', 'createdBy', 'updatedBy', 'counselingSessions.counselor']),
            'principalMode' => true,
        ]);
    }

    public function sessions(Request $request): View
    {
        return $this->index($request);
    }

    public function sessionShow(CareerCounselingSession $session): View
    {
        return view('career-counselor.sessions.show', [
            'session' => $session->load(['student.classRoom', 'careerProfile', 'counselor', 'createdBy', 'updatedBy']),
            'principalMode' => true,
        ]);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'student' => ['nullable', 'string', 'max:100'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'counselor_id' => ['nullable', 'integer', 'exists:users,id'],
            'session' => ['nullable', 'string', 'max:20'],
        ]);
    }
}
