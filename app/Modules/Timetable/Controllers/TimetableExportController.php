<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Modules\Reports\Services\ReportService;
use App\Modules\Timetable\Requests\TimetableExportRequest;
use App\Modules\Timetable\Services\TimetableViewerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use RuntimeException;

class TimetableExportController extends Controller
{
    public function __construct(
        private readonly TimetableViewerService $viewerService,
        private readonly ReportService $reportService,
    ) {
    }

    public function pdf(TimetableExportRequest $request): Response
    {
        $type = (string) $request->input('type', '');
        if (! in_array($type, ['class', 'teacher'], true)) {
            return response('Export type must be "class" or "teacher".', 422);
        }

        $session = (string) $request->input('session');

        try {
            if ($type === 'class') {
                $classSectionId = (int) $request->input('class_section_id');
                $payload = $this->viewerService->classTimetable($session, $classSectionId);
                $pdf = Pdf::loadView('modules.reports.timetable-class', [
                    'report' => $payload,
                    'school' => $this->reportService->schoolMeta(),
                ])->setPaper('a4', 'landscape');

                return $pdf->stream('class_timetable_'.$session.'_'.$classSectionId.'.pdf');
            }

            $teacherId = (int) $request->input('teacher_id');
            $payload = $this->viewerService->teacherTimetable($session, $teacherId);
            $pdf = Pdf::loadView('modules.reports.timetable-teacher', [
                'report' => $payload,
                'school' => $this->reportService->schoolMeta(),
            ])->setPaper('a4', 'landscape');

            return $pdf->stream('teacher_timetable_'.$session.'_'.$teacherId.'.pdf');
        } catch (RuntimeException $exception) {
            return response($exception->getMessage(), 422);
        }
    }

    public function csv(TimetableExportRequest $request): Response
    {
        $session = (string) $request->input('session');
        $classSectionId = $request->filled('class_section_id') ? (int) $request->input('class_section_id') : null;
        $teacherId = $request->filled('teacher_id') ? (int) $request->input('teacher_id') : null;

        $rows = $this->viewerService->exportRows($session, $classSectionId, $teacherId);

        $csvLines = [];
        $csvLines[] = [
            'session',
            'class_section',
            'day_of_week',
            'slot_index',
            'subject',
            'teacher',
            'room',
            'room_type',
        ];

        foreach ($rows as $row) {
            $classLabel = $this->classSectionLabel($row->classSection);
            $csvLines[] = [
                $row->session,
                $classLabel,
                $row->day_of_week,
                (string) $row->slot_index,
                $row->subject?->name ?? '',
                $row->teacher?->user?->name ?? '',
                $row->room?->name ?? '',
                $row->room?->type ?? '',
            ];
        }

        $stream = fopen('php://temp', 'r+');
        foreach ($csvLines as $line) {
            fputcsv($stream, $line);
        }
        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        $suffix = 'all';
        if ($classSectionId) {
            $suffix = 'class_'.$classSectionId;
        } elseif ($teacherId) {
            $suffix = 'teacher_'.$teacherId;
        }

        $filename = 'timetable_'.$session.'_'.$suffix.'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function classSectionLabel(?ClassSection $classSection): string
    {
        if (! $classSection) {
            return '';
        }

        $className = trim(($classSection->classRoom?->name ?? 'Class').' '.($classSection->classRoom?->section ?? ''));

        return trim($className.' - '.($classSection->section_name ?? 'Section'));
    }
}
