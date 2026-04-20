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

    /**
     * @return array<int, string>
     */
    public function principalSessionOptions(string $date, int $backward = 1, int $forward = 3): array
    {
        $resolvedDate = trim($date) !== '' ? $date : now()->toDateString();
        $currentSession = $this->sessionFromDate($resolvedDate);
        $currentStartYear = (int) explode('-', $currentSession)[0];

        $generatedSessions = [];
        for ($year = $currentStartYear - $backward; $year <= $currentStartYear + $forward; $year++) {
            $generatedSessions[] = $year.'-'.($year + 1);
        }

        $storedSessions = TeacherAssignment::query()
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        return collect(array_merge([$currentSession], $storedSessions, $generatedSessions))
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{
     *   date:string,
     *   session:string,
     *   class_filter_id:?int,
     *   summary:array{
     *     classes_count:int,
     *     total_students:int,
     *     present:int,
     *     absent:int,
     *     leave:int,
     *     assigned_class_teachers:int,
     *     classes_marked:int,
     *     classes_not_marked:int,
     *     unassigned_classes:int
     *   },
     *   teacher_marking:Collection<int, array{
     *     class_id:int,
     *     class_name:string,
     *     teacher_id:?int,
     *     teacher_name:string,
     *     teacher_code:string,
     *     active_students:int,
     *     marked_students:int,
     *     present:int,
     *     absent:int,
     *     leave:int,
     *     is_marked:bool,
     *     is_partial:bool,
     *     status_label:string
     *   }>,
     *   absent_students:Collection<int, array{
     *     student_id_value:string,
     *     student_name:string,
     *     class_name:string
     *   }>
     * }
     */
    public function principalClasswiseAttendanceOverview(
        string $date,
        ?string $session = null,
        ?int $classId = null
    ): array {
        $resolvedDate = trim($date) !== '' ? $date : now()->toDateString();
        $currentSession = $this->sessionFromDate($resolvedDate);
        $resolvedSession = trim((string) $session) !== ''
            ? trim((string) $session)
            : $currentSession;

        $classesQuery = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section');

        if ($classId !== null && $classId > 0) {
            $classesQuery->whereKey($classId);
        }

        $classes = $classesQuery->get(['id', 'name', 'section']);
        $classIds = $classes->pluck('id')->map(static fn ($id): int => (int) $id)->values();

        $activeStudentCountByClass = $classIds->isNotEmpty()
            ? Student::query()
                ->whereIn('class_id', $classIds)
                ->where('status', 'active')
                ->selectRaw('class_id, count(*) as total')
                ->groupBy('class_id')
                ->pluck('total', 'class_id')
            : collect();

        $classTeacherAssignments = $classIds->isNotEmpty()
            ? TeacherAssignment::query()
                ->with([
                    'teacher:id,teacher_id,user_id',
                    'teacher.user:id,name',
                ])
                ->whereIn('class_id', $classIds)
                ->where('session', $resolvedSession)
                ->where('is_class_teacher', true)
                ->orderByDesc('id')
                ->get(['id', 'teacher_id', 'class_id', 'session'])
                ->groupBy('class_id')
                ->map(fn (Collection $rows): ?TeacherAssignment => $rows->first())
            : collect();

        $attendanceRows = $classIds->isNotEmpty()
            ? Attendance::query()
                ->with([
                    'student:id,name,student_id,class_id',
                    'classRoom:id,name,section',
                ])
                ->whereDate('date', $resolvedDate)
                ->whereIn('class_id', $classIds)
                ->orderBy('class_id')
                ->orderBy('student_id')
                ->get(['id', 'student_id', 'class_id', 'date', 'status'])
            : collect();

        $attendanceByClass = $attendanceRows->groupBy('class_id');

        $teacherMarking = $classes
            ->map(function (SchoolClass $classRoom) use ($classTeacherAssignments, $attendanceByClass, $activeStudentCountByClass): array {
                $classId = (int) $classRoom->id;
                /** @var TeacherAssignment|null $assignment */
                $assignment = $classTeacherAssignments->get($classId);
                $classAttendance = $attendanceByClass->get($classId, collect());

                $activeStudents = (int) ($activeStudentCountByClass->get($classId) ?? 0);
                $markedStudents = $classAttendance->count();
                $present = $classAttendance->where('status', 'present')->count();
                $absent = $classAttendance->where('status', 'absent')->count();
                $leave = $classAttendance->where('status', 'leave')->count();
                $isMarked = $markedStudents > 0;
                $isPartial = $isMarked && $activeStudents > 0 && $markedStudents < $activeStudents;

                $statusLabel = match (true) {
                    $assignment === null => 'No class teacher assigned',
                    ! $isMarked => 'Not marked',
                    $isPartial => 'Partially marked',
                    default => 'Marked',
                };

                return [
                    'class_id' => $classId,
                    'class_name' => trim((string) $classRoom->name.' '.(string) ($classRoom->section ?? '')),
                    'teacher_id' => $assignment ? (int) $assignment->teacher_id : null,
                    'teacher_name' => $assignment?->teacher?->user?->name ?? 'Unassigned',
                    'teacher_code' => (string) ($assignment?->teacher?->teacher_id ?? '-'),
                    'active_students' => $activeStudents,
                    'marked_students' => $markedStudents,
                    'present' => $present,
                    'absent' => $absent,
                    'leave' => $leave,
                    'is_marked' => $isMarked,
                    'is_partial' => $isPartial,
                    'status_label' => $statusLabel,
                ];
            })
            ->values();

        $absentStudents = $attendanceRows
            ->where('status', 'absent')
            ->map(function (Attendance $attendance): array {
                return [
                    'student_id_value' => (string) ($attendance->student?->student_id ?? '-'),
                    'student_name' => (string) ($attendance->student?->name ?? 'Student'),
                    'class_name' => trim((string) ($attendance->classRoom?->name ?? '').' '.(string) ($attendance->classRoom?->section ?? '')),
                ];
            })
            ->values();

        $assignedClassTeachers = $teacherMarking->whereNotNull('teacher_id')->count();
        $classesMarked = $teacherMarking
            ->filter(fn (array $row): bool => $row['teacher_id'] !== null && (bool) $row['is_marked'])
            ->count();
        $classesNotMarked = $teacherMarking
            ->filter(fn (array $row): bool => $row['teacher_id'] !== null && ! (bool) $row['is_marked'])
            ->count();
        $unassignedClasses = $teacherMarking->whereNull('teacher_id')->count();

        return [
            'date' => $resolvedDate,
            'session' => $resolvedSession,
            'class_filter_id' => $classId !== null && $classId > 0 ? $classId : null,
            'summary' => [
                'classes_count' => $teacherMarking->count(),
                'total_students' => (int) $teacherMarking->sum('active_students'),
                'present' => (int) $teacherMarking->sum('present'),
                'absent' => (int) $teacherMarking->sum('absent'),
                'leave' => (int) $teacherMarking->sum('leave'),
                'assigned_class_teachers' => $assignedClassTeachers,
                'classes_marked' => $classesMarked,
                'classes_not_marked' => $classesNotMarked,
                'unassigned_classes' => $unassignedClasses,
            ],
            'teacher_marking' => $teacherMarking,
            'absent_students' => $absentStudents,
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
