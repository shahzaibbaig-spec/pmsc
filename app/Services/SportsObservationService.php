<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentSportsObservation;
use App\Models\User;
use App\Notifications\SportsObservationCreatedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SportsObservationService
{
    public function __construct(private readonly DailyDiaryService $dailyDiaryService)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array{id:int,student_name:string,admission_no:string,class_section:string,father_name:string}>
     */
    public function searchStudents(string $term, array $filters = []): array
    {
        $needle = trim($term);
        if ($needle === '' || mb_strlen($needle) < 2) {
            return [];
        }

        $normalized = $this->normalizeFilters($filters);
        $limit = isset($filters['limit']) ? max(5, min((int) $filters['limit'], 50)) : 20;
        $contains = '%'.$needle.'%';
        $prefix = $needle.'%';

        $query = Student::query()
            ->with('classRoom:id,name,section')
            ->where('status', 'active')
            ->where(function (Builder $query) use ($contains, $prefix): void {
                $query->where('name', 'like', $contains)
                    ->orWhere('student_id', 'like', $prefix)
                    ->orWhere('father_name', 'like', $contains)
                    ->orWhereHas('classRoom', function (Builder $classQuery) use ($contains): void {
                        $classQuery->where('name', 'like', $contains)
                            ->orWhere('section', 'like', $contains);
                    });
            });

        if (isset($normalized['class_id']) && $normalized['class_id'] !== null) {
            $query->where('class_id', (int) $normalized['class_id']);
        }

        $session = (string) ($normalized['session'] ?? '');
        if ($session !== '' && StudentClassHistory::query()->where('session', $session)->exists()) {
            $query->whereHas('classHistories', fn (Builder $historyQuery) => $historyQuery->where('session', $session));
        }

        return $query
            ->orderByRaw("CASE WHEN student_id LIKE ? THEN 0 ELSE 1 END", [$prefix])
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'student_id', 'name', 'father_name', 'class_id'])
            ->map(function (Student $student): array {
                return [
                    'id' => (int) $student->id,
                    'student_name' => (string) $student->name,
                    'admission_no' => (string) $student->student_id,
                    'class_section' => trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? '')),
                    'father_name' => (string) ($student->father_name ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createObservation(array $data, User $sportsTeacher): StudentSportsObservation
    {
        $student = Student::query()
            ->with('classRoom:id,name,section')
            ->findOrFail((int) $data['student_id']);

        $issueType = trim((string) ($data['issue_type'] ?? ''));
        if (! array_key_exists($issueType, StudentSportsObservation::ISSUE_LABELS)) {
            throw ValidationException::withMessages([
                'issue_type' => 'Please select a valid issue type.',
            ]);
        }

        $severity = trim((string) ($data['severity'] ?? StudentSportsObservation::SEVERITY_NORMAL));
        if (! in_array($severity, StudentSportsObservation::SEVERITIES, true)) {
            $severity = StudentSportsObservation::SEVERITY_NORMAL;
        }

        $session = $this->resolveSession(isset($data['session']) ? (string) $data['session'] : null);
        $observationDate = isset($data['observation_date']) && trim((string) $data['observation_date']) !== ''
            ? Carbon::parse((string) $data['observation_date'])->toDateString()
            : now()->toDateString();
        $customNote = trim((string) ($data['custom_note'] ?? '')) ?: null;
        $allowDuplicate = $severity === StudentSportsObservation::SEVERITY_REPEATED
            || (bool) ($data['confirm_duplicate'] ?? false);

        $duplicateExists = StudentSportsObservation::query()
            ->where('student_id', (int) $student->id)
            ->where('issue_type', $issueType)
            ->whereDate('observation_date', $observationDate)
            ->where('session', $session)
            ->exists();

        if ($duplicateExists && ! $allowDuplicate) {
            throw ValidationException::withMessages([
                'duplicate_warning' => 'A similar observation already exists for this student on the selected date and session. Select severity "Repeated" or tick confirm duplicate to submit anyway.',
            ]);
        }

        $issueLabel = StudentSportsObservation::issueLabelFor($issueType);
        $autoMessage = $this->generateAutoMessage($student, $issueType, $customNote);

        /** @var StudentSportsObservation $observation */
        $observation = DB::transaction(function () use ($student, $sportsTeacher, $session, $observationDate, $issueType, $issueLabel, $autoMessage, $severity): StudentSportsObservation {
            $observation = StudentSportsObservation::query()->create([
                'student_id' => (int) $student->id,
                'class_id' => $student->class_id ? (int) $student->class_id : null,
                'sports_teacher_id' => (int) $sportsTeacher->id,
                'session' => $session,
                'observation_date' => $observationDate,
                'issue_type' => $issueType,
                'issue_label' => $issueLabel,
                'auto_message' => $autoMessage,
                'severity' => $severity,
                'status' => StudentSportsObservation::STATUS_OPEN,
                'created_by' => (int) $sportsTeacher->id,
                'updated_by' => (int) $sportsTeacher->id,
            ]);

            $this->notifyPrincipalAndWardens($observation);

            return $observation;
        });

        return $observation->fresh([
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'sportsTeacher:id,name',
            'createdBy:id,name',
        ]);
    }

    public function generateAutoMessage(Student $student, string $issueType, ?string $customNote = null): string
    {
        $studentName = trim((string) $student->name);
        $classSection = trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? ''));

        $templates = [
            StudentSportsObservation::ISSUE_NAILS_NOT_CUT => 'Student {student_name} of {class_section} came to sports class with nails not properly cut. Kindly ensure personal hygiene is maintained.',
            StudentSportsObservation::ISSUE_HAIR_NOT_CUT => 'Student {student_name} of {class_section} needs a proper haircut as per school discipline policy.',
            StudentSportsObservation::ISSUE_UNIFORM_NOT_NEAT => 'Student {student_name} of {class_section} was observed with an untidy uniform during sports class.',
            StudentSportsObservation::ISSUE_SHOES_NOT_POLISHED => 'Student {student_name} of {class_section} came with shoes not properly polished.',
            StudentSportsObservation::ISSUE_NOT_CLEAN => 'Student {student_name} of {class_section} was not properly clean and needs attention regarding personal hygiene.',
            StudentSportsObservation::ISSUE_POOR_SPORTS_DISCIPLINE => 'Student {student_name} of {class_section} showed poor discipline during sports class and needs guidance.',
        ];

        $template = $templates[$issueType] ?? 'Student {student_name} of {class_section} needs attention from sports discipline perspective.';

        $message = str_replace(
            ['{student_name}', '{class_section}'],
            [$studentName !== '' ? $studentName : 'Student', $classSection !== '' ? $classSection : 'Unknown Class'],
            $template
        );

        if ($customNote !== null && trim($customNote) !== '') {
            $message .= ' Note: '.trim($customNote);
        }

        return $message;
    }

    public function notifyPrincipalAndWardens(StudentSportsObservation $observation): void
    {
        $observation->loadMissing([
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'sportsTeacher:id,name',
        ]);

        $principalRecipients = $this->activeUsersByRoles(['Principal', 'Admin']);
        $wardenRecipients = $this->activeUsersByRoles(['Warden']);

        $principalRecipients->each(fn (User $user) => $user->notify(new SportsObservationCreatedNotification($observation)));
        $wardenRecipients->each(fn (User $user) => $user->notify(new SportsObservationCreatedNotification($observation)));

        $payload = [];
        if ($principalRecipients->isNotEmpty()) {
            $payload['notified_principal_at'] = now();
        }
        if ($wardenRecipients->isNotEmpty()) {
            $payload['notified_wardens_at'] = now();
        }

        if ($payload !== []) {
            $observation->forceFill($payload)->save();
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     observations:LengthAwarePaginator,
     *     filters:array<string, mixed>,
     *     cards:array<string, mixed>,
     *     sessions:array<int, string>,
     *     issue_options:array<string, string>,
     *     severity_options:array<int, string>,
     *     status_options:array<int, string>
     * }
     */
    public function getObservationsForSportsTeacher(User $teacher, array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);

        $query = $this->baseObservationQuery()
            ->where('sports_teacher_id', (int) $teacher->id);

        $this->applyObservationFilters($query, $normalized);

        $observations = $query
            ->orderByDesc('observation_date')
            ->orderByDesc('id')
            ->paginate((int) $normalized['per_page'])
            ->withQueryString();

        $today = now()->toDateString();
        $cards = [
            'today_observations' => StudentSportsObservation::query()
                ->where('sports_teacher_id', (int) $teacher->id)
                ->whereDate('observation_date', $today)
                ->count(),
            'open_observations' => StudentSportsObservation::query()
                ->where('sports_teacher_id', (int) $teacher->id)
                ->where('status', StudentSportsObservation::STATUS_OPEN)
                ->count(),
            'repeated_issues' => StudentSportsObservation::query()
                ->where('sports_teacher_id', (int) $teacher->id)
                ->where('severity', StudentSportsObservation::SEVERITY_REPEATED)
                ->count(),
            'resolved_observations' => StudentSportsObservation::query()
                ->where('sports_teacher_id', (int) $teacher->id)
                ->where('status', StudentSportsObservation::STATUS_RESOLVED)
                ->count(),
            'most_common_issue_today' => StudentSportsObservation::query()
                ->where('sports_teacher_id', (int) $teacher->id)
                ->whereDate('observation_date', $today)
                ->select('issue_type', 'issue_label', DB::raw('COUNT(*) as total'))
                ->groupBy('issue_type', 'issue_label')
                ->orderByDesc('total')
                ->value('issue_label') ?? 'N/A',
        ];

        return [
            'observations' => $observations,
            'filters' => $normalized,
            'cards' => $cards,
            'sessions' => $this->sessionOptions(),
            'issue_options' => StudentSportsObservation::ISSUE_LABELS,
            'severity_options' => StudentSportsObservation::SEVERITIES,
            'status_options' => StudentSportsObservation::STATUSES,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *     observations:LengthAwarePaginator|Collection<int, StudentSportsObservation>,
     *     filters:array<string, mixed>,
     *     cards:array<string, int>,
     *     sessions:array<int, string>,
     *     classes:array<int, array{id:int,name:string}>,
     *     students:array<int, array{id:int,name:string}>,
     *     sports_teachers:array<int, array{id:int,name:string}>,
     *     issue_options:array<string, string>,
     *     severity_options:array<int, string>,
     *     status_options:array<int, string>,
     *     issue_summary:array<int, array{issue_type:string,issue_label:string,total:int}>,
     *     class_summary:array<int, array{class_name:string,total:int}>,
     *     repeated_students:array<int, array{student_name:string,admission_no:string,total:int,class_name:string}>,
     *     status_summary:array<int, array{status:string,total:int}>
     * }
     */
    public function getDailyObservationsForPrincipal(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);

        $query = $this->baseObservationQuery();
        $this->applyObservationFilters($query, $normalized);

        $paginate = array_key_exists('paginate', $filters) ? (bool) $filters['paginate'] : true;

        $observations = $paginate
            ? $query
                ->orderByDesc('observation_date')
                ->orderByDesc('id')
                ->paginate((int) $normalized['per_page'])
                ->withQueryString()
            : $query
                ->orderBy('observation_date')
                ->orderBy('class_id')
                ->orderBy('student_id')
                ->get();

        $summaryQuery = StudentSportsObservation::query();
        $this->applyObservationFilters($summaryQuery, $normalized);

        $cards = [
            'total' => (int) (clone $summaryQuery)->count(),
            'open' => (int) (clone $summaryQuery)->where('status', StudentSportsObservation::STATUS_OPEN)->count(),
            'acknowledged' => (int) (clone $summaryQuery)->where('status', StudentSportsObservation::STATUS_ACKNOWLEDGED)->count(),
            'resolved' => (int) (clone $summaryQuery)->where('status', StudentSportsObservation::STATUS_RESOLVED)->count(),
            'repeated' => (int) (clone $summaryQuery)->where('severity', StudentSportsObservation::SEVERITY_REPEATED)->count(),
            'serious' => (int) (clone $summaryQuery)->where('severity', StudentSportsObservation::SEVERITY_SERIOUS)->count(),
        ];

        $issueSummary = (clone $summaryQuery)
            ->select('issue_type', 'issue_label', DB::raw('COUNT(*) as total'))
            ->groupBy('issue_type', 'issue_label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'issue_type' => (string) $row->issue_type,
                'issue_label' => (string) $row->issue_label,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $classSummary = (clone $summaryQuery)
            ->leftJoin('school_classes', 'school_classes.id', '=', 'student_sports_observations.class_id')
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
            ->join('students', 'students.id', '=', 'student_sports_observations.student_id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'student_sports_observations.class_id')
            ->select('students.name as student_name', 'students.student_id as admission_no')
            ->selectRaw("COALESCE(CONCAT(school_classes.name, ' ', COALESCE(school_classes.section, '')), 'Unknown Class') as class_name")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('student_sports_observations.student_id', 'students.name', 'students.student_id', 'class_name')
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
            'observations' => $observations,
            'filters' => $normalized,
            'cards' => $cards,
            'sessions' => $this->sessionOptions(),
            'classes' => $this->classOptions(),
            'students' => $this->studentOptions(),
            'sports_teachers' => $this->sportsTeacherOptions(),
            'issue_options' => StudentSportsObservation::ISSUE_LABELS,
            'severity_options' => StudentSportsObservation::SEVERITIES,
            'status_options' => StudentSportsObservation::STATUSES,
            'issue_summary' => $issueSummary,
            'class_summary' => $classSummary,
            'repeated_students' => $repeatedStudents,
            'status_summary' => $statusSummary,
        ];
    }

    public function markAcknowledged(StudentSportsObservation $observation, User $user): StudentSportsObservation
    {
        if ((string) $observation->status === StudentSportsObservation::STATUS_RESOLVED) {
            return $observation;
        }

        $observation->forceFill([
            'status' => StudentSportsObservation::STATUS_ACKNOWLEDGED,
            'updated_by' => (int) $user->id,
        ])->save();

        return $observation->fresh([
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'sportsTeacher:id,name',
            'updatedBy:id,name',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function markResolved(StudentSportsObservation $observation, array $data, User $user): StudentSportsObservation
    {
        $notes = trim((string) ($data['resolution_notes'] ?? ''));

        $observation->forceFill([
            'status' => StudentSportsObservation::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => (int) $user->id,
            'resolution_notes' => $notes !== '' ? $notes : null,
            'updated_by' => (int) $user->id,
        ])->save();

        return $observation->fresh([
            'student.classRoom:id,name,section',
            'classRoom:id,name,section',
            'sportsTeacher:id,name',
            'resolvedBy:id,name',
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
            'issue_type' => trim((string) ($filters['issue_type'] ?? '')) ?: null,
            'sports_teacher_id' => isset($filters['sports_teacher_id']) && $filters['sports_teacher_id'] !== '' ? (int) $filters['sports_teacher_id'] : null,
            'status' => trim((string) ($filters['status'] ?? '')) ?: null,
            'severity' => trim((string) ($filters['severity'] ?? '')) ?: null,
            'per_page' => $perPage,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyObservationFilters(Builder $query, array $filters): void
    {
        if (isset($filters['session']) && $filters['session'] !== null) {
            $query->where('session', (string) $filters['session']);
        }

        if (isset($filters['date']) && $filters['date'] !== null) {
            $query->whereDate('observation_date', Carbon::parse((string) $filters['date'])->toDateString());
        }

        if (isset($filters['date_from']) && $filters['date_from'] !== null) {
            $query->whereDate('observation_date', '>=', Carbon::parse((string) $filters['date_from'])->toDateString());
        }

        if (isset($filters['date_to']) && $filters['date_to'] !== null) {
            $query->whereDate('observation_date', '<=', Carbon::parse((string) $filters['date_to'])->toDateString());
        }

        if (isset($filters['class_id']) && $filters['class_id'] !== null) {
            $query->where('class_id', (int) $filters['class_id']);
        }

        if (isset($filters['student_id']) && $filters['student_id'] !== null) {
            $query->where('student_id', (int) $filters['student_id']);
        }

        if (isset($filters['issue_type']) && $filters['issue_type'] !== null) {
            $query->where('issue_type', (string) $filters['issue_type']);
        }

        if (isset($filters['sports_teacher_id']) && $filters['sports_teacher_id'] !== null) {
            $query->where('sports_teacher_id', (int) $filters['sports_teacher_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== null) {
            $query->where('status', (string) $filters['status']);
        }

        if (isset($filters['severity']) && $filters['severity'] !== null) {
            $query->where('severity', (string) $filters['severity']);
        }
    }

    private function baseObservationQuery(): Builder
    {
        return StudentSportsObservation::query()
            ->with([
                'student:id,student_id,name,father_name,class_id,status',
                'student.classRoom:id,name,section',
                'classRoom:id,name,section',
                'sportsTeacher:id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
                'resolvedBy:id,name',
            ]);
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
    private function classOptions(): array
    {
        return SchoolClass::query()
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
    private function studentOptions(): array
    {
        return Student::query()
            ->where('status', 'active')
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
    private function sportsTeacherOptions(): array
    {
        return User::query()
            ->role('Sports Teacher')
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
            StudentSportsObservation::query()
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
}
