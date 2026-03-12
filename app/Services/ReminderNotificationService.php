<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Mark;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Notifications\AttendanceCutoffReminderNotification;
use App\Notifications\MarksSubmissionReminderNotification;
use Illuminate\Support\Carbon;

class ReminderNotificationService
{
    public function sendMarksSubmissionReminders(string $session, string $date): array
    {
        $targetDate = Carbon::parse($date)->toDateString();

        $assignments = TeacherAssignment::query()
            ->with('teacher.user:id,name,email,status')
            ->where('session', $session)
            ->whereNotNull('subject_id')
            ->get(['teacher_id', 'subject_id', 'session'])
            ->groupBy('teacher_id');

        $notified = 0;
        $skipped = 0;

        foreach ($assignments as $teacherId => $rows) {
            $teacherUser = $rows->first()?->teacher?->user;
            if (! $teacherUser) {
                $skipped++;
                continue;
            }

            if (! $this->isActive($teacherUser)) {
                $skipped++;
                continue;
            }

            $hasMarksToday = Mark::query()
                ->where('teacher_id', (int) $teacherId)
                ->where('session', $session)
                ->whereDate('created_at', $targetDate)
                ->exists();

            if ($hasMarksToday) {
                $skipped++;
                continue;
            }

            if ($this->alreadyNotified($teacherUser, 'marks_submission_reminder', 'reminder_date', $targetDate)) {
                $skipped++;
                continue;
            }

            $teacherUser->notify(new MarksSubmissionReminderNotification([
                'session' => $session,
                'reminder_date' => $targetDate,
            ]));
            $notified++;
        }

        return [
            'session' => $session,
            'date' => $targetDate,
            'notified' => $notified,
            'skipped' => $skipped,
        ];
    }

    public function sendAttendanceCutoffReminders(string $session, string $date, string $cutoffTime): array
    {
        $targetDate = Carbon::parse($date)->toDateString();

        $assignments = TeacherAssignment::query()
            ->with([
                'teacher.user:id,name,email,status',
                'classRoom:id,name,section',
            ])
            ->where('session', $session)
            ->where('is_class_teacher', true)
            ->get(['teacher_id', 'class_id', 'session'])
            ->groupBy('teacher_id');

        $notified = 0;
        $skipped = 0;

        foreach ($assignments as $teacherId => $rows) {
            $teacherUser = $rows->first()?->teacher?->user;
            if (! $teacherUser || ! $this->isActive($teacherUser)) {
                $skipped++;
                continue;
            }

            $pendingClasses = $rows
                ->filter(function (TeacherAssignment $assignment) use ($targetDate): bool {
                    return ! Attendance::query()
                        ->where('class_id', $assignment->class_id)
                        ->whereDate('date', $targetDate)
                        ->exists();
                })
                ->map(fn (TeacherAssignment $assignment): array => [
                    'class_id' => (int) $assignment->class_id,
                    'class_name' => trim(($assignment->classRoom?->name ?? '').' '.($assignment->classRoom?->section ?? '')),
                ])
                ->values();

            if ($pendingClasses->isEmpty()) {
                $skipped++;
                continue;
            }

            if ($this->alreadyNotified($teacherUser, 'attendance_cutoff_reminder', 'attendance_date', $targetDate)) {
                $skipped++;
                continue;
            }

            $teacherUser->notify(new AttendanceCutoffReminderNotification([
                'attendance_date' => $targetDate,
                'cutoff_time' => $cutoffTime,
                'pending_classes' => $pendingClasses->all(),
            ]));
            $notified++;
        }

        return [
            'session' => $session,
            'date' => $targetDate,
            'cutoff_time' => $cutoffTime,
            'notified' => $notified,
            'skipped' => $skipped,
        ];
    }

    public function sessionFromDate(string $date): string
    {
        $dateTime = Carbon::parse($date);
        $startYear = $dateTime->month >= 7 ? $dateTime->year : ($dateTime->year - 1);

        return $startYear.'-'.($startYear + 1);
    }

    private function alreadyNotified(User $user, string $type, string $dateKey, string $dateValue): bool
    {
        return $user->notifications()
            ->where('data->type', $type)
            ->where("data->{$dateKey}", $dateValue)
            ->exists();
    }

    private function isActive(User $user): bool
    {
        return $user->status === null || $user->status === 'active';
    }
}
