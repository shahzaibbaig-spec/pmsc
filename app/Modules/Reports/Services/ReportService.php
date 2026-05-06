<?php

namespace App\Modules\Reports\Services;

use App\Models\Attendance;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AssessmentMarkingModeService;
use App\Services\ClassAssessmentModeService;
use App\Modules\Exams\Enums\ExamType;
use App\Modules\Results\Services\ResultService;
use Illuminate\Support\Collection;
use RuntimeException;

class ReportService
{
    public function __construct(
        private readonly ResultService $resultService,
        private readonly AssessmentMarkingModeService $markingModeService,
        private readonly ClassAssessmentModeService $assessmentModeService
    )
    {
    }

    public function schoolMeta(): array
    {
        $setting = SchoolSetting::cached();

        $logoAbsolutePath = null;
        $logoUrl = null;
        $logoDataUri = null;

        $logoPath = trim((string) ($setting?->logo_path ?? ''));
        if ($logoPath !== '') {
            if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
                $logoUrl = $logoPath;
            } else {
                $logoUrl = asset('storage/'.$logoPath);

                $publicStoragePath = public_path('storage/'.$logoPath);
                $appStoragePath = storage_path('app/public/'.$logoPath);
                $resolvedFilePath = is_file($publicStoragePath)
                    ? $publicStoragePath
                    : (is_file($appStoragePath) ? $appStoragePath : null);

                if ($resolvedFilePath !== null) {
                    $logoAbsolutePath = $resolvedFilePath;

                    $binary = @file_get_contents($resolvedFilePath);
                    if ($binary !== false && $binary !== '') {
                        $mimeType = @mime_content_type($resolvedFilePath);
                        $resolvedMimeType = is_string($mimeType) && trim($mimeType) !== ''
                            ? $mimeType
                            : 'image/jpeg';
                        $logoDataUri = 'data:'.$resolvedMimeType.';base64,'.base64_encode($binary);
                    }
                }
            }
        }

        return [
            'name' => $setting?->school_name ?? 'School Management System',
            'logo_path' => $setting?->logo_path,
            'logo_url' => $logoUrl,
            'logo_absolute_path' => $logoDataUri ?? $logoUrl ?? $logoAbsolutePath,
        ];
    }

    public function classResultData(int $classId, string $session, string $examType, ?string $examLabel = null): array
    {
        $classRoom = SchoolClass::query()->find($classId);
        if (! $classRoom) {
            throw new RuntimeException('Class not found.');
        }

        $resolvedExamLabel = trim((string) $examLabel);
        if ($resolvedExamLabel === '' && $this->requiresLabelDisambiguation($examType)) {
            $labels = \App\Models\Exam::query()
                ->where('class_id', $classId)
                ->where('session', $session)
                ->where('exam_type', $examType)
                ->whereNotNull('exam_label')
                ->pluck('exam_label')
                ->map(fn ($label): string => trim((string) $label))
                ->filter(fn (string $label): bool => $label !== '')
                ->unique()
                ->values();

            if ($labels->count() > 1) {
                throw new RuntimeException('Multiple exam scopes were found for this exam type. Please select exam scope first.');
            }

            if ($labels->count() === 1) {
                $resolvedExamLabel = (string) $labels->first();
            }
        }

        $marks = Mark::query()
            ->with([
                'student:id,name,student_id',
                'exam:id,class_id,subject_id,exam_type,exam_label,session,marking_mode',
            ])
            ->where('session', $session)
            ->whereHas('exam', function ($query) use ($classId, $examType, $session, $resolvedExamLabel): void {
                $query->where('class_id', $classId)
                    ->where('exam_type', $examType)
                    ->where('session', $session)
                    ->when(
                        $resolvedExamLabel !== '',
                        fn ($builder) => $builder->where('exam_label', $resolvedExamLabel)
                    );
            })
            ->get();

        if ($marks->isEmpty()) {
            throw new RuntimeException('No marks found for this class/session/exam type.');
        }

        $grouped = $marks->groupBy('student_id');
        $markingMode = $this->markingModeService->resolveMarkingModeForExamContext($classId, $session, $examType);
        $usesGradeSystem = $markingMode === AssessmentMarkingModeService::MODE_GRADE;

        $students = $grouped->map(function (Collection $rows) use ($usesGradeSystem): array {
            $student = $rows->first()?->student;
            $grade = $usesGradeSystem
                ? $this->assessmentModeService->dominantGrade($rows->pluck('grade')->all())
                : null;
            $totalMarks = $usesGradeSystem ? null : (int) $rows->sum('total_marks');
            $obtainedMarks = $usesGradeSystem ? null : (int) $rows->sum('obtained_marks');
            $percentage = $usesGradeSystem || ! $totalMarks ? null : round(($obtainedMarks / $totalMarks) * 100, 2);

            return [
                'student_id' => $student?->student_id ?? '-',
                'student_name' => $student?->name ?? 'Student',
                'subjects_count' => $rows->count(),
                'total_marks' => $totalMarks,
                'obtained_marks' => $obtainedMarks,
                'percentage' => $percentage,
                'grade' => $usesGradeSystem ? $grade : $this->resultService->computeGrade((float) $percentage),
                'grade_label' => $usesGradeSystem ? $this->assessmentModeService->gradeLabel($grade) : null,
            ];
        })->sortBy('student_name')->values()->all();

        $classTeacher = TeacherAssignment::query()
            ->with('teacher.user:id,name')
            ->where('class_id', $classId)
            ->where('session', $session)
            ->where('is_class_teacher', true)
            ->first();

        $principal = User::role('Principal')->orderBy('id')->first(['id', 'name']);

        $totalMarksAll = $usesGradeSystem ? null : array_sum(array_map(fn (array $row): int => (int) ($row['total_marks'] ?? 0), $students));
        $obtainedMarksAll = $usesGradeSystem ? null : array_sum(array_map(fn (array $row): int => (int) ($row['obtained_marks'] ?? 0), $students));
        $overallPercentage = ! $usesGradeSystem && $totalMarksAll > 0
            ? round(($obtainedMarksAll / $totalMarksAll) * 100, 2)
            : null;
        $passCount = ! $usesGradeSystem
            ? collect($students)->filter(fn (array $row): bool => (float) ($row['percentage'] ?? 0) >= 60)->count()
            : null;
        $passRate = ! $usesGradeSystem && count($students) > 0 && $passCount !== null
            ? round(($passCount / count($students)) * 100, 2)
            : null;

        return [
            'school' => $this->schoolMeta(),
            'class' => [
                'id' => $classRoom->id,
                'name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
            ],
            'exam' => [
                'session' => $session,
                'exam_type' => $examType,
                'exam_type_label' => $resolvedExamLabel !== '' ? $resolvedExamLabel : $this->examTypeLabel($examType),
                'exam_label' => $resolvedExamLabel !== '' ? $resolvedExamLabel : null,
                'generated_at' => now()->toDateString(),
            ],
            'marking_mode' => $markingMode,
            'uses_grade_system' => $usesGradeSystem,
            'students' => $students,
            'summary' => [
                'students_count' => count($students),
                'total_marks' => $totalMarksAll,
                'obtained_marks' => $obtainedMarksAll,
                'overall_percentage' => $overallPercentage,
                'pass_rate' => $passRate,
            ],
            'signatures' => [
                'class_teacher' => $classTeacher?->teacher?->user?->name ?? 'Class Teacher',
                'principal' => $principal?->name ?? 'Principal',
            ],
        ];
    }

    public function attendanceReportData(string $date, ?int $classId = null): array
    {
        $attendanceQuery = Attendance::query()
            ->with([
                'student:id,name,student_id,class_id',
                'classRoom:id,name,section',
            ])
            ->whereDate('date', $date);

        $classMeta = null;
        if ($classId) {
            $attendanceQuery->where('class_id', $classId);
            $classRoom = SchoolClass::query()->find($classId);
            if (! $classRoom) {
                throw new RuntimeException('Class not found for attendance report.');
            }

            $classMeta = [
                'id' => $classRoom->id,
                'name' => trim($classRoom->name.' '.($classRoom->section ?? '')),
            ];
        }

        $rows = $attendanceQuery->orderBy('class_id')->orderBy('student_id')->get();

        $totalStudents = $classId
            ? Student::query()->where('class_id', $classId)->where('status', 'active')->count()
            : Student::query()->where('status', 'active')->count();

        $present = $rows->where('status', 'present')->count();
        $absent = $rows->where('status', 'absent')->count();
        $leave = $rows->where('status', 'leave')->count();

        return [
            'school' => $this->schoolMeta(),
            'date' => $date,
            'class' => $classMeta,
            'summary' => [
                'total_students' => $totalStudents,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
            ],
            'rows' => $rows->map(function (Attendance $record): array {
                return [
                    'student_id' => $record->student?->student_id ?? '-',
                    'student_name' => $record->student?->name ?? 'Student',
                    'class_name' => trim(($record->classRoom?->name ?? '').' '.($record->classRoom?->section ?? '')),
                    'status' => ucfirst((string) $record->status),
                ];
            })->values()->all(),
        ];
    }

    private function examTypeLabel(string $examType): string
    {
        $type = ExamType::tryFrom($examType);

        return $type?->label() ?? str_replace('_', ' ', ucfirst($examType));
    }

    private function requiresLabelDisambiguation(string $examType): bool
    {
        return in_array($examType, [ExamType::ClassTest->value, ExamType::BimonthlyTest->value], true);
    }
}
