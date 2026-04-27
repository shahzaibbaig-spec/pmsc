<?php

namespace App\Http\Controllers\CareerCounselor;

use App\Http\Controllers\Controller;
use App\Http\Requests\CareerCounselor\StoreCounselingSessionRequest;
use App\Models\CareerCounselingSession;
use App\Models\Student;
use App\Services\CareerCounselorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CounselingSessionController extends Controller
{
    public function __construct(private readonly CareerCounselorService $careerCounselorService)
    {
    }

    public function index(Request $request): View
    {
        return view('career-counselor.sessions.index', [
            'sessions' => $request->user()->id
                ? CareerCounselingSession::query()
                    ->with(['student.classRoom', 'careerProfile'])
                    ->where('counselor_id', $request->user()->id)
                    ->orderByDesc('counseling_date')
                    ->paginate(20)
                : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $student = $request->integer('student_id') > 0
            ? Student::query()->with('classRoom')->find($request->integer('student_id'))
            : null;

        return view('career-counselor.sessions.create', [
            'student' => $student,
        ]);
    }

    public function store(StoreCounselingSessionRequest $request): RedirectResponse
    {
        $student = Student::query()->with('classRoom')->findOrFail($request->integer('student_id'));
        $session = $this->careerCounselorService->createCounselingSession($student, $request->validated(), $request->user());

        return redirect()
            ->route('career-counselor.sessions.show', $session)
            ->with('success', 'Career Counselor session recorded.');
    }

    public function show(CareerCounselingSession $session): View
    {
        return view('career-counselor.sessions.show', [
            'session' => $session->load(['student.classRoom', 'careerProfile', 'counselor', 'createdBy', 'updatedBy']),
        ]);
    }

}
