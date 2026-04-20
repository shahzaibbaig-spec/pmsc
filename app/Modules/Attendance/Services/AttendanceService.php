<?php

namespace App\Modules\Attendance\Services;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Notifications\AttendanceMarkedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class AttendanceService
{
    public function classTeacherClassesForUser(int $userId, string $date, ?string $session = null): Collection
    {
        $teacher = Teacher::query()->where('user_id', $userId)->first();
        if (! $teacher) {
            return collect();
        }

        $currentSession = $this->sessionFromDate($date);
        $assignmentSession = $this->resolveRequestedOrDefaultClassTeacherSession(
            (int) $teacher->id,
            $currentSession,
            $session
        );

        $assignments = TeacherAssignment::query()
            ->with('classRoom:id,name,section')
            ->where('teacher_id', $teacher->id)
            ->where('is_class_teacher', true)
            ->where('session', $assignmentSession)
            ->orderBy('class_id')
            ->get(['id', 'class_id', 'session']);

        $classIds = $assignments->pluck('class_id')->unique()->values();
        $activeStudentCountByClass = Student::query()
            ->whereIn('class_id', $classIds)
            ->where('status', 'active')
            ->selectRaw('class_id, count(*) as total')
            ->groupBy('class_id')
            ->pluck('total', 'class_id');

        return $assignments->map(function (TeacherAssignment $assignment) use ($activeStudentCountByClass): array {
            return [
                'assignment_id' => $assignment->id,
                'class_id' => $assignment->class_id,
                'session' => $assignment->session,
                'class_name' => trim(($assignment->classRoom?->name ?? '').' '.($assignment->classRoom?->section ?? '')),
                'active_students' => (int) ($activeStudentCountByClass->get($assignment->class_id) ?? 0),
            ];
        })->values();
    }

    /**
     * @return array<int, string>
     */
    public function classTeacherSessionsForUser(int $userId, string $date): array
    {
        $teacher = Teacher::query()->where('user_id', $userId)->first();
        $currentSession = $this->sessionFromDate($date);

        if (! $teacher) {
            return [$currentSession];
        }

        $storedSessions = TeacherAssignment::query()
            ->where('teacher_id', (int) $teacher->id)
            ->where('is_class_teacher', true)
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        return collect(array_merge([$currentSession], $storedSessions))
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function classAttendanceSheet(int $classId, string $date): array
    {
        $classRoom = SchoolClass::query()->findOrFail($classId, ['id', 'name', 'section']);

        $students = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'student_id', 'name', 'father_name']);

        $attendanceMap = Attendance::query()
            ->where('class_id', $classId)
            ->whereDate('date', $date)
            ->get(['student_id', 'status'])
            ->pluck('status', 'student_id');

        return [
            'class' => [
                'id' => $classRoom->id,
                'name' => $classRoom->name,
                'section' => $classRoom->section,
                'display_name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
            ],
            'date' => $date,
            'students' => $students->map(function (Student $student) use ($attendanceMap): array {
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id,
                    'name' => $student->name,
                    'father_name' => $student->father_name,
                    'status' => $attendanceMap->get($student->id, 'present'),
                ];
            })->values()->all(),
        ];
    }

    public function markAttendance(int $userId, int $classId, string $date, array $records, ?string $session = null): void
    {
        if (! $this->teacherCanMarkClass($userId, $classId, $date, $session)) {
            throw new RuntimeException('You are not assigned as class teacher for this class/session.');
        }

        $studentIds = collect($records)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $validCount = Student::query()
            ->where('class_id', $classId)
            ->whereIn('id', $studentIds)
            ->count();

        if ($validCount !== $studentIds->count()) {
            throw new RuntimeException('One or more students are invalid for the selected class.');
        }

        DB::transaction(function () use ($classId, $date, $records): void {
            foreach ($records as $row) {
                Attendance::query()->updateOrCreate(
                    [
                        'student_id' => (int) $row['student_id'],
                        'date' => $date,
                    ],
                    [
                        'class_id' => $classId,
                        'status' => $row['status'],
                    ]
                );
            }
        });

        $statusSummary = collect($records)
            ->countBy(fn (array $row): string => (string) $row['status']);

        $classRoom = SchoolClass::query()->find($classId, ['id', 'name', 'section']);
        $markedBy = User::query()->find($userId, ['id', 'name']);

        $recipientUsers = User::permission('view_attendance')
            ->where('id', '!=', $userId)
            ->where(function ($query): void {
                $query->where('status', 'active')
                    ->orWhereNull('status');
            })
            ->get(['id', 'name', 'email']);

        if ($recipientUsers->isNotEmpty()) {
            Notification::send($recipientUsers, new AttendanceMarkedNotification([
                'class_id' => $classId,
                'class_name' => trim(($classRoom?->name ?? 'Class').' '.($classRoom?->section ?? '')),
                'date' => $date,
                'marked_by' => $markedBy?->name ?? 'Teacher',
                'present' => (int) ($statusSummary['present'] ?? 0),
                'absent' => (int) ($statusSummary['absent'] ?? 0),
                'leave' => (int) ($statusSummary['leave'] ?? 0),
                'url' => route('dashboard'),
            ]));
        }
    }

    public function principalDailySummary(string $date): array
    {
        $totalStudents = Student::query()
            ->where('status', 'active')
            ->count();

        $baseQuery = Attendance::query()->whereDate('date', $date);

        $present = (clone $baseQuery)->where('status', 'present')->count();
        $absent = (clone $baseQuery)->where('status', 'absent')->count();
        $leave = (clone $baseQuery)->where('status', 'leave')->count();

        return [
            'date' => $date,
            'total_students' => $totalStudents,
            'present' => $present,
            'absent' => $absent,
            'leave' => $leave,
        ];
    }

    private function teacherCanMarkClass(int $userId, int $classId, string $date, ?string $session = null): bool
    {
        $teacher = Teacher::query()->where('user_id', $userId)->first();
        if (! $teacher) {
            return false;
        }

        $currentSession = $this->sessionFromDate($date);
        $assignmentSession = $this->resolveRequestedOrDefaultClassTeacherSession(
            (int) $teacher->id,
            $currentSession,
            $session
        );

        return TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $classId)
            ->where('is_class_teacher', true)
            ->where('session', $assignmentSession)
            ->exists();
    }

    private function resolveRequestedOrDefaultClassTeacherSession(
        int $teacherId,
        string $currentSession,
        ?string $requestedSession = null
    ): string {
        $resolvedRequested = trim((string) $requestedSession);

        if ($resolvedRequested !== '') {
            $requestedExists = TeacherAssignment::query()
                ->where('teacher_id', $teacherId)
                ->where('is_class_teacher', true)
                ->where('session', $resolvedRequested)
                ->exists();

            if ($requestedExists) {
                return $resolvedRequested;
            }
        }

        return $this->classTeacherAssignmentSessionForTeacher($teacherId, $currentSession);
    }

    private function classTeacherAssignmentSessionForTeacher(int $teacherId, string $currentSession): string
    {
        $hasCurrent = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('is_class_teacher', true)
            ->where('session', $currentSession)
            ->exists();

        if ($hasCurrent) {
            return $currentSession;
        }

        $latestSession = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('is_class_teacher', true)
            ->orderByDesc('session')
            ->value('session');

        return $latestSession ?: $currentSession;
    }

    private function sessionFromDate(string $date): string
    {
        $dateTime = Carbon::parse($date);
        $startYear = $dateTime->month >= 7 ? $dateTime->year : ($dateTime->year - 1);

        return $startYear.'-'.($startYear + 1);
    }
}
