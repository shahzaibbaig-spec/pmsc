<?php

namespace App\Http\Controllers\Principal;

use App\Exports\ClassWiseStudentListExport;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Services\StudentListService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentListController extends Controller
{
    public function __construct(private readonly StudentListService $studentListService)
    {
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'section' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:active,inactive,all'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:200'],
        ]);

        return view('principal.student-lists.index', $this->studentListService->getClassWiseStudents($validated));
    }

    public function print(Request $request): View|Response|BinaryFileResponse
    {
        $validated = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'section' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:active,inactive,all'],
            'format' => ['nullable', 'in:html,pdf,xlsx,csv'],
        ]);

        $payload = $this->studentListService->getPrintData($validated);
        $payload['filters']['class_name'] = $this->resolveClassName(isset($payload['filters']['class_id']) ? (int) $payload['filters']['class_id'] : null);

        $format = (string) ($validated['format'] ?? 'html');

        if ($format === 'pdf') {
            if (! class_exists(Pdf::class)) {
                return response('PDF package is not installed. Please use the Print List option.', 422);
            }

            return Pdf::loadView('principal.student-lists.print', $payload)
                ->setPaper('a4', 'landscape')
                ->download('class-wise-student-list-'.now()->format('Ymd_His').'.pdf');
        }

        if ($format === 'xlsx' || $format === 'csv') {
            if (! class_exists(Excel::class)) {
                return response('Excel export package is not installed. Please use Print List.', 422);
            }

            $writerType = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
            $extension = $format === 'csv' ? 'csv' : 'xlsx';

            return Excel::download(
                new ClassWiseStudentListExport($payload),
                'class-wise-student-list-'.now()->format('Ymd_His').'.'.$extension,
                $writerType
            );
        }

        return response()
            ->view('principal.student-lists.print', $payload);
    }

    private function resolveClassName(?int $classId): string
    {
        if ($classId === null) {
            return 'All Classes';
        }

        $class = SchoolClass::query()->find($classId);
        if (! $class instanceof SchoolClass) {
            return 'All Classes';
        }

        return trim((string) $class->name.' '.(string) ($class->section ?? ''));
    }
}
