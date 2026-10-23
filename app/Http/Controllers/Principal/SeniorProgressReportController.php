<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\SeniorProgressReportDraft;
use App\Models\Student;
use App\Services\DailyDiaryService;
use App\Services\SeniorProgressReportService;
use App\Services\SeniorProgressWordExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use ZipArchive;

class SeniorProgressReportController extends Controller
{
    public function __construct(
        private readonly SeniorProgressReportService $reports,
        private readonly SeniorProgressWordExporter $exporter,
    ) {}

    public function index(Request $request)
    {
        $this->assertEnabled();
        $filters = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'session' => ['nullable', 'string', 'max:20'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
        ]);
        $classes = $this->reports->classes();
        $session = $filters['session'] ?? app(DailyDiaryService::class)->resolveSession(null);
        $class = isset($filters['class_id']) ? $classes->firstWhere('id', (int) $filters['class_id']) : null;
        abort_if(isset($filters['class_id']) && ! $class, 404);
        $scopes = $class ? $this->reports->scopes($class->id, $session) : collect();
        $examId = isset($filters['exam_id']) ? (int) $filters['exam_id'] : null;
        if ($examId && $class) {
            $examId = $this->reports->scope($class->id, $session, $examId)->id;
        }
        $students = $class ? $this->reports->roster($class->id, $session) : collect();
        $draftStudentIds = $class && $examId ? SeniorProgressReportDraft::query()
            ->where('class_id', $class->id)->where('session', $session)->where('exam_id', $examId)
            ->pluck('student_id')->all() : [];

        return view('principal.senior-progress.index', compact(
            'classes', 'class', 'session', 'scopes', 'examId', 'students', 'draftStudentIds'
        ));
    }

    public function preview(Request $request, int $student)
    {
        [$class, $session, $scope] = $this->context($request);
        $student = $this->student($student, $class, $session);
        $report = $this->report($student, $class, $session, $scope);

        return view('principal.senior-progress.preview', compact('student', 'class', 'session', 'scope', 'report'));
    }

    public function save(Request $request, int $student)
    {
        [$class, $session, $scope] = $this->context($request);
        $student = $this->student($student, $class, $session);
        $report = $this->reports->report($student, $class, $session, $scope);
        $content = $request->validate([
            'progress' => ['nullable', 'array', 'list', 'max:10'],
            'progress.*' => ['nullable', Rule::in(['0', '1', '2'])],
            'effort' => ['nullable', 'array', 'list', 'max:10'],
            'effort.*' => ['nullable', Rule::in(['0', '1', '2'])],
            'skills' => ['nullable', 'array', 'list', 'max:7'],
            'skills.*' => ['nullable', Rule::in(['0', '1', '2', '3'])],
            'teacher_remarks' => ['nullable', 'string', 'max:600'],
            'head_remarks' => ['nullable', 'string', 'max:600'],
        ]);
        // Ratings are keyed by subject name, so a later roster change cannot move them to another subject.
        $content['subjects'] = [];
        foreach ($report['subjects'] as $index => $subject) {
            $content['subjects'][$subject['name']] = [
                'progress' => $content['progress'][$index] ?? '',
                'effort' => $content['effort'][$index] ?? '',
            ];
        }
        unset($content['progress'], $content['effort']);
        SeniorProgressReportDraft::query()->updateOrCreate([
            'student_id' => $student->id, 'class_id' => $class->id,
            'session' => $session, 'exam_id' => $scope->id,
        ], ['content' => $content, 'updated_by' => $request->user()->id]);

        return redirect()->route('principal.senior-progress.preview', [
            'student' => $student->id, 'class_id' => $class->id, 'session' => $session, 'exam_id' => $scope->id,
        ])->with('success', 'Progress report review saved. Individual and class downloads will include it.');
    }

    public function download(Request $request, int $student)
    {
        [$class, $session, $scope] = $this->context($request);
        $student = $this->student($student, $class, $session);
        $report = $this->report($student, $class, $session, $scope);
        $path = $this->temporaryFile();
        try {
            $this->exporter->export($report, $path);
        } catch (\Throwable $error) {
            @unlink($path);
            throw $error;
        }

        return response()->download($path, $this->filename($student), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);
    }

    public function downloadClass(Request $request)
    {
        [$class, $session, $scope] = $this->context($request);
        $students = $this->reports->roster($class->id, $session);
        if ($students->isEmpty()) {
            throw ValidationException::withMessages(['class_id' => 'No students found for this class and session.']);
        }
        $path = $this->temporaryFile();
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            throw new RuntimeException('Unable to create the class archive.');
        }
        $closed = false;
        try {
            foreach ($students as $student) {
                $document = $this->temporaryFile();
                try {
                    $this->exporter->export($this->report($student, $class, $session, $scope), $document);
                    if (! $zip->addFromString($this->filename($student), File::get($document))) {
                        throw new RuntimeException('Unable to add a report to the class archive.');
                    }
                } finally {
                    @unlink($document);
                }
            }
            $closed = true;
            if (! $zip->close()) {
                throw new RuntimeException('Unable to finish the class archive.');
            }
        } catch (\Throwable $error) {
            if (! $closed) {
                @$zip->close();
            }
            @unlink($path);
            throw $error;
        }

        return response()->download($path, 'Mirpur-'.Str::slug($class->name.'-'.$class->section.'-'.$session).'.zip', [
            'Content-Type' => 'application/zip', 'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);
    }

    private function context(Request $request): array
    {
        $this->assertEnabled();
        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'session' => ['required', 'string', 'max:20'],
            'exam_id' => ['required', 'integer', 'exists:exams,id'],
        ]);
        $class = SchoolClass::query()->findOrFail($data['class_id']);
        abort_unless(SeniorProgressReportService::seniorClass($class->name), 404);
        $scope = $this->reports->scope($class->id, $data['session'], (int) $data['exam_id']);

        return [$class, $data['session'], $scope];
    }

    private function student(int $id, SchoolClass $class, string $session): Student
    {
        $student = $this->reports->roster($class->id, $session)->firstWhere('id', $id);
        abort_unless($student, 404);

        return $student;
    }

    private function report(Student $student, SchoolClass $class, string $session, Exam $scope): array
    {
        $report = $this->reports->report($student, $class, $session, $scope);
        $draft = SeniorProgressReportDraft::query()->where('student_id', $student->id)
            ->where('class_id', $class->id)->where('session', $session)->where('exam_id', $scope->id)->first();
        $content = $draft?->content ?? [];
        foreach (['teacher_remarks', 'head_remarks', 'skills'] as $key) {
            if (array_key_exists($key, $content)) {
                $report[$key] = $content[$key] ?? ($key === 'skills' ? [] : '');
            }
        }
        foreach ($report['subjects'] as &$subject) {
            $subject = array_merge($subject, $content['subjects'][$subject['name']] ?? []);
        }

        return $report;
    }

    private function temporaryFile(): string
    {
        $directory = storage_path('app/private/senior-progress');
        File::ensureDirectoryExists($directory, 0700);
        $path = tempnam($directory, 'report-');
        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary report.');
        }
        chmod($path, 0600);

        return $path;
    }

    private function filename(Student $student): string
    {
        return Str::slug($student->student_id.'-'.$student->name).'-'.$student->id.'-progress.docx';
    }

    private function assertEnabled(): void
    {
        abort_unless(SeniorProgressReportService::enabled(), 404);
    }
}
