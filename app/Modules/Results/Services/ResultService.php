<?php

namespace App\Modules\Results\Services;

use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AssessmentMarkingModeService;
use App\Services\ClassAssessmentModeService;
use App\Notifications\ResultsPublishedNotification;
use App\Modules\Exams\Enums\ExamType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class ResultService
{
    public function __construct(
        private readonly ClassAssessmentModeService $assessmentModeService,
        private readonly AssessmentMarkingModeService $markingModeService
    ) {}

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
                'exam:id,class_id,subject_id,exam_type,session,marking_mode',
                'exam.classRoom:id,name,section',
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

        $resultClass = $this->resolveResultClass($student, $session, $marks);
        $resultClassId = (int) ($resultClass?->id ?? $student->class_id);

        $markingMode = $this->markingModeService->resolveMarkingModeForExamContext(
            $resultClassId,
            $session,
            $examType
        );
        $usesGradeSystem = $markingMode === AssessmentMarkingModeService::MODE_GRADE;

        $rows = $marks->map(function (Mark $mark) use ($usesGradeSystem): array {
            $subjectName = $mark->exam?->subject?->name ?? 'Subject';
            $grade = $usesGradeSystem
                ? $this->assessmentModeService->normalizeGrade($mark->grade)
                : null;
            $total = $usesGradeSystem ? null : (int) $mark->total_marks;
            $obtained = $usesGradeSystem ? null : (int) $mark->obtained_marks;
            $percentage = $usesGradeSystem
                ? null
                : ($total > 0 ? round(($obtained / $total) * 100, 2) : 0.0);

            return [
                'subject' => $subjectName,
                'total_marks' => $total,
                'obtained_marks' => $obtained,
                'percentage' => $percentage,
                'grade' => $usesGradeSystem ? $grade : $this->computeGrade((float) $percentage),
                'grade_label' => $usesGradeSystem
                    ? $this->assessmentModeService->gradeLabel($grade)
                    : null,
            ];
        })->sortBy('subject')->values();

        $summary = $usesGradeSystem
            ? $this->gradeSummary($rows)
            : $this->numericSummary($rows);

        $classTeacher = TeacherAssignment::query()
            ->with('teacher.user:id,name')
            ->where('class_id', $resultClassId)
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
                'class' => trim((string) ($resultClass?->name ?? '').' '.(string) ($resultClass?->section ?? '')),
                'age' => $this->resolveAge($student->age, $student->date_of_birth),
            ],
            'exam' => [
                'session' => $session,
                'exam_type' => $examType,
                'exam_type_label' => $this->examTypeLabel($examType),
                'generated_at' => now()->toDateString(),
            ],
            'uses_grade_system' => $usesGradeSystem,
            'marking_mode' => $markingMode,
            'subjects' => $rows->all(),
            'summary' => $summary,
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

        $students = $this->studentRosterForClassSession($classId, $session);

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
        $markingMode = $this->markingModeService->resolveMarkingModeForExamContext($classId, $session, $examType);

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
            'uses_grade_system' => $markingMode === AssessmentMarkingModeService::MODE_GRADE,
            'marking_mode' => $markingMode,
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

    private function resolveResultClass(Student $student, string $session, Collection $marks): ?SchoolClass
    {
        $examClassId = $marks
            ->pluck('exam.class_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->first();

        if ($examClassId !== null) {
            return SchoolClass::query()->find($examClassId, ['id', 'name', 'section']);
        }

        $history = StudentClassHistory::query()
            ->where('student_id', (int) $student->id)
            ->where('session', $session)
            ->with('classRoom:id,name,section')
            ->latest('joined_on')
            ->first();

        if ($history?->classRoom !== null) {
            return $history->classRoom;
        }

        return $student->classRoom;
    }

    private function studentRosterForClassSession(int $classId, string $session): Collection
    {
        $studentIds = StudentClassHistory::query()
            ->where('class_id', $classId)
            ->where('session', $session)
            ->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($studentIds->isNotEmpty()) {
            return Student::query()
                ->whereIn('id', $studentIds)
                ->orderBy('name')
                ->orderBy('student_id')
                ->get(['id', 'student_id', 'name']);
        }

        return Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'student_id', 'name']);
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

    private function numericSummary(Collection $rows): array
    {
        $totalMarks = (int) $rows->sum('total_marks');
        $obtainedMarks = (int) $rows->sum('obtained_marks');
        $overallPercentage = $totalMarks > 0 ? round(($obtainedMarks / $totalMarks) * 100, 2) : 0.0;

        return [
            'total_marks' => $totalMarks,
            'obtained_marks' => $obtainedMarks,
            'percentage' => $overallPercentage,
            'grade' => $this->computeGrade($overallPercentage),
            'grade_label' => null,
            'overall_performance' => null,
        ];
    }

    private function gradeSummary(Collection $rows): array
    {
        $dominantGrade = $this->assessmentModeService->dominantGrade($rows->pluck('grade')->all());

        return [
            'total_marks' => null,
            'obtained_marks' => null,
            'percentage' => null,
            'grade' => $dominantGrade,
            'grade_label' => $this->assessmentModeService->gradeLabel($dominantGrade),
            'overall_performance' => $this->assessmentModeService->overallPerformanceLabel($rows->pluck('grade')->all()),
        ];
    }
}
