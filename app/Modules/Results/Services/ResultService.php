<?php

namespace App\Modules\Results\Services;

use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Notifications\ResultsPublishedNotification;
use App\Modules\Exams\Enums\ExamType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class ResultService
{
    public function generateStudentResult(int $studentId, string $session, string $examType): array
    {
        $student = Student::query()
            ->with('classRoom:id,name,section')
            ->find($studentId);

        if (! $student) {
            throw new RuntimeException('Student not found.');
        }

        $marks = Mark::query()
            ->with([
                'exam:id,subject_id,exam_type,session',
                'exam.subject:id,name',
            ])
            ->where('student_id', $studentId)
            ->where('session', $session)
            ->whereHas('exam', function ($query) use ($examType, $session): void {
                $query->where('exam_type', $examType)
                    ->where('session', $session);
            })
            ->get();

        if ($marks->isEmpty()) {
            throw new RuntimeException('No marks found for selected student, session, and exam type.');
        }

        $rows = $marks->map(function (Mark $mark): array {
            $subjectName = $mark->exam?->subject?->name ?? 'Subject';
            $total = (int) $mark->total_marks;
            $obtained = (int) $mark->obtained_marks;
            $percentage = $total > 0 ? round(($obtained / $total) * 100, 2) : 0.0;

            return [
                'subject' => $subjectName,
                'total_marks' => $total,
                'obtained_marks' => $obtained,
                'percentage' => $percentage,
                'grade' => $this->computeGrade($percentage),
            ];
        })->sortBy('subject')->values();

        $totalMarks = (int) $rows->sum('total_marks');
        $obtainedMarks = (int) $rows->sum('obtained_marks');
        $overallPercentage = $totalMarks > 0 ? round(($obtainedMarks / $totalMarks) * 100, 2) : 0.0;

        $classTeacher = TeacherAssignment::query()
            ->with('teacher.user:id,name')
            ->where('class_id', $student->class_id)
            ->where('session', $session)
            ->where('is_class_teacher', true)
            ->first();

        $principal = User::role('Principal')->orderBy('id')->first(['id', 'name']);
        $setting = $this->schoolSetting();

        return [
            'school' => [
                'name' => $setting?->school_name ?? 'School Management System',
                'logo_path' => $setting?->logo_path,
            ],
            'student' => [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->name,
                'class' => trim(($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? '')),
                'age' => $this->resolveAge($student->age, $student->date_of_birth),
            ],
            'exam' => [
                'session' => $session,
                'exam_type' => $examType,
                'exam_type_label' => $this->examTypeLabel($examType),
                'generated_at' => now()->toDateString(),
            ],
            'subjects' => $rows->all(),
            'summary' => [
                'total_marks' => $totalMarks,
                'obtained_marks' => $obtainedMarks,
                'percentage' => $overallPercentage,
                'grade' => $this->computeGrade($overallPercentage),
            ],
            'signatures' => [
                'class_teacher' => $classTeacher?->teacher?->user?->name ?? 'Class Teacher',
                'principal' => $principal?->name ?? 'Principal',
            ],
        ];
    }

    public function generateClassResultCards(int $classId, string $session, string $examType): array
    {
        $classRoom = SchoolClass::query()
            ->find($classId, ['id', 'name', 'section']);

        if (! $classRoom) {
            throw new RuntimeException('Class not found.');
        }

        $students = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'student_id', 'name']);

        if ($students->isEmpty()) {
            throw new RuntimeException('No active students found for selected class.');
        }

        $cards = [];
        foreach ($students as $student) {
            try {
                $cards[] = $this->generateStudentResult((int) $student->id, $session, $examType);
            } catch (RuntimeException) {
                // Skip students without marks for selected session/exam.
            }
        }

        if ($cards === []) {
            throw new RuntimeException('No result cards could be generated for this class/session/exam type.');
        }

        $setting = $this->schoolSetting();

        return [
            'school' => [
                'name' => $setting?->school_name ?? 'School Management System',
                'logo_path' => $setting?->logo_path,
            ],
            'class' => [
                'id' => (int) $classRoom->id,
                'name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
            ],
            'exam' => [
                'session' => $session,
                'exam_type' => $examType,
                'exam_type_label' => $this->examTypeLabel($examType),
                'generated_at' => now()->toDateString(),
            ],
            'summary' => [
                'students_total' => $students->count(),
                'cards_generated' => count($cards),
            ],
            'cards' => $cards,
        ];
    }

    public function computeGrade(float $percentage): string
    {
        if ($percentage >= 90) {
            return 'A*';
        }

        if ($percentage >= 80) {
            return 'A';
        }

        if ($percentage >= 70) {
            return 'B+';
        }

        if ($percentage >= 60) {
            return 'B';
        }

        return 'Fail';
    }

    public function publishResults(int $publisherUserId, int $classId, string $session, string $examType): array
    {
        $classRoom = SchoolClass::query()->find($classId, ['id', 'name', 'section']);
        if (! $classRoom) {
            throw new RuntimeException('Class not found.');
        }

        $marksCount = Mark::query()
            ->whereHas('exam', function ($query) use ($classId, $session, $examType): void {
                $query->where('class_id', $classId)
                    ->where('session', $session)
                    ->where('exam_type', $examType);
            })
            ->count();

        if ($marksCount === 0) {
            throw new RuntimeException('No marks found for this class/session/exam type.');
        }

        $teacherUserIds = TeacherAssignment::query()
            ->with('teacher:id,user_id')
            ->where('class_id', $classId)
            ->where('session', $session)
            ->get()
            ->pluck('teacher.user_id')
            ->filter()
            ->map(fn ($id): int => (int) $id);

        $adminUserIds = User::role('Admin')->pluck('id')->map(fn ($id): int => (int) $id);
        $recipientIds = $teacherUserIds
            ->merge($adminUserIds)
            ->unique()
            ->reject(fn (int $id): bool => $id === $publisherUserId)
            ->values();

        if ($recipientIds->isEmpty()) {
            return [
                'notified_users' => 0,
                'class_name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
                'session' => $session,
                'exam_type' => $examType,
                'exam_type_label' => $this->examTypeLabel($examType),
            ];
        }

        $recipients = User::query()
            ->whereIn('id', $recipientIds)
            ->where(function ($query): void {
                $query->where('status', 'active')
                    ->orWhereNull('status');
            })
            ->get(['id', 'name', 'email']);

        if ($recipients->isEmpty()) {
            return [
                'notified_users' => 0,
                'class_name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
                'session' => $session,
                'exam_type' => $examType,
                'exam_type_label' => $this->examTypeLabel($examType),
            ];
        }

        $publisher = User::query()->find($publisherUserId, ['id', 'name']);
        $notificationPayload = [
            'class_id' => $classRoom->id,
            'class_name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
            'session' => $session,
            'exam_type' => $examType,
            'exam_type_label' => $this->examTypeLabel($examType),
            'published_by' => $publisher?->name ?? 'Principal',
            'published_at' => now()->toDateTimeString(),
            'url' => route('dashboard'),
        ];

        Notification::send($recipients, new ResultsPublishedNotification($notificationPayload));

        return [
            'notified_users' => $recipients->count(),
            'class_name' => $notificationPayload['class_name'],
            'session' => $session,
            'exam_type' => $examType,
            'exam_type_label' => $notificationPayload['exam_type_label'],
        ];
    }

    private function schoolSetting(): ?SchoolSetting
    {
        return SchoolSetting::cached();
    }

    private function examTypeLabel(string $examType): string
    {
        $type = ExamType::tryFrom($examType);

        return $type?->label() ?? str_replace('_', ' ', ucfirst($examType));
    }

    private function resolveAge(?int $age, $dateOfBirth): ?int
    {
        if ($age !== null) {
            return (int) $age;
        }

        if (! $dateOfBirth) {
            return null;
        }

        $dob = $dateOfBirth instanceof Carbon ? $dateOfBirth : Carbon::parse($dateOfBirth);

        return $dob->age;
    }
}
