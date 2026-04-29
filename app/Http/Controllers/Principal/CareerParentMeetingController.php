<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\CareerParentMeeting;
use Illuminate\View\View;

class CareerParentMeetingController extends Controller
{
    public function index(): View
    {
        return view('principal.career-parent-meetings.index', [
            'meetings' => CareerParentMeeting::query()->with(['student.classRoom', 'counselor'])->latest('id')->paginate(20),
        ]);
    }

    public function show(CareerParentMeeting $meeting): View
    {
        return view('career-counselor.parent-meetings.show', ['meeting' => $meeting->load(['student.classRoom', 'counselor'])]);
    }

    public function print(CareerParentMeeting $meeting): View
    {
        return view('career-counselor.parent-meetings.print', ['meeting' => $meeting->load(['student.classRoom', 'counselor'])]);
    }
}
