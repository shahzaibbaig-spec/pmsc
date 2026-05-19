<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentDisciplineReport;
use App\Models\StudentSubject;
use App\Models\StudentSubjectAssignment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Notifications\StudentDisciplineReportCreatedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StudentDisciplineReportService
{
    public function __construct(
        private readonly DailyDiaryService $dailyDiaryService,
        private readonly TeacherStudentVisibilityService $visibilityService
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array{id:int,text:string,student_name:string,admission_no:string,class_section:string,father_name:string,roll_number:string,subjects:array<int, array{id:int,name:string}>}>
     */
    public function searchStudentsForTeacher(User $teacher, string $term, array $filters = []): array
    {
        $needle = trim($term);
        if ($needle === '' || mb_strlen($needle) < 2) {
            return [];
        }

        $normalized = $this->normalizeFilters($filters);
        $session = (string) $normalized['session'];
        $assignmentContext = $this->teacherAssignmentContext($teacher, $session);
        $allowedClassIds = array_keys($assignmentContext['subject_ids_by_class']);
        if ($allowedClassIds === []) {
            return [];
        }

        $limit = isset($filters['limit']) ? max(5, min((int) $filters['limit'], 50)) : 20;
        $contains = '%'.$needle.'%';
        $prefix = $needle.'%';
        $hasRollNumber = Schema::hasTable('students') && Schema::hasColumn('students', 'roll_number');

        $studentColumns = ['id', 'student_id', 'name', 'father_name', 'class_id'];
        if ($hasRollNumber) {
            $studentColumns[] = 'roll_number';
        }

        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->where('status', 'active')
            ->where(function (Builder $query) use ($allowedClassIds, $session): void {
                $query->whereIn('class_id', $allowedClassIds)
                    ->orWhereHas('classHistories', function (Builder $historyQuery) use ($allowedClassIds, $session): void {
                        $historyQuery->where('session', $session)
                            ->whereIn('class_id', $allowedClassIds);
                    });
            })
            ->where(function (Builder $query) use ($contains, $prefix, $hasRollNumber): void {
                $query->where('name', 'like', $contains)
                    ->orWhere('student_id', 'like', $prefix)
                    ->orWhere('father_name', 'like', $contains)
                    ->orWhereHas('classRoom', function (Builder $classQuery) use ($contains): void {
                        $classQuery->where('name', 'like', $contains)
                            ->orWhere('section', 'like', $contains);
                    });

                if ($hasRollNumber) {
                    $query->orWhere('roll_number', 'like', $prefix);
                }
            })
            ->orderByRaw("CASE WHEN student_id LIKE ? THEN 0 ELSE 1 END", [$prefix])
            ->orderBy('name')
            ->limit($limit)
            ->get($studentColumns);

        $sessionClassMap = $this->sessionClassMap(
            $students->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            $session
        );

        return $students
            ->map(function (Student $student) use ($sessionClassMap, $assignmentContext, $hasRollNumber): array {
                $resolvedClass = $sessionClassMap[(int) $student->id] ?? null;
                $classId = is_array($resolvedClass)
                    ? (int) ($resolvedClass['class_id'] ?? 0)
                    : (int) $student->class_id;
                $classSection = is_array($resolvedClass)
                    ? trim((string) ($resolvedClass['class_section'] ?? ''))
                    : trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? ''));
                $subjectOptions = $assignmentContext['subjects_by_class'][$classId] ?? [];

                $studentName = (string) $student->name;
                $admissionNo = (string) $student->student_id;

                return [
                    'id' => (int) $student->id,
                    'text' => trim($studentName.' | '.$admissionNo.' | '.$classSection),
                    'student_name' => $studentName,
                    'admission_no' => $admissionNo,
                    'class_section' => $classSection,
                    'father_name' => (string) ($student->father_name ?? ''),
                    'roll_number' => (string) ((($hasRollNumber ? ($student->roll_number ?? '') : '') ?: $student->student_id) ?? ''),
                    'subjects' => $subjectOptions,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createReport(array $data, User $teacher): StudentDisciplineReport
    {
        $report = $this->createReports($data, $teacher)->first();

        if (! $report instanceof StudentDisciplineReport) {
            throw ValidationException::withMessages([
                'student_ids' => 'Please select at least one student.',
            ]);
        }

        return $report;
    }

    /**
     * @param array<string, mixed> $data
     * @return Collection<int, StudentDisciplineReport>
     */
    public function createReports(array $data, User $teacher): Collection
    {
        $session = $this->resolveSession(isset($data['session']) ? (string) $data['session'] : null);
        $studentIds = $this->extractRequestedStudentIds($data);
        if ($studentIds === []) {
            throw ValidationException::withMessages([
                'student_ids' => 'Please select at least one student.',
            ]);
        }

        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy(fn (Student $student): int => (int) $student->id);
        if ($students->count() !== count($studentIds)) {
            throw ValidationException::withMessages([
                'student_ids' => 'One or more selected students could not be found.',
            ]);
        }

        $subjectId = isset($data['subject_id']) && $data['subject_id'] !== '' ? (int) $data['subject_id'] : null;
        $assignmentContext = $this->teacherAssignmentContext($teacher, $session);

        $issueType = trim((string) ($data['issue_type'] ?? ''));
        if (! array_key_exists($issueType, StudentDisciplineReport::ISSUE_LABELS)) {
            throw ValidationException::withMessages([
                'issue_type' => 'Please select a valid discipline issue.',
            ]);
        }

        $severity = trim((string) ($data['severity'] ?? StudentDisciplineReport::SEVERITY_NORMAL));
        if (! in_array($severity, StudentDisciplineReport::SEVERITIES, true)) {
            $severity = StudentDisciplineReport::SEVERITY_NORMAL;
        }

        $reportDate = isset($data['report_date']) && trim((string) $data['report_date']) !== ''
            ? Carbon::parse((string) $data['report_date'])->toDateString()
            : now()->toDateString();

        $description = trim((string) ($data['description'] ?? '')) ?: null;
        $allowDuplicate = in_array($severity, [
            StudentDisciplineReport::SEVERITY_REPEATED,
            StudentDisciplineReport::SEVERITY_SERIOUS,
            StudentDisciplineReport::SEVERITY_URGENT,
        ], true) || (bool) ($data['confirm_duplicate'] ?? false);

        $subject = $subjectId !== null
            ? Subject::query()->find($subjectId)
            : null;

        $preparedRows = [];
        $duplicateStudentNames = [];
        foreach ($studentIds as $studentId) {
            /** @var Student|null $student */
            $student = $students->get($studentId);
            if (! $student instanceof Student) {
                continue;
            }

            $resolvedClassMeta = $this->resolveClassMetaForStudentSession($student, $session);
            $resolvedClassId = is_array($resolvedClassMeta)
                ? (int) ($resolvedClassMeta['class_id'] ?? 0)
                : (int) $student->class_id;
            $allowedSubjectIds = $assignmentContext['subject_ids_by_class'][$resolvedClassId] ?? [];
            $studentName = trim((string) $student->name) !== '' ? trim((string) $student->name) : 'selected student';

            if (! $this->canTeacherReportStudentWithContext($assignmentContext, $student, $session, $subjectId)) {
                throw ValidationException::withMessages([
                    'student_ids' => 'You are not allowed to report '.$studentName.' for the selected session.',
                ]);
            }

            if ($subjectId === null && count($allowedSubjectIds) > 1) {
                throw ValidationException::withMessages([
                    'subject_id' => 'Please select a subject for '.$studentName.'.',
                ]);
            }

            if ($subjectId !== null && ! in_array($subjectId, $allowedSubjectIds, true)) {
                throw ValidationException::withMessages([
                    'subject_id' => 'You are not assigned the selected subject for '.$studentName.'.',
                ]);
            }

            if ($subjectId !== null && $this->visibilityService->classRequiresSubjectFiltering($resolvedClassId)) {
                $classIdForSubjectValidation = $resolvedClassId > 0 ? $resolvedClassId : (int) $student->class_id;
                if (! $this->studentHasSubjectForSession((int) $student->id, $classIdForSubjectValidation, $subjectId, $session)) {
                    throw ValidationException::withMessages([
                        'subject_id' => 'Selected subject is not assigned to '.$studentName.' for the selected session.',
                    ]);
                }
            }

            $duplicateExists = StudentDisciplineReport::query()
                ->where('student_id', (int) $student->id)
                ->where('teacher_id', (int) $teacher->id)
                ->where('issue_type', $issueType)
                ->whereDate('report_date', $reportDate)
                ->where('session', $session)
                ->exists();
            if ($duplicateExists && ! $allowDuplicate) {
                $duplicateStudentNames[] = $studentName;
            }

            $preparedRows[] = [
                'student_id' => (int) $student->id,
                'class_id' => $resolvedClassId > 0 ? $resolvedClassId : null,
                'subject_id' => $subjectId,
                'teacher_id' => (int) $teacher->id,
                'session' => $session,
                'report_date' => $reportDate,
                'issue_type' => $issueType,
                'issue_label' => StudentDisciplineReport::issueLabelFor($issueType),
                'severity' => $severity,
                'description' => $description,
                'auto_message' => $this->generateAutoMessage($student, $teacher, $issueType, $description, $subject),
                'status' => StudentDisciplineReport::STATUS_OPEN,
                'created_by' => (int) $teacher->id,
                'updated_by' => (int) $teacher->id,
            ];
        }

        if ($duplicateStudentNames !== [] && ! $allowDuplicate) {
            throw ValidationException::withMessages([
                'duplicate_warning' => 'A similar report already exists today for: '.implode(', ', $duplicateStudentNames).'. Choose severity Repeated/Serious/Urgent or confirm duplicate to continue.',
            ]);
        }

        $reportIds = DB::transaction(function () use ($preparedRows): array {
            $ids = [];
            foreach ($preparedRows as $row) {
                /** @var StudentDisciplineReport $report */
                $report = StudentDisciplineReport::query()->create($row);
                $this->notifyPrincipalAndWardens($report);
                $ids[] = (int) $report->id;
            }

            return $ids;
        });

        $relations = [
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'subject:id,name',
            'teacher:id,name',
            'acknowledgedBy:id,name',
            'resolvedBy:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
        ];

        $positionById = array_flip($reportIds);

        return StudentDisciplineReport::query()
            ->with($relations)
            ->whereIn('id', $reportIds)
            ->get()
            ->sortBy(fn (StudentDisciplineReport $report): int => (int) ($positionById[(int) $report->id] ?? PHP_INT_MAX))
            ->values();
    }

    public function teacherCanReportStudent(User $teacher, Student $student, ?int $subjectId = null): bool
    {
        $session = $this->resolveSession(null);
        $assignmentContext = $this->teacherAssignmentContext($teacher, $session);

        return $this->canTeacherReportStudentWithContext($assignmentContext, $student, $session, $subjectId);
    }

    public function generateAutoMessage(
        Student $student,
        User $teacher,
        string $issueType,
        ?string $description = null,
        ?Subject $subject = null
    ): string {
        $studentName = trim((string) $student->name) !== '' ? trim((string) $student->name) : 'Student';
        $teacherName = trim((string) $teacher->name) !== '' ? trim((string) $teacher->name) : 'Teacher';
        $classSection = trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? ''));
        if ($classSection === '') {
            $classSection = 'Unknown Class';
        }

        $subjectName = trim((string) ($subject?->name ?? ''));
        $template = $this->issueTemplates()[$issueType]
            ?? '{student_name} of {class_section} was reported by {teacher_name} for a discipline concern.';

        $message = str_replace(
            ['{student_name}', '{class_section}', '{teacher_name}', '{subject_name}'],
            [$studentName, $classSection, $teacherName, $subjectName !== '' ? $subjectName : 'the subject'],
            $template
        );

        $note = trim((string) $description);
        if ($note !== '') {
            $message .= ' Note: '.$note;
        }

        return $message;
    }

    public function notifyPrincipalAndWardens(StudentDisciplineReport $report): void
    {
        $report->loadMissing([
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'subject:id,name',
            'teacher:id,name',
        ]);

        $principalAndAdmin = $this->activeUsersByRoles(['Principal', 'Admin']);
        $wardens = $this->activeUsersByRoles(['Warden']);

        $principalAndAdmin->each(fn (User $user) => $user->notify(new StudentDisciplineReportCreatedNotification($report)));
        $wardens->each(fn (User $user) => $user->notify(new StudentDisciplineReportCreatedNotification($report)));
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     reports:LengthAwarePaginator,
     *     filters:array<string, mixed>,
     *     cards:array<string, mixed>,
     *     sessions:array<int, string>,
     *     classes:array<int, array{id:int,name:string}>,
     *     subjects_by_class:array<int, array<int, array{id:int,name:string}>>,
     *     issue_options:array<string, string>,
     *     severity_options:array<int, string>,
     *     status_options:array<int, string>
     * }
     */
    public function getTeacherReports(User $teacher, array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $session = (string) $normalized['session'];
        $assignmentContext = $this->teacherAssignmentContext($teacher, $session);

        $query = $this->baseReportQuery()
            ->where('teacher_id', (int) $teacher->id);

        $this->applyReportFilters($query, $normalized);

        $reports = $query
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->paginate((int) $normalized['per_page'])
            ->withQueryString();

        $today = now()->toDateString();
        $mostCommonIssueToday = StudentDisciplineReport::query()
            ->where('teacher_id', (int) $teacher->id)
            ->whereDate('report_date', $today)
            ->select('issue_type', DB::raw('COUNT(*) as total'))
            ->groupBy('issue_type')
            ->orderByDesc('total')
            ->value('issue_type');

        $cards = [
            'today_reports' => StudentDisciplineReport::query()
                ->where('teacher_id', (int) $teacher->id)
                ->whereDate('report_date', $today)
                ->count(),
            'open_reports' => StudentDisciplineReport::query()
                ->where('teacher_id', (int) $teacher->id)
                ->where('status', StudentDisciplineReport::STATUS_OPEN)
                ->count(),
            'repeated_reports' => StudentDisciplineReport::query()
                ->where('teacher_id', (int) $teacher->id)
                ->where('severity', StudentDisciplineReport::SEVERITY_REPEATED)
                ->count(),
            'resolved_reports' => StudentDisciplineReport::query()
                ->where('teacher_id', (int) $teacher->id)
                ->where('status', StudentDisciplineReport::STATUS_RESOLVED)
                ->count(),
            'most_common_issue_today' => $mostCommonIssueToday
                ? StudentDisciplineReport::issueLabelFor((string) $mostCommonIssueToday)
                : 'N/A',
        ];

        return [
            'reports' => $reports,
            'filters' => $normalized,
            'cards' => $cards,
            'sessions' => $this->sessionOptions(),
            'classes' => $assignmentContext['classes'],
            'subjects_by_class' => $assignmentContext['subjects_by_class'],
            'issue_options' => StudentDisciplineReport::ISSUE_LABELS,
            'severity_options' => StudentDisciplineReport::SEVERITIES,
            'status_options' => StudentDisciplineReport::STATUSES,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     reports:LengthAwarePaginator|Collection<int, StudentDisciplineReport>,
     *     filters:array<string, mixed>,
     *     cards:array<string, int>,
     *     sessions:array<int, string>,
     *     classes:array<int, array{id:int,name:string}>,
     *     students:array<int, array{id:int,name:string}>,
     *     teachers:array<int, array{id:int,name:string}>,
     *     issue_options:array<string, string>,
     *     severity_options:array<int, string>,
     *     status_options:array<int, string>,
     *     issue_summary:array<int, array{issue_type:string,issue_label:string,total:int}>,
     *     class_summary:array<int, array{class_name:string,total:int}>,
     *     repeated_students:array<int, array{student_name:string,admission_no:string,total:int,class_name:string}>,
     *     status_summary:array<int, array{status:string,total:int}>
     * }
     */
    public function getPrincipalReports(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $query = $this->baseReportQuery();
        $this->applyReportFilters($query, $normalized);

        $paginate = array_key_exists('paginate', $filters) ? (bool) $filters['paginate'] : true;
        $reports = $paginate
            ? $query
                ->orderByDesc('report_date')
                ->orderByDesc('id')
                ->paginate((int) $normalized['per_page'])
                ->withQueryString()
            : $query
                ->orderBy('report_date')
                ->orderBy('class_id')
                ->orderBy('student_id')
                ->get();

        $summaryQuery = StudentDisciplineReport::query();
        $this->applyReportFilters($summaryQuery, $normalized);

        $cards = [
            'total' => (int) (clone $summaryQuery)->count(),
            'open' => (int) (clone $summaryQuery)->where('status', StudentDisciplineReport::STATUS_OPEN)->count(),
            'acknowledged' => (int) (clone $summaryQuery)->where('status', StudentDisciplineReport::STATUS_ACKNOWLEDGED)->count(),
            'resolved' => (int) (clone $summaryQuery)->where('status', StudentDisciplineReport::STATUS_RESOLVED)->count(),
            'repeated' => (int) (clone $summaryQuery)->where('severity', StudentDisciplineReport::SEVERITY_REPEATED)->count(),
            'serious' => (int) (clone $summaryQuery)->where('severity', StudentDisciplineReport::SEVERITY_SERIOUS)->count(),
            'urgent' => (int) (clone $summaryQuery)->where('severity', StudentDisciplineReport::SEVERITY_URGENT)->count(),
        ];

        $issueSummary = (clone $summaryQuery)
            ->select('issue_type', DB::raw('COUNT(*) as total'))
            ->groupBy('issue_type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'issue_type' => (string) $row->issue_type,
                'issue_label' => StudentDisciplineReport::issueLabelFor((string) $row->issue_type),
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $classSummary = (clone $summaryQuery)
            ->leftJoin('school_classes', 'school_classes.id', '=', 'student_discipline_reports.class_id')
            ->selectRaw("COALESCE(CONCAT(school_classes.name, ' ', COALESCE(school_classes.section, '')), 'Unknown Class') as class_name")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('class_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'class_name' => trim((string) $row->class_name),
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $repeatedStudents = (clone $summaryQuery)
            ->join('students', 'students.id', '=', 'student_discipline_reports.student_id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'student_discipline_reports.class_id')
            ->select('students.name as student_name', 'students.student_id as admission_no')
            ->selectRaw("COALESCE(CONCAT(school_classes.name, ' ', COALESCE(school_classes.section, '')), 'Unknown Class') as class_name")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('student_discipline_reports.student_id', 'students.name', 'students.student_id', 'class_name')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('total')
            ->limit(20)
            ->get()
            ->map(fn ($row): array => [
                'student_name' => (string) $row->student_name,
                'admission_no' => (string) $row->admission_no,
                'class_name' => trim((string) $row->class_name),
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $statusSummary = (clone $summaryQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row): array => [
                'status' => (string) $row->status,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        return [
            'reports' => $reports,
            'filters' => $normalized,
            'cards' => $cards,
            'sessions' => $this->sessionOptions(),
            'classes' => $this->classOptions(),
            'students' => $this->studentOptions(),
            'teachers' => $this->teacherOptions(),
            'issue_options' => StudentDisciplineReport::ISSUE_LABELS,
            'severity_options' => StudentDisciplineReport::SEVERITIES,
            'status_options' => StudentDisciplineReport::STATUSES,
            'issue_summary' => $issueSummary,
            'class_summary' => $classSummary,
            'repeated_students' => $repeatedStudents,
            'status_summary' => $statusSummary,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     reports:LengthAwarePaginator,
     *     filters:array<string, mixed>,
     *     cards:array<string, int>,
     *     sessions:array<int, string>,
     *     classes:array<int, array{id:int,name:string}>,
     *     students:array<int, array{id:int,name:string}>,
     *     teachers:array<int, array{id:int,name:string}>,
     *     issue_options:array<string, string>,
     *     severity_options:array<int, string>,
     *     status_options:array<int, string>
     * }
     */
    public function getWardenReports(User $warden, array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $allowedClassIds = Student::query()
            ->forWarden($warden)
            ->distinct()
            ->pluck('class_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();
        $hasWardenClassScope = $allowedClassIds !== [];

        $query = $this->baseReportQuery()
            ->when(
                $hasWardenClassScope,
                fn (Builder $reportQuery): Builder => $reportQuery
                    ->whereHas('student', fn (Builder $studentQuery) => $studentQuery->forWarden($warden))
            );
        $this->applyReportFilters($query, $normalized);

        $reports = $query
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->paginate((int) $normalized['per_page'])
            ->withQueryString();

        $summaryQuery = StudentDisciplineReport::query()
            ->when(
                $hasWardenClassScope,
                fn (Builder $reportQuery): Builder => $reportQuery
                    ->whereHas('student', fn (Builder $studentQuery) => $studentQuery->forWarden($warden))
            );
        $this->applyReportFilters($summaryQuery, $normalized);

        $cards = [
            'total' => (int) (clone $summaryQuery)->count(),
            'open' => (int) (clone $summaryQuery)->where('status', StudentDisciplineReport::STATUS_OPEN)->count(),
            'acknowledged' => (int) (clone $summaryQuery)->where('status', StudentDisciplineReport::STATUS_ACKNOWLEDGED)->count(),
            'resolved' => (int) (clone $summaryQuery)->where('status', StudentDisciplineReport::STATUS_RESOLVED)->count(),
        ];

        return [
            'reports' => $reports,
            'filters' => $normalized,
            'cards' => $cards,
            'sessions' => $this->sessionOptions(),
            'classes' => $this->classOptions($allowedClassIds),
            'students' => $this->studentOptions($allowedClassIds),
            'teachers' => $this->teacherOptions(),
            'issue_options' => StudentDisciplineReport::ISSUE_LABELS,
            'severity_options' => StudentDisciplineReport::SEVERITIES,
            'status_options' => StudentDisciplineReport::STATUSES,
        ];
    }

    public function markAcknowledged(StudentDisciplineReport $report, User $user): StudentDisciplineReport
    {
        if ((string) $report->status === StudentDisciplineReport::STATUS_RESOLVED) {
            return $report;
        }

        $payload = [
            'status' => StudentDisciplineReport::STATUS_ACKNOWLEDGED,
            'updated_by' => (int) $user->id,
        ];

        if ($report->acknowledged_at === null) {
            $payload['acknowledged_at'] = now();
        }
        if ($report->acknowledged_by === null) {
            $payload['acknowledged_by'] = (int) $user->id;
        }

        $report->forceFill($payload)->save();

        return $report->fresh([
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'subject:id,name',
            'teacher:id,name',
            'acknowledgedBy:id,name',
            'resolvedBy:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function markResolved(StudentDisciplineReport $report, array $data, User $user): StudentDisciplineReport
    {
        $principalRemarks = trim((string) ($data['principal_remarks'] ?? '')) ?: null;
        $wardenRemarks = trim((string) ($data['warden_remarks'] ?? '')) ?: null;

        $payload = [
            'status' => StudentDisciplineReport::STATUS_RESOLVED,
            'resolved_by' => (int) $user->id,
            'resolved_at' => now(),
            'updated_by' => (int) $user->id,
        ];

        if ($principalRemarks !== null) {
            $payload['principal_remarks'] = $principalRemarks;
        }
        if ($wardenRemarks !== null) {
            $payload['warden_remarks'] = $wardenRemarks;
        }
        if ($report->acknowledged_at === null) {
            $payload['acknowledged_at'] = now();
        }
        if ($report->acknowledged_by === null) {
            $payload['acknowledged_by'] = (int) $user->id;
        }

        $report->forceFill($payload)->save();

        return $report->fresh([
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'subject:id,name',
            'teacher:id,name',
            'acknowledgedBy:id,name',
            'resolvedBy:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 20;
        $perPage = max(10, min($perPage, 200));

        return [
            'session' => trim((string) ($filters['session'] ?? '')) ?: $this->resolveSession(null),
            'date' => trim((string) ($filters['date'] ?? '')) ?: null,
            'date_from' => trim((string) ($filters['date_from'] ?? '')) ?: null,
            'date_to' => trim((string) ($filters['date_to'] ?? '')) ?: null,
            'class_id' => isset($filters['class_id']) && $filters['class_id'] !== '' ? (int) $filters['class_id'] : null,
            'student_id' => isset($filters['student_id']) && $filters['student_id'] !== '' ? (int) $filters['student_id'] : null,
            'teacher_id' => isset($filters['teacher_id']) && $filters['teacher_id'] !== '' ? (int) $filters['teacher_id'] : null,
            'subject_id' => isset($filters['subject_id']) && $filters['subject_id'] !== '' ? (int) $filters['subject_id'] : null,
            'issue_type' => trim((string) ($filters['issue_type'] ?? '')) ?: null,
            'severity' => trim((string) ($filters['severity'] ?? '')) ?: null,
            'status' => trim((string) ($filters['status'] ?? '')) ?: null,
            'per_page' => $perPage,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyReportFilters(Builder $query, array $filters): void
    {
        if (isset($filters['session']) && $filters['session'] !== null) {
            $query->where('student_discipline_reports.session', (string) $filters['session']);
        }

        if (isset($filters['date']) && $filters['date'] !== null) {
            $query->whereDate('student_discipline_reports.report_date', Carbon::parse((string) $filters['date'])->toDateString());
        }

        if (isset($filters['date_from']) && $filters['date_from'] !== null) {
            $query->whereDate('student_discipline_reports.report_date', '>=', Carbon::parse((string) $filters['date_from'])->toDateString());
        }

        if (isset($filters['date_to']) && $filters['date_to'] !== null) {
            $query->whereDate('student_discipline_reports.report_date', '<=', Carbon::parse((string) $filters['date_to'])->toDateString());
        }

        if (isset($filters['class_id']) && $filters['class_id'] !== null) {
            $query->where('student_discipline_reports.class_id', (int) $filters['class_id']);
        }

        if (isset($filters['student_id']) && $filters['student_id'] !== null) {
            $query->where('student_discipline_reports.student_id', (int) $filters['student_id']);
        }

        if (isset($filters['teacher_id']) && $filters['teacher_id'] !== null) {
            $query->where('student_discipline_reports.teacher_id', (int) $filters['teacher_id']);
        }

        if (isset($filters['subject_id']) && $filters['subject_id'] !== null) {
            $query->where('student_discipline_reports.subject_id', (int) $filters['subject_id']);
        }

        if (isset($filters['issue_type']) && $filters['issue_type'] !== null) {
            $query->where('student_discipline_reports.issue_type', (string) $filters['issue_type']);
        }

        if (isset($filters['severity']) && $filters['severity'] !== null) {
            $query->where('student_discipline_reports.severity', (string) $filters['severity']);
        }

        if (isset($filters['status']) && $filters['status'] !== null) {
            $query->where('student_discipline_reports.status', (string) $filters['status']);
        }
    }

    private function baseReportQuery(): Builder
    {
        return StudentDisciplineReport::query()
            ->with([
                'student:id,student_id,name,father_name,class_id,status',
                'student.classRoom:id,name,section',
                'classRoom:id,name,section',
                'subject:id,name',
                'teacher:id,name',
                'acknowledgedBy:id,name',
                'resolvedBy:id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ]);
    }

    /**
     * @return array{class_id:int,class_section:string}|null
     */
    private function resolveClassMetaForStudentSession(Student $student, string $session): ?array
    {
        $history = StudentClassHistory::query()
            ->with('classRoom:id,name,section')
            ->where('student_id', (int) $student->id)
            ->where('session', $session)
            ->orderByDesc('joined_on')
            ->orderByDesc('id')
            ->first();

        if ($history?->classRoom) {
            return [
                'class_id' => (int) $history->classRoom->id,
                'class_section' => trim((string) ($history->classRoom->name ?? '').' '.(string) ($history->classRoom->section ?? '')),
            ];
        }

        if ($student->classRoom) {
            return [
                'class_id' => (int) $student->classRoom->id,
                'class_section' => trim((string) ($student->classRoom->name ?? '').' '.(string) ($student->classRoom->section ?? '')),
            ];
        }

        return null;
    }

    /**
     * @param array<int, int> $studentIds
     * @return array<int, array{class_id:int,class_section:string}>
     */
    private function sessionClassMap(array $studentIds, string $session): array
    {
        if ($studentIds === []) {
            return [];
        }

        $histories = StudentClassHistory::query()
            ->with('classRoom:id,name,section')
            ->whereIn('student_id', $studentIds)
            ->where('session', $session)
            ->orderByDesc('joined_on')
            ->orderByDesc('id')
            ->get(['student_id', 'class_id', 'joined_on', 'id']);

        $map = [];
        foreach ($histories as $history) {
            $studentId = (int) $history->student_id;
            if (isset($map[$studentId])) {
                continue;
            }

            $map[$studentId] = [
                'class_id' => (int) $history->class_id,
                'class_section' => trim((string) ($history->classRoom?->name ?? '').' '.(string) ($history->classRoom?->section ?? '')),
            ];
        }

        return $map;
    }

    private function canTeacherReportStudentWithContext(
        array $assignmentContext,
        Student $student,
        string $session,
        ?int $subjectId = null
    ): bool {
        $resolvedClassMeta = $this->resolveClassMetaForStudentSession($student, $session);
        $resolvedClassId = is_array($resolvedClassMeta)
            ? (int) ($resolvedClassMeta['class_id'] ?? 0)
            : (int) $student->class_id;
        if ($resolvedClassId <= 0) {
            return false;
        }

        $allowedSubjectIds = $assignmentContext['subject_ids_by_class'][$resolvedClassId] ?? [];
        if ($allowedSubjectIds === []) {
            return false;
        }

        if ($subjectId === null) {
            return true;
        }

        if (! in_array($subjectId, $allowedSubjectIds, true)) {
            return false;
        }

        if (! $this->visibilityService->classRequiresSubjectFiltering($resolvedClassId)) {
            return true;
        }

        return $this->studentHasSubjectForSession((int) $student->id, $resolvedClassId, $subjectId, $session);
    }

    private function studentHasSubjectForSession(int $studentId, int $classId, int $subjectId, string $session): bool
    {
        $fromMatrix = StudentSubjectAssignment::query()
            ->where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->exists();

        if ($fromMatrix) {
            return true;
        }

        return StudentSubject::query()
            ->where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->exists();
    }

    /**
     * @return array{
     *   teacher_profile_id:?int,
     *   subject_ids_by_class:array<int, array<int, int>>,
     *   subjects_by_class:array<int, array<int, array{id:int,name:string}>>,
     *   classes:array<int, array{id:int,name:string}>
     * }
     */
    private function teacherAssignmentContext(User $teacher, string $session): array
    {
        $teacherProfile = Teacher::query()
            ->where('user_id', (int) $teacher->id)
            ->first();

        if (! $teacherProfile instanceof Teacher) {
            return [
                'teacher_profile_id' => null,
                'subject_ids_by_class' => [],
                'subjects_by_class' => [],
                'classes' => [],
            ];
        }

        $assignments = TeacherAssignment::query()
            ->with(['classRoom:id,name,section', 'subject:id,name'])
            ->where('teacher_id', (int) $teacherProfile->id)
            ->where('session', $session)
            ->whereNotNull('subject_id')
            ->orderBy('class_id')
            ->orderBy('subject_id')
            ->get(['class_id', 'subject_id', 'teacher_id', 'session']);

        $subjectIdsByClass = [];
        $subjectsByClass = [];
        $classes = [];
        foreach ($assignments as $assignment) {
            $classId = (int) $assignment->class_id;
            $subjectId = (int) ($assignment->subject_id ?? 0);
            if ($classId <= 0 || $subjectId <= 0) {
                continue;
            }

            if (! isset($subjectIdsByClass[$classId])) {
                $subjectIdsByClass[$classId] = [];
            }
            $subjectIdsByClass[$classId][] = $subjectId;

            if (! isset($subjectsByClass[$classId])) {
                $subjectsByClass[$classId] = [];
            }
            $subjectsByClass[$classId][] = [
                'id' => $subjectId,
                'name' => (string) ($assignment->subject?->name ?? 'Subject'),
            ];

            if (! isset($classes[$classId])) {
                $classes[$classId] = [
                    'id' => $classId,
                    'name' => trim((string) ($assignment->classRoom?->name ?? '').' '.(string) ($assignment->classRoom?->section ?? '')),
                ];
            }
        }

        foreach ($subjectIdsByClass as $classId => $subjectIds) {
            $subjectIdsByClass[$classId] = array_values(array_unique(array_map(static fn ($id): int => (int) $id, $subjectIds)));
        }
        foreach ($subjectsByClass as $classId => $rows) {
            $subjectsByClass[$classId] = collect($rows)
                ->unique('id')
                ->sortBy('name')
                ->values()
                ->all();
        }

        return [
            'teacher_profile_id' => (int) $teacherProfile->id,
            'subject_ids_by_class' => $subjectIdsByClass,
            'subjects_by_class' => $subjectsByClass,
            'classes' => collect(array_values($classes))
                ->sortBy('name')
                ->values()
                ->all(),
        ];
    }

    /**
     * @param array<int, string> $roles
     * @return Collection<int, User>
     */
    private function activeUsersByRoles(array $roles): Collection
    {
        return User::query()
            ->role($roles)
            ->where(function (Builder $query): void {
                $query->whereNull('status')
                    ->orWhereIn('status', ['active', 'Active', 'ACTIVE', 'enabled', 'Enabled', 'ENABLED', '1', 1]);
            })
            ->get(['id', 'name', 'email'])
            ->unique('id')
            ->values();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function classOptions(array $classIds = []): array
    {
        return SchoolClass::query()
            ->when($classIds !== [], fn (Builder $query) => $query->whereIn('id', $classIds))
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section'])
            ->map(fn (SchoolClass $class): array => [
                'id' => (int) $class->id,
                'name' => trim((string) $class->name.' '.(string) ($class->section ?? '')),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function studentOptions(array $classIds = []): array
    {
        return Student::query()
            ->where('status', 'active')
            ->when($classIds !== [], fn (Builder $query) => $query->whereIn('class_id', $classIds))
            ->orderBy('name')
            ->limit(400)
            ->get(['id', 'name', 'student_id'])
            ->map(fn (Student $student): array => [
                'id' => (int) $student->id,
                'name' => trim((string) $student->name.' ('.(string) $student->student_id.')'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function teacherOptions(): array
    {
        return User::query()
            ->role('Teacher')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function sessionOptions(): array
    {
        return collect(array_merge(
            StudentDisciplineReport::query()
                ->pluck('session')
                ->filter(fn ($session): bool => is_string($session) && trim($session) !== '')
                ->values()
                ->all(),
            TeacherAssignment::query()
                ->pluck('session')
                ->filter(fn ($session): bool => is_string($session) && trim($session) !== '')
                ->values()
                ->all(),
            $this->dailyDiaryService->sessionOptions()
        ))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    private function resolveSession(?string $session): string
    {
        return $this->dailyDiaryService->resolveSession($session);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, int>
     */
    private function extractRequestedStudentIds(array $data): array
    {
        return collect((array) ($data['student_ids'] ?? []))
            ->push($data['student_id'] ?? null)
            ->filter(fn ($studentId): bool => $studentId !== null && $studentId !== '')
            ->map(fn ($studentId): int => (int) $studentId)
            ->filter(fn (int $studentId): bool => $studentId > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function issueTemplates(): array
    {
        return [
            StudentDisciplineReport::ISSUE_LATE_TO_CLASS => '{student_name} of {class_section} was reported by {teacher_name} for being late to class.',
            StudentDisciplineReport::ISSUE_HOMEWORK_NOT_COMPLETED => '{student_name} of {class_section} was reported by {teacher_name} for not completing homework in {subject_name}.',
            StudentDisciplineReport::ISSUE_CLASS_DISTURBANCE => '{student_name} of {class_section} was reported by {teacher_name} for disturbing the class.',
            StudentDisciplineReport::ISSUE_DISRESPECTFUL_BEHAVIOR => '{student_name} of {class_section} was reported by {teacher_name} for disrespectful behavior during class.',
            StudentDisciplineReport::ISSUE_FIGHTING_AGGRESSION => '{student_name} of {class_section} was reported by {teacher_name} for fighting or aggressive behavior.',
            StudentDisciplineReport::ISSUE_BULLYING => '{student_name} of {class_section} was reported by {teacher_name} for bullying behavior.',
            StudentDisciplineReport::ISSUE_ABUSIVE_LANGUAGE => '{student_name} of {class_section} was reported by {teacher_name} for using abusive language.',
            StudentDisciplineReport::ISSUE_UNIFORM_ISSUE => '{student_name} of {class_section} was reported by {teacher_name} for uniform-related discipline concern.',
            StudentDisciplineReport::ISSUE_MOBILE_PHONE_MISUSE => '{student_name} of {class_section} was reported by {teacher_name} for mobile phone misuse during class.',
            StudentDisciplineReport::ISSUE_CHEATING_DISHONESTY => '{student_name} of {class_section} was reported by {teacher_name} for cheating or dishonest conduct.',
            StudentDisciplineReport::ISSUE_LEAVING_CLASS_WITHOUT_PERMISSION => '{student_name} of {class_section} was reported by {teacher_name} for leaving class without permission.',
            StudentDisciplineReport::ISSUE_REPEATED_NEGLIGENCE => '{student_name} of {class_section} was reported by {teacher_name} for repeated negligence in class discipline.',
            StudentDisciplineReport::ISSUE_OTHER => '{student_name} of {class_section} was reported by {teacher_name} for a discipline concern.',
        ];
    }
}
