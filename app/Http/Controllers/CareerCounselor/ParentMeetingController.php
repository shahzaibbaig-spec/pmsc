<?php

namespace App\Http\Controllers\CareerCounselor;

use App\Http\Controllers\Controller;
use App\Models\CareerParentMeeting;
use App\Models\Student;
use App\Services\CareerParentMeetingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentMeetingController extends Controller
{
    public function __construct(private readonly CareerParentMeetingService $careerParentMeetingService) {}

    public function index(Request $request): View
    {
        return view('career-counselor.parent-meetings.index', [
            'meetings' => CareerParentMeeting::query()
                ->with(['student.classRoom', 'counselor'])
                ->where('counselor_id', $request->user()->id)
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        return view('career-counselor.parent-meetings.form', [
            'student' => $request->integer('student_id') ? Student::query()->with('classRoom')->find($request->integer('student_id')) : null,
            'meeting' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateMeeting($request);
        $student = Student::query()->with('classRoom')->findOrFail((int) $validated['student_id']);
        $meeting = $this->careerParentMeetingService->createMeeting($student, $validated, $request->user());

        return redirect()->route('career-counselor.parent-meetings.show', $meeting)->with('success', 'Parent meeting recommended.');
    }

    public function show(CareerParentMeeting $meeting): View
    {
        return view('career-counselor.parent-meetings.show', ['meeting' => $meeting->load(['student.classRoom', 'counselor'])]);
    }

    public function update(Request $request, CareerParentMeeting $meeting): RedirectResponse
    {
        $this->careerParentMeetingService->updateMeeting($meeting, $this->validateMeeting($request, false), $request->user());

        return back()->with('success', 'Parent meeting updated.');
    }

    public function complete(Request $request, CareerParentMeeting $meeting): RedirectResponse
    {
        $this->careerParentMeetingService->markCompleted($meeting, $request->user());

        return back()->with('success', 'Parent meeting completed.');
    }

    public function print(CareerParentMeeting $meeting): View
    {
        return view('career-counselor.parent-meetings.print', ['meeting' => $meeting->load(['student.classRoom', 'counselor'])]);
    }

    private function validateMeeting(Request $request, bool $requireStudent = true): array
    {
        return $request->validate([
            'student_id' => [$requireStudent ? 'required' : 'nullable', 'exists:students,id'],
            'meeting_date' => ['nullable', 'date'],
            'parent_concerns' => ['nullable', 'string'],
            'parent_expectations' => ['nullable', 'string'],
            'counselor_recommendation' => ['nullable', 'string'],
            'agreed_action_plan' => ['nullable', 'string'],
            'next_meeting_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:30'],
        ]);
    }
}
