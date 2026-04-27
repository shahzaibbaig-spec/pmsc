<?php

namespace App\Http\Controllers\CareerCounselor;

use App\Http\Controllers\Controller;
use App\Http\Requests\CareerCounselor\StoreCareerProfileRequest;
use App\Models\CareerProfile;
use App\Models\Student;
use App\Services\CareerCounselorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerProfileController extends Controller
{
    public function __construct(private readonly CareerCounselorService $careerCounselorService)
    {
    }

    public function create(Request $request): View
    {
        $student = $request->integer('student_id') > 0
            ? Student::query()->with(['classRoom', 'latestCareerProfile'])->find($request->integer('student_id'))
            : null;

        return view('career-counselor.profiles.form', [
            'student' => $student,
            'profile' => $student?->latestCareerProfile,
        ]);
    }

    public function store(StoreCareerProfileRequest $request): RedirectResponse
    {
        $student = Student::query()->with('classRoom')->findOrFail($request->integer('student_id'));
        $profile = $this->careerCounselorService->createOrUpdateCareerProfile($student, $request->validated(), $request->user());

        return redirect()
            ->route('career-counselor.profiles.show', $profile)
            ->with('success', 'Career Counselor profile saved.');
    }

    public function show(CareerProfile $profile): View
    {
        return view('career-counselor.profiles.show', [
            'profile' => $profile->load(['student.classRoom', 'currentClass', 'createdBy', 'updatedBy', 'counselingSessions.counselor']),
        ]);
    }
}
