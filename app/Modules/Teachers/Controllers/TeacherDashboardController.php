<?php

namespace App\Modules\Teachers\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Mark;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function __invoke(): View
    {
        $userId = (int) auth()->id();
        $teacher = Teacher::query()->where('user_id', $userId)->first();
        $today = now()->toDateString();
        $currentSession = $this->sessionFromDate($today);
        $assignmentSession = $this->assignmentSessionForTeacher((int) ($teacher?->id ?? 0), $currentSession);

        if (! $teacher) {
            return view('modules.teacher.dashboard', [
                'stats' => [
                    'assigned_classes' => 0,
                    'assigned_subjects' => 0,
                    'marks_today' => 0,
                    'attendance_rows_today' => 0,
                ],
            ]);
        }

        $classTeacherClassIds = TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('session', $currentSession)
            ->where('is_class_teacher', true)
            ->pluck('class_id');

        return view('modules.teacher.dashboard', [
            'stats' => [
                'assigned_classes' => TeacherAssignment::query()
                    ->where('teacher_id', $teacher->id)
                    ->where('session', $assignmentSession)
                    ->distinct('class_id')
                    ->count('class_id'),
                'assigned_subjects' => TeacherAssignment::query()
                    ->where('teacher_id', $teacher->id)
                    ->where('session', $assignmentSession)
                    ->whereNotNull('subject_id')
                    ->count(),
                'assignment_session' => $assignmentSession,
                'marks_today' => Mark::query()
                    ->where('teacher_id', $teacher->id)
                    ->whereDate('created_at', $today)
                    ->count(),
                'attendance_rows_today' => Attendance::query()
                    ->whereIn('class_id', $classTeacherClassIds)
                    ->whereDate('date', $today)
                    ->count(),
            ],
        ]);
    }

    private function sessionFromDate(string $date): string
    {
        $dateTime = Carbon::parse($date);
        $startYear = $dateTime->month >= 7 ? $dateTime->year : ($dateTime->year - 1);

        return $startYear.'-'.($startYear + 1);
    }

    private function assignmentSessionForTeacher(int $teacherId, string $currentSession): string
    {
        if ($teacherId <= 0) {
            return $currentSession;
        }

        $hasCurrent = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('session', $currentSession)
            ->exists();

        if ($hasCurrent) {
            return $currentSession;
        }

        $latestSession = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->orderByDesc('session')
            ->value('session');

        return $latestSession ?: $currentSession;
    }
}
