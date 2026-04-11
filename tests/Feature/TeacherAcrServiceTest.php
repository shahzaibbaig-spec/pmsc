<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAcr;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\TeacherAcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAcrServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_teacher_acr_draft_from_existing_metrics(): void
    {
        $service = app(TeacherAcrService::class);
        $classRoom = $this->createClass('8', 'A');
        $subject = $this->createSubject('Mathematics', 'MTH');
        $teacher = $this->createTeacher('Adeel');

        TeacherAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);

        $studentOne = $this->createStudent($classRoom, 'STD-8A-1', 'Student One');
        $studentTwo = $this->createStudent($classRoom, 'STD-8A-2', 'Student Two');

        $this->createAttendanceRow($studentOne, $classRoom, '2025-07-05', 'present');
        $this->createAttendanceRow($studentTwo, $classRoom, '2025-07-05', 'present');
        $this->createAttendanceRow($studentOne, $classRoom, '2025-07-06', 'present');
        $this->createAttendanceRow($studentTwo, $classRoom, '2025-07-06', 'absent');

        $this->createExamWithMarks($classRoom, $subject, $teacher, '2025-2026', 'first_term', [
            [$studentOne, 60, 100],
            [$studentTwo, 70, 100],
        ]);
        $this->createExamWithMarks($classRoom, $subject, $teacher, '2025-2026', 'final_term', [
            [$studentOne, 80, 100],
            [$studentTwo, 90, 100],
        ]);

        $result = $service->generateDraftAcr($teacher->id, '2025-2026');

        $this->assertTrue((bool) ($result['created'] ?? false));

        $acr = TeacherAcr::query()->with('metric')->where('teacher_id', $teacher->id)->where('session', '2025-2026')->sole();

        $this->assertSame(TeacherAcr::STATUS_DRAFT, $acr->status);
        $this->assertNull($acr->final_grade);
        $this->assertSame(11.25, round((float) $acr->attendance_score, 2));
        $this->assertSame(24.75, round((float) $acr->academic_score, 2));
        $this->assertSame(15.00, round((float) $acr->improvement_score, 2));
        $this->assertSame(5.00, round((float) $acr->pd_score, 2));
        $this->assertSame(56.00, round((float) $acr->total_score, 2));
        $this->assertNotEmpty($acr->strengths);
        $this->assertNotEmpty($acr->areas_for_improvement);
        $this->assertNotEmpty($acr->recommendations);

        $this->assertNotNull($acr->metric);
        $this->assertSame(75.00, round((float) $acr->metric->attendance_percentage, 2));
        $this->assertSame(4.50, round((float) $acr->metric->teacher_cgpa, 2));
        $this->assertSame(100.00, round((float) $acr->metric->pass_percentage, 2));
        $this->assertSame(100.00, round((float) $acr->metric->student_improvement_percentage, 2));
        $this->assertSame(0, (int) $acr->metric->trainings_attended);
    }

    public function test_it_reviews_finalizes_and_preserves_finalized_acrs_from_regeneration(): void
    {
        $service = app(TeacherAcrService::class);
        $classRoom = $this->createClass('7', 'B');
        $subject = $this->createSubject('English', 'ENG');
        $teacher = $this->createTeacher('Bilal');
        $principal = User::factory()->create([
            'name' => 'Principal Reviewer',
            'email' => 'principal-reviewer@example.test',
        ]);

        TeacherAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);

        $student = $this->createStudent($classRoom, 'STD-7B-1', 'Student A');
        $this->createAttendanceRow($student, $classRoom, '2025-08-01', 'present');
        $this->createExamWithMarks($classRoom, $subject, $teacher, '2025-2026', 'first_term', [
            [$student, 75, 100],
        ]);
        $this->createExamWithMarks($classRoom, $subject, $teacher, '2025-2026', 'final_term', [
            [$student, 82, 100],
        ]);

        $service->generateDraftAcr($teacher->id, '2025-2026');

        $acr = TeacherAcr::query()->where('teacher_id', $teacher->id)->where('session', '2025-2026')->sole();

        $service->savePrincipalReview($acr->id, [
            'conduct_score' => 12,
            'principal_score' => 14,
            'strengths' => 'Calm and dependable classroom presence.',
            'areas_for_improvement' => 'Needs stronger enrichment planning.',
            'recommendations' => 'Use monthly differentiation checkpoints.',
            'confidential_remarks' => 'Ready for broader leadership tasks.',
        ], $principal->id);

        $acr->refresh();

        $this->assertSame(TeacherAcr::STATUS_REVIEWED, $acr->status);
        $this->assertSame(12.00, round((float) $acr->conduct_score, 2));
        $this->assertSame(14.00, round((float) $acr->principal_score, 2));
        $this->assertSame('Very Good', $acr->final_grade);
        $this->assertNotNull($acr->reviewed_at);
        $this->assertSame($principal->id, $acr->reviewed_by);

        $service->finalizeAcr($acr->id, $principal->id);

        $acr->refresh();

        $this->assertSame(TeacherAcr::STATUS_FINALIZED, $acr->status);
        $this->assertNotNull($acr->finalized_at);

        $result = $service->generateDraftAcr($teacher->id, '2025-2026');

        $acr->refresh();

        $this->assertSame('finalized', $result['skipped_reason']);
        $this->assertSame(TeacherAcr::STATUS_FINALIZED, $acr->status);
        $this->assertSame('Ready for broader leadership tasks.', $acr->confidential_remarks);
    }

    private function createClass(string $name, string $section): SchoolClass
    {
        return SchoolClass::query()->create([
            'name' => $name,
            'section' => $section,
            'status' => 'active',
        ]);
    }

    private function createSubject(string $name, string $code): Subject
    {
        return Subject::query()->create([
            'name' => $name,
            'code' => $code,
            'status' => 'active',
        ]);
    }

    private function createTeacher(string $name): Teacher
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'@example.test',
        ]);

        return Teacher::query()->create([
            'teacher_id' => 'T-'.str_pad((string) ((int) Teacher::query()->count() + 1), 4, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'designation' => 'Teacher',
            'employee_code' => 'EMP-'.str_pad((string) $user->id, 3, '0', STR_PAD_LEFT),
        ]);
    }

    private function createStudent(SchoolClass $classRoom, string $studentCode, string $name): Student
    {
        return Student::query()->create([
            'student_id' => $studentCode,
            'name' => $name,
            'class_id' => $classRoom->id,
            'status' => 'active',
        ]);
    }

    private function createAttendanceRow(Student $student, SchoolClass $classRoom, string $date, string $status): void
    {
        Attendance::query()->create([
            'student_id' => $student->id,
            'class_id' => $classRoom->id,
            'date' => $date,
            'status' => $status,
        ]);
    }

    /**
     * @param array<int, array{0:Student,1:int,2:int}> $markRows
     */
    private function createExamWithMarks(
        SchoolClass $classRoom,
        Subject $subject,
        Teacher $teacher,
        string $session,
        string $examType,
        array $markRows
    ): Exam {
        $exam = Exam::query()->create([
            'class_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'exam_type' => $examType,
            'session' => $session,
            'total_marks' => 100,
            'teacher_id' => $teacher->id,
        ]);

        foreach ($markRows as [$student, $obtainedMarks, $totalMarks]) {
            Mark::query()->create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'obtained_marks' => $obtainedMarks,
                'grade' => null,
                'total_marks' => $totalMarks,
                'teacher_id' => $teacher->id,
                'session' => $session,
            ]);
        }

        return $exam;
    }
}
