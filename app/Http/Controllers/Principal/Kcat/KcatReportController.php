<?php

namespace App\Http\Controllers\Principal\Kcat;

use App\Http\Controllers\Controller;
use App\Models\KcatAttempt;
use App\Models\Student;
use App\Services\Kcat\KcatReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KcatReportController extends Controller
{
    public function __construct(private readonly KcatReportService $reportService) {}

    public function index(Request $request): View
    {
        return view('principal.kcat.reports.index', ['attempts' => $this->reportService->getPrincipalReportData($request->only(['session', 'student']))]);
    }

    public function show(KcatAttempt $attempt): View
    {
        return view('principal.kcat.reports.show', ['report' => $this->reportService->generateStudentReport($attempt)]);
    }

    public function print(KcatAttempt $attempt): View
    {
        return view('principal.kcat.reports.print', ['report' => $this->reportService->generateStudentReport($attempt)]);
    }

    public function studentReport(Student $student): View
    {
        return view('principal.kcat.reports.index', ['attempts' => KcatAttempt::query()->where('student_id', $student->id)->with(['student.classRoom', 'test'])->latest('submitted_at')->paginate(20)]);
    }
}
