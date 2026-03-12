<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Timetable\Requests\ImportTimetableWorkbookRequest;
use App\Modules\Timetable\Services\MasterTimetableImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class TimetableImportController extends Controller
{
    public function index(): View
    {
        $sessions = $this->sessionOptions();

        return view('modules.principal.timetable.import', [
            'sessions' => $sessions,
            'defaultSession' => $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1)),
            'summary' => session('timetable_import_summary'),
            'importError' => session('timetable_import_error'),
        ]);
    }

    public function store(
        ImportTimetableWorkbookRequest $request,
        MasterTimetableImportService $importService
    ): RedirectResponse {
        try {
            $summary = $importService->import(
                $request->file('workbook'),
                $request->input('session')
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('principal.timetable.import.index')
                ->withInput($request->except('workbook'))
                ->with('timetable_import_error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            $message = app()->isLocal()
                ? 'Import failed: '.$exception->getMessage()
                : 'Import failed due to an unexpected fatal error. Nothing was saved.';

            return redirect()
                ->route('principal.timetable.import.index')
                ->withInput($request->except('workbook'))
                ->with('timetable_import_error', $message);
        }

        return redirect()
            ->route('principal.timetable.import.index')
            ->with('timetable_import_summary', $summary);
    }

    private function sessionOptions(int $backward = 1, int $forward = 3): array
    {
        $now = now();
        $currentStartYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        $sessions = [];
        for ($year = $currentStartYear - $backward; $year <= $currentStartYear + $forward; $year++) {
            $sessions[] = $year.'-'.($year + 1);
        }

        return $sessions;
    }
}
