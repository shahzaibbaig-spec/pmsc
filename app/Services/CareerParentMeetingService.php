<?php

namespace App\Services;

use App\Models\CareerParentMeeting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CareerParentMeetingService
{
    public function __construct(private readonly CareerCounselorService $careerCounselorService)
    {
    }

    public function createMeeting(Student $student, array $data, User $counselor): CareerParentMeeting
    {
        return DB::transaction(function () use ($student, $data, $counselor): CareerParentMeeting {
            $meeting = CareerParentMeeting::query()->create([
                ...$this->meetingFields($data),
                'student_id' => $student->id,
                'career_profile_id' => $data['career_profile_id'] ?? null,
                'counseling_session_id' => $data['counseling_session_id'] ?? null,
                'counselor_id' => $counselor->id,
                'session' => $this->careerCounselorService->currentSession(),
                'created_by' => $counselor->id,
                'updated_by' => $counselor->id,
            ]);

            $this->careerCounselorService->notifyPrincipals($student, $counselor, 'Parent meeting recommended.');

            return $meeting->fresh(['student.classRoom', 'counselor']);
        });
    }

    public function updateMeeting(CareerParentMeeting $meeting, array $data, User $counselor): CareerParentMeeting
    {
        return DB::transaction(function () use ($meeting, $data, $counselor): CareerParentMeeting {
            $meeting->update([...$this->meetingFields($data), 'updated_by' => $counselor->id]);

            return $meeting->fresh(['student.classRoom', 'counselor']);
        });
    }

    public function markCompleted(CareerParentMeeting $meeting, User $counselor): CareerParentMeeting
    {
        return DB::transaction(function () use ($meeting, $counselor): CareerParentMeeting {
            $meeting->update(['status' => 'completed', 'updated_by' => $counselor->id]);

            return $meeting->fresh(['student.classRoom', 'counselor']);
        });
    }

    public function getStudentMeetings(Student $student): Collection
    {
        return $student->careerParentMeetings()->with('counselor')->latest('id')->get();
    }

    private function meetingFields(array $data): array
    {
        return collect($data)->only([
            'meeting_date',
            'parent_concerns',
            'parent_expectations',
            'counselor_recommendation',
            'agreed_action_plan',
            'next_meeting_date',
            'status',
        ])->all();
    }
}
