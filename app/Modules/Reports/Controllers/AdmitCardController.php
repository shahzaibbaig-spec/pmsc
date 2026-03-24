<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdmitCardOverride;
use App\Models\ExamSession;
use App\Models\FeeDefaulter;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Modules\Fees\Services\FeeDefaulterService;
use App\Modules\Reports\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;

class AdmitCardController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly FeeDefaulterService $feeDefaulterService,
    ) {
    }

    public function index(Request $request): View
    {
        if (! $this->admitCardTablesReady()) {
            return redirect()
                ->route('dashboard')
                ->with('error', $this->missingTablesMessage());
        }

        $sessions = ExamSession::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'name', 'session', 'start_date', 'end_date']);

        $selectedExamSessionId = $request->filled('exam_session_id')
            ? (int) $request->input('exam_session_id')
            : (int) ($sessions->first()->id ?? 0);

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        return view('modules.principal.admit-cards.index', [
            'examSessions' => $sessions,
            'selectedExamSessionId' => $selectedExamSessionId > 0 ? $selectedExamSessionId : '',
            'classes' => $classes,
        ]);
    }

    public function storeExamSession(Request $request): RedirectResponse
    {
        if (! $this->admitCardTablesReady()) {
            return back()->with('error', $this->missingTablesMessage());
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'session' => ['required', 'string', 'max:20'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $examSession = ExamSession::query()->create([
            'name' => trim((string) $validated['name']),
            'session' => trim((string) $validated['session']),
            'start_date' => (string) $validated['start_date'],
            'end_date' => (string) $validated['end_date'],
        ]);

        return redirect()
            ->route('principal.admit-cards.index', ['exam_session_id' => $examSession->id])
            ->with('status', 'Exam session created successfully.');
    }

    public function singlePdf(Request $request): Response
    {
        if (! $this->admitCardTablesReady()) {
            return response($this->missingTablesMessage(), 422);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
        ]);

        $examSession = ExamSession::query()->findOrFail((int) $validated['exam_session_id']);
        $student = Student::query()
            ->with('classRoom:id,name,section')
            ->findOrFail((int) $validated['student_id'], [
                'id',
                'student_id',
                'name',
                'father_name',
                'class_id',
                'photo_path',
                'status',
            ]);

        try {
            $this->feeDefaulterService->processSession((string) $examSession->session);
            $this->ensureAdmitCardAllowed($student, $examSession);
        } catch (RuntimeException $exception) {
            return response($exception->getMessage(), 422);
        }

        $payload = $this->admitCardPayload($student, $examSession);
        $pdf = Pdf::loadView('modules.reports.admit-card', [
            'card' => $payload,
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'admit_card_%s_%s.pdf',
            $payload['student']['student_code'],
            str_replace(' ', '_', strtolower((string) $examSession->name))
        );

        return $pdf->stream($filename);
    }

    public function classPdf(Request $request): Response
    {
        if (! $this->admitCardTablesReady()) {
            return response($this->missingTablesMessage(), 422);
        }

        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
        ]);

        $class = SchoolClass::query()->findOrFail((int) $validated['class_id'], ['id', 'name', 'section']);
        $examSession = ExamSession::query()->findOrFail((int) $validated['exam_session_id']);

        $this->feeDefaulterService->processSession((string) $examSession->session);

        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->where('class_id', (int) $class->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'student_id', 'name', 'father_name', 'class_id', 'photo_path', 'status']);

        if ($students->isEmpty()) {
            return response('No active students found for selected class.', 422);
        }

        $cards = [];
        $blockedStudents = [];
        foreach ($students as $student) {
            if ($this->isBlockedWithoutExamOverride($student, $examSession)) {
                $blockedStudents[] = [
                    'name' => (string) $student->name,
                    'student_id' => (string) ($student->student_id ?: $student->id),
                ];
                continue;
            }

            $cards[] = $this->admitCardPayload($student, $examSession);
        }

        if ($cards === []) {
            $sample = collect($blockedStudents)
                ->take(3)
                ->map(fn (array $row): string => $row['name'].' ('.$row['student_id'].')')
                ->implode(', ');

            return response(sprintf(
                'Admit cards are blocked for all students in this class due to fee defaulter rules. %s',
                $sample !== '' ? 'Examples: '.$sample.'.' : ''
            ), 422);
        }

        $pdf = Pdf::loadView('modules.reports.admit-cards-class', [
            'cards' => $cards,
            'meta' => [
                'class_name' => trim((string) $class->name.' '.(string) ($class->section ?? '')),
                'exam_session_name' => (string) $examSession->name,
                'exam_session_range' => optional($examSession->start_date)->format('d M Y')
                    .' - '
                    .optional($examSession->end_date)->format('d M Y'),
                'total_students' => $students->count(),
                'cards_generated' => count($cards),
                'blocked_count' => count($blockedStudents),
            ],
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'admit_cards_class_%d_%d.pdf',
            (int) $class->id,
            (int) $examSession->id
        );

        return $pdf->stream($filename);
    }

    public function overrides(Request $request): View
    {
        if (! $this->admitCardTablesReady()) {
            return redirect()
                ->route('dashboard')
                ->with('error', $this->missingTablesMessage());
        }

        $examSessions = ExamSession::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'name', 'session', 'start_date', 'end_date']);

        $selectedExamSessionId = $request->filled('exam_session_id')
            ? (int) $request->input('exam_session_id')
            : (int) ($examSessions->first()->id ?? 0);

        $selectedExamSession = $selectedExamSessionId > 0
            ? $examSessions->firstWhere('id', $selectedExamSessionId)
            : null;

        $classes = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $search = trim((string) $request->query('search', ''));
        $classId = $request->filled('class_id') ? (int) $request->query('class_id') : null;

        if ($selectedExamSession) {
            $this->feeDefaulterService->processSession((string) $selectedExamSession->session);
        }

        $rows = FeeDefaulter::query()
            ->with([
                'student:id,name,student_id,class_id,status',
                'student.classRoom:id,name,section',
            ])
            ->when($selectedExamSession !== null, function ($query) use ($selectedExamSession): void {
                $query->where('session', (string) $selectedExamSession->session);
            }, function ($query): void {
                $query->whereRaw('1 = 0');
            })
            ->where('is_active', true)
            ->when($classId !== null, function ($query) use ($classId): void {
                $query->whereHas('student', function ($studentQuery) use ($classId): void {
                    $studentQuery->where('class_id', $classId);
                });
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('student', function ($studentQuery) use ($search): void {
                    $studentQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('student_id', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('total_due')
            ->orderBy('oldest_due_date')
            ->paginate(20)
            ->withQueryString();

        $studentIds = $rows->getCollection()
            ->pluck('student_id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        $overrideMap = collect();
        if ($selectedExamSession && $studentIds->isNotEmpty()) {
            $overrideMap = AdmitCardOverride::query()
                ->with('approver:id,name')
                ->where('exam_session_id', (int) $selectedExamSession->id)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->keyBy(fn (AdmitCardOverride $override): int => (int) $override->student_id);
        }

        return view('modules.principal.admit-cards.overrides', [
            'examSessions' => $examSessions,
            'selectedExamSession' => $selectedExamSession,
            'rows' => $rows,
            'overrideMap' => $overrideMap,
            'classes' => $classes,
            'filters' => [
                'exam_session_id' => $selectedExamSession?->id ?: '',
                'class_id' => $classId ?? '',
                'search' => $search,
            ],
        ]);
    }

    public function storeOverride(Request $request): RedirectResponse
    {
        if (! $this->admitCardTablesReady()) {
            return back()->with('error', $this->missingTablesMessage());
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'is_allowed' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        AdmitCardOverride::query()->updateOrCreate(
            [
                'student_id' => (int) $validated['student_id'],
                'exam_session_id' => (int) $validated['exam_session_id'],
            ],
            [
                'is_allowed' => $request->boolean('is_allowed', true),
                'reason' => trim((string) ($validated['reason'] ?? '')) ?: null,
                'approved_by' => (int) ($request->user()?->id ?? 0) ?: null,
            ]
        );

        return redirect()
            ->route('principal.admit-cards.overrides.index', [
                'exam_session_id' => (int) $validated['exam_session_id'],
                'search' => $request->query('search', ''),
                'class_id' => $request->query('class_id', ''),
            ])
            ->with('status', 'Admit card override saved successfully.');
    }

    private function ensureAdmitCardAllowed(Student $student, ExamSession $examSession): void
    {
        if (! $this->isBlockedWithoutExamOverride($student, $examSession)) {
            return;
        }

        $breakdown = $this->feeDefaulterService->dueBreakdownForStudent((int) $student->id, (string) $examSession->session);
        $totalDue = round((float) ($breakdown['total_due'] ?? 0), 2);

        throw new RuntimeException(sprintf(
            'Admit card is blocked for this student due to unpaid dues (PKR %s). Add an override to allow generation.',
            number_format($totalDue, 2)
        ));
    }

    private function isBlockedWithoutExamOverride(Student $student, ExamSession $examSession): bool
    {
        $blockedByFee = FeeDefaulter::query()
            ->where('student_id', (int) $student->id)
            ->where('session', (string) $examSession->session)
            ->where('is_active', true)
            ->where('total_due', '>', 0)
            ->exists();

        if (! $blockedByFee) {
            return false;
        }

        return ! AdmitCardOverride::query()
            ->where('student_id', (int) $student->id)
            ->where('exam_session_id', (int) $examSession->id)
            ->where('is_allowed', true)
            ->exists();
    }

    /**
     * @return array{
     *   school:array{name:string,logo_absolute_path:?string},
     *   exam_session:array{name:string,session:string,start_date:string,end_date:string},
     *   student:array{name:string,student_code:string,father_name:string,class_name:string,photo_absolute_path:?string},
     *   issued_at:string,
     *   signatures:array{principal:string,controller:string}
     * }
     */
    private function admitCardPayload(Student $student, ExamSession $examSession): array
    {
        $school = $this->reportService->schoolMeta();

        $className = trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? ''));
        $studentCode = trim((string) ($student->student_id ?: $student->id));
        $photoAbsolutePath = $this->photoAbsolutePath((string) ($student->photo_path ?? ''));

        $principal = User::role('Principal')
            ->orderBy('id')
            ->value('name');

        $controllerName = TeacherAssignment::query()
            ->with('teacher.user:id,name')
            ->where('class_id', (int) ($student->class_id ?? 0))
            ->where('session', (string) $examSession->session)
            ->where('is_class_teacher', true)
            ->first()?->teacher?->user?->name;

        return [
            'school' => [
                'name' => (string) ($school['name'] ?? 'School Management System'),
                'logo_absolute_path' => $school['logo_absolute_path'] ?? null,
            ],
            'exam_session' => [
                'name' => (string) $examSession->name,
                'session' => (string) $examSession->session,
                'start_date' => optional($examSession->start_date)->format('d M Y') ?: '-',
                'end_date' => optional($examSession->end_date)->format('d M Y') ?: '-',
            ],
            'student' => [
                'name' => (string) $student->name,
                'student_code' => $studentCode,
                'father_name' => trim((string) ($student->father_name ?? '')) ?: '-',
                'class_name' => $className !== '' ? $className : '-',
                'photo_absolute_path' => $photoAbsolutePath,
            ],
            'issued_at' => now()->format('d M Y'),
            'signatures' => [
                'principal' => $principal ?: 'Principal',
                'controller' => $controllerName ?: 'Exam Controller',
            ],
        ];
    }

    private function photoAbsolutePath(string $photoPath): ?string
    {
        $normalized = trim($photoPath);
        if ($normalized === '') {
            return null;
        }

        $absolute = public_path('storage/'.$normalized);

        return is_file($absolute) ? $absolute : null;
    }

    private function admitCardTablesReady(): bool
    {
        return Schema::hasTable('exam_sessions')
            && Schema::hasTable('admit_card_overrides')
            && Schema::hasTable('fee_defaulters');
    }

    private function missingTablesMessage(): string
    {
        return 'Admit card tables are missing on server. Please run latest migrations: php artisan migrate --force';
    }
}
