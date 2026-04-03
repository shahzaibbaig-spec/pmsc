<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\StudentSubjectAssignment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherCgpaRanking;
use App\Models\User;
use App\Services\GradeScaleService;
use App\Services\TeacherRankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeacherRankingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_classwise_and_overall_teacher_rankings_from_result_data(): void
    {
        $service = app(TeacherRankingService::class);
        $classEight = $this->createClass('8', 'A');
        $classNine = $this->createClass('9', 'A');
        $math = $this->createSubject('Mathematics', 'MTH');
        $english = $this->createSubject('English', 'ENG');
        $physics = $this->createSubject('Physics', 'PHY');

        [$teacherOne, $teacherTwo] = $this->createTeachers('Teacher One', 'Teacher Two');

        TeacherAssignment::query()->create([
            'teacher_id' => $teacherOne->id,
            'class_id' => $classEight->id,
            'subject_id' => $math->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherTwo->id,
            'class_id' => $classEight->id,
            'subject_id' => $english->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacherOne->id,
            'class_id' => $classNine->id,
            'subject_id' => $physics->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);

        $studentA = $this->createStudent($classEight, 'STD-8A-1', 'Student A');
        $studentB = $this->createStudent($classEight, 'STD-8A-2', 'Student B');
        $studentC = $this->createStudent($classNine, 'STD-9A-1', 'Student C');

        StudentSubjectAssignment::query()->create([
            'session' => '2025-2026',
            'student_id' => $studentC->id,
            'class_id' => $classNine->id,
            'subject_id' => $physics->id,
            'subject_group_id' => null,
            'assigned_by' => null,
        ]);

        $this->createExamWithMarks($classEight, $math, $teacherOne, '2025-2026', 'final_term', [
            [$studentA, 90, 100],
            [$studentB, 80, 100],
        ]);
        $this->createExamWithMarks($classEight, $english, $teacherTwo, '2025-2026', 'final_term', [
            [$studentA, 70, 100],
            [$studentB, 60, 100],
        ]);
        $this->createExamWithMarks($classNine, $physics, $teacherOne, '2025-2026', 'final_term', [
            [$studentC, 60, 100],
        ]);

        $service->storeTeacherCgpaRankings('2025-2026', 'final_term');

        $overall = TeacherCgpaRanking::query()
            ->where('session', '2025-2026')
            ->where('exam_type', 'final_term')
            ->where('ranking_scope', TeacherCgpaRanking::SCOPE_OVERALL)
            ->orderBy('rank_position')
            ->get();

        $this->assertCount(2, $overall);
        $this->assertSame($teacherOne->id, $overall[0]->teacher_id);
        $this->assertSame(1, $overall[0]->rank_position);
        $this->assertSame('76.67', (string) $overall[0]->average_percentage);
        $this->assertSame('100.00', (string) $overall[0]->pass_percentage);
        $this->assertSame('4.60', (string) $overall[0]->cgpa);
        $this->assertSame(3, $overall[0]->student_count);

        $this->assertSame($teacherTwo->id, $overall[1]->teacher_id);
        $this->assertSame(2, $overall[1]->rank_position);
        $this->assertSame('65.00', (string) $overall[1]->average_percentage);
        $this->assertSame('100.00', (string) $overall[1]->pass_percentage);
        $this->assertSame('3.90', (string) $overall[1]->cgpa);
        $this->assertSame(2, $overall[1]->student_count);

        $classwiseEight = TeacherCgpaRanking::query()
            ->where('session', '2025-2026')
            ->where('exam_type', 'final_term')
            ->where('ranking_scope', TeacherCgpaRanking::SCOPE_CLASSWISE)
            ->where('class_id', $classEight->id)
            ->orderBy('rank_position')
            ->get();

        $this->assertCount(2, $classwiseEight);
        $this->assertSame($teacherOne->id, $classwiseEight[0]->teacher_id);
        $this->assertSame(1, $classwiseEight[0]->rank_position);
        $this->assertSame('85.00', (string) $classwiseEight[0]->average_percentage);
        $this->assertSame('100.00', (string) $classwiseEight[0]->pass_percentage);

        $this->assertSame($teacherTwo->id, $classwiseEight[1]->teacher_id);
        $this->assertSame(2, $classwiseEight[1]->rank_position);
        $this->assertSame('65.00', (string) $classwiseEight[1]->average_percentage);
        $this->assertSame('100.00', (string) $classwiseEight[1]->pass_percentage);

        $service->storeTeacherCgpaRankings('2025-2026', 'final_term');

        $this->assertSame(
            5,
            TeacherCgpaRanking::query()
                ->where('session', '2025-2026')
                ->where('exam_type', 'final_term')
                ->count()
        );
    }

    public function test_rank_teachers_uses_all_requested_tie_breakers(): void
    {
        $service = app(TeacherRankingService::class);

        $ranked = $service->rankTeachers([
            [
                'teacher_id' => 6,
                'teacher_name' => 'Zara',
                'cgpa' => 5.10,
                'average_percentage' => 85.05,
                'u_grade_count' => 4,
                'top_grade_count' => 0,
                'pass_percentage' => 10.00,
                'student_count' => 1,
            ],
            [
                'teacher_id' => 1,
                'teacher_name' => 'Adeel',
                'cgpa' => 5.10,
                'average_percentage' => 85.00,
                'u_grade_count' => 0,
                'top_grade_count' => 3,
                'pass_percentage' => 80.00,
                'student_count' => 10,
            ],
            [
                'teacher_id' => 5,
                'teacher_name' => 'Basit',
                'cgpa' => 5.10,
                'average_percentage' => 85.00,
                'u_grade_count' => 0,
                'top_grade_count' => 2,
                'pass_percentage' => 100.00,
                'student_count' => 20,
            ],
            [
                'teacher_id' => 7,
                'teacher_name' => 'Danish',
                'cgpa' => 5.10,
                'average_percentage' => 85.00,
                'u_grade_count' => 0,
                'top_grade_count' => 2,
                'pass_percentage' => 95.00,
                'student_count' => 30,
            ],
            [
                'teacher_id' => 3,
                'teacher_name' => 'Ahmad',
                'cgpa' => 5.10,
                'average_percentage' => 85.00,
                'u_grade_count' => 0,
                'top_grade_count' => 2,
                'pass_percentage' => 80.00,
                'student_count' => 20,
            ],
            [
                'teacher_id' => 2,
                'teacher_name' => 'Hamza',
                'cgpa' => 5.10,
                'average_percentage' => 85.00,
                'u_grade_count' => 0,
                'top_grade_count' => 2,
                'pass_percentage' => 80.00,
                'student_count' => 20,
            ],
            [
                'teacher_id' => 4,
                'teacher_name' => 'Bilal',
                'cgpa' => 5.10,
                'average_percentage' => 85.00,
                'u_grade_count' => 1,
                'top_grade_count' => 9,
                'pass_percentage' => 100.00,
                'student_count' => 50,
            ],
        ]);

        $this->assertSame([6, 1, 5, 7, 3, 2, 4], array_column($ranked, 'teacher_id'));
        $this->assertSame([1, 2, 3, 4, 5, 6, 7], array_column($ranked, 'rank_position'));
    }

    public function test_it_includes_grade_classes_and_filters_unassigned_subject_group_students_for_grades_9_to_12(): void
    {
        $service = app(TeacherRankingService::class);
        $gradeOnlyClass = $this->createClass('1', 'A');
        $classTen = $this->createClass('10', 'A');
        $science = $this->createSubject('Science', 'SCI');
        $teacher = $this->createTeachers('Ranking Teacher')[0];

        TeacherAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $gradeOnlyClass->id,
            'subject_id' => $science->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);
        TeacherAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $classTen->id,
            'subject_id' => $science->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);

        $gradeStudentOne = $this->createStudent($gradeOnlyClass, 'STD-1A-1', 'Grade Student One');
        $gradeStudentTwo = $this->createStudent($gradeOnlyClass, 'STD-1A-2', 'Grade Student Two');
        $assignedStudent = $this->createStudent($classTen, 'STD-10A-1', 'Assigned Student');
        $unassignedStudent = $this->createStudent($classTen, 'STD-10A-2', 'Unassigned Student');

        $this->createExamWithGrades($gradeOnlyClass, $science, $teacher, '2025-2026', 'bimonthly_test', [
            [$gradeStudentOne, 'A*'],
            [$gradeStudentTwo, 'A'],
        ]);
        $this->createExamWithMarks($classTen, $science, $teacher, '2025-2026', 'bimonthly_test', [
            [$assignedStudent, 88, 100],
            [$unassignedStudent, 72, 100],
        ]);

        StudentSubjectAssignment::query()->create([
            'session' => '2025-2026',
            'student_id' => $assignedStudent->id,
            'class_id' => $classTen->id,
            'subject_id' => $science->id,
            'subject_group_id' => null,
            'assigned_by' => null,
        ]);

        $classwiseRows = collect($service->calculateTeacherClasswiseCgpa('2025-2026', 'bimonthly'));

        $gradeClassRow = $classwiseRows->firstWhere('class_id', $gradeOnlyClass->id);
        $numericClassRow = $classwiseRows->firstWhere('class_id', $classTen->id);

        $this->assertNotNull($gradeClassRow);
        $this->assertTrue((bool) $gradeClassRow['uses_grade_system']);
        $this->assertSame(2, $gradeClassRow['student_count']);
        $this->assertSame(0, $gradeClassRow['u_grade_count']);
        $this->assertSame(2, $gradeClassRow['top_grade_count']);
        $this->assertSame(100.00, (float) $gradeClassRow['pass_percentage']);
        $this->assertSame(91.50, (float) $gradeClassRow['average_percentage']);
        $this->assertSame(5.75, (float) $gradeClassRow['cgpa']);

        $this->assertNotNull($numericClassRow);
        $this->assertFalse((bool) $numericClassRow['uses_grade_system']);
        $this->assertSame(1, $numericClassRow['student_count']);
        $this->assertSame(88.00, (float) $numericClassRow['average_percentage']);
        $this->assertSame(5.28, (float) $numericClassRow['cgpa']);

        $service->storeTeacherCgpaRankings('2025-2026', 'bimonthly');

        $gradeRanking = TeacherCgpaRanking::query()
            ->where('session', '2025-2026')
            ->where('exam_type', 'bimonthly_test')
            ->where('ranking_scope', TeacherCgpaRanking::SCOPE_CLASSWISE)
            ->where('class_id', $gradeOnlyClass->id)
            ->sole();

        $this->assertSame(2, $gradeRanking->student_count);
        $this->assertSame('91.50', (string) $gradeRanking->average_percentage);
        $this->assertSame('100.00', (string) $gradeRanking->pass_percentage);
        $this->assertSame('5.75', (string) $gradeRanking->cgpa);

        $numericRanking = TeacherCgpaRanking::query()
            ->where('session', '2025-2026')
            ->where('exam_type', 'bimonthly_test')
            ->where('ranking_scope', TeacherCgpaRanking::SCOPE_CLASSWISE)
            ->where('class_id', $classTen->id)
            ->sole();

        $this->assertSame(1, $numericRanking->student_count);
        $this->assertSame('88.00', (string) $numericRanking->average_percentage);
        $this->assertSame('100.00', (string) $numericRanking->pass_percentage);
        $this->assertSame('5.28', (string) $numericRanking->cgpa);

        $overallRanking = TeacherCgpaRanking::query()
            ->where('session', '2025-2026')
            ->where('exam_type', 'bimonthly_test')
            ->where('ranking_scope', TeacherCgpaRanking::SCOPE_OVERALL)
            ->sole();

        $this->assertSame(3, $overallRanking->student_count);
        $this->assertSame('90.33', (string) $overallRanking->average_percentage);
        $this->assertSame('100.00', (string) $overallRanking->pass_percentage);
        $this->assertSame('5.59', (string) $overallRanking->cgpa);
    }

    public function test_it_falls_back_to_student_subjects_for_grades_9_to_12_when_matrix_assignments_do_not_exist(): void
    {
        $service = app(TeacherRankingService::class);
        $classNine = $this->createClass('9', 'B');
        $biology = $this->createSubject('Biology', 'BIO');
        $teacher = $this->createTeachers('Fallback Teacher')[0];

        TeacherAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $classNine->id,
            'subject_id' => $biology->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);

        $legacyAssignedStudent = $this->createStudent($classNine, 'STD-9B-1', 'Legacy Assigned');
        $unassignedStudent = $this->createStudent($classNine, 'STD-9B-2', 'Not Assigned');

        StudentSubject::query()->create([
            'student_id' => $legacyAssignedStudent->id,
            'subject_id' => $biology->id,
            'session' => '2025-2026',
        ]);

        $this->createExamWithMarks($classNine, $biology, $teacher, '2025-2026', 'first_term', [
            [$legacyAssignedStudent, 84, 100],
            [$unassignedStudent, 66, 100],
        ]);

        $service->storeTeacherCgpaRankings('2025-2026', 'first_term');

        $ranking = TeacherCgpaRanking::query()
            ->where('session', '2025-2026')
            ->where('exam_type', 'first_term')
            ->where('ranking_scope', TeacherCgpaRanking::SCOPE_CLASSWISE)
            ->where('class_id', $classNine->id)
            ->sole();

        $this->assertSame(1, $ranking->student_count);
        $this->assertSame('84.00', (string) $ranking->average_percentage);
        $this->assertSame('100.00', (string) $ranking->pass_percentage);
        $this->assertSame('5.04', (string) $ranking->cgpa);
    }

    public function test_grade_scale_service_maps_points_percentages_and_labels(): void
    {
        $service = app(GradeScaleService::class);

        $this->assertSame(6.00, $service->getGradePoint('A*'));
        $this->assertSame(56.00, $service->getPercentageEquivalent('e'));
        $this->assertSame('Very Good', $service->getOverallLabelFromCgpa(5.50));
        $this->assertSame('Ungraded / Unsatisfactory', $service->getOverallLabelFromCgpa(0.20));
    }

    public function test_snapshot_returns_live_preview_when_rankings_table_is_missing(): void
    {
        $service = app(TeacherRankingService::class);
        $classRoom = $this->createClass('8', 'A');
        $subject = $this->createSubject('Mathematics', 'MTH');
        $teacher = $this->createTeachers('Preview Teacher')[0];

        TeacherAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);

        $student = $this->createStudent($classRoom, 'STD-8A-PREVIEW', 'Preview Student');

        $this->createExamWithMarks($classRoom, $subject, $teacher, '2025-2026', 'final_term', [
            [$student, 78, 100],
        ]);

        Schema::drop('teacher_cgpa_rankings');

        $snapshot = $service->snapshot('2025-2026', 'final_term');

        $this->assertFalse($snapshot['schema_ready']);
        $this->assertTrue($snapshot['preview_mode']);
        $this->assertSame('live_preview', $snapshot['data_source']);
        $this->assertCount(1, $snapshot['overall']);
        $this->assertCount(1, $snapshot['classwise']);
        $this->assertSame(1, $snapshot['summary']['total_ranked_teachers']);
        $this->assertNotEmpty($snapshot['schema_message']);
    }

    public function test_snapshot_falls_back_to_live_preview_when_no_saved_rows_exist_yet(): void
    {
        $service = app(TeacherRankingService::class);
        $classRoom = $this->createClass('7', 'B');
        $subject = $this->createSubject('English', 'ENG');
        $teacher = $this->createTeachers('Unsaved Teacher')[0];

        TeacherAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'class_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'is_class_teacher' => false,
            'session' => '2025-2026',
        ]);

        $student = $this->createStudent($classRoom, 'STD-7B-UNSAVED', 'Unsaved Student');

        $this->createExamWithMarks($classRoom, $subject, $teacher, '2025-2026', 'class_test', [
            [$student, 67, 100],
        ]);

        $snapshot = $service->snapshot('2025-2026', 'class_test');

        $this->assertTrue($snapshot['schema_ready']);
        $this->assertTrue($snapshot['preview_mode']);
        $this->assertSame('live_preview', $snapshot['data_source']);
        $this->assertCount(1, $snapshot['overall']);
        $this->assertCount(1, $snapshot['classwise']);
        $this->assertStringContainsString('No saved teacher ranking snapshot exists', (string) $snapshot['schema_message']);
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

    /**
     * @return array<int, Teacher>
     */
    private function createTeachers(string ...$names): array
    {
        return collect($names)->map(function (string $name, int $index): Teacher {
            $user = User::factory()->create([
                'name' => $name,
                'email' => 'teacher-'.$index.'-'.strtolower(str_replace(' ', '-', $name)).'@example.test',
            ]);

            return Teacher::query()->create([
                'teacher_id' => 'T-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'designation' => 'Teacher',
                'employee_code' => 'EMP-'.($index + 1),
            ]);
        })->all();
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

    /**
     * @param array<int, array{0:Student,1:string}> $gradeRows
     */
    private function createExamWithGrades(
        SchoolClass $classRoom,
        Subject $subject,
        Teacher $teacher,
        string $session,
        string $examType,
        array $gradeRows
    ): Exam {
        $exam = Exam::query()->create([
            'class_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'exam_type' => $examType,
            'session' => $session,
            'total_marks' => null,
            'teacher_id' => $teacher->id,
        ]);

        foreach ($gradeRows as [$student, $grade]) {
            Mark::query()->create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'obtained_marks' => null,
                'grade' => $grade,
                'total_marks' => null,
                'teacher_id' => $teacher->id,
                'session' => $session,
            ]);
        }

        return $exam;
    }
}
