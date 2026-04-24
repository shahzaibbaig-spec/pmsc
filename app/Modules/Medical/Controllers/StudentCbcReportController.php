<?php

namespace App\Modules\Medical\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MedicalReferral;
use App\Models\Student;
use App\Models\StudentCbcReport;
use App\Models\StudentMedicalRecord;
use App\Models\User;
use App\Modules\Medical\Requests\StoreStudentCbcReportRequest;
use App\Modules\Medical\Requests\UpdateStudentCbcReportRequest;
use App\Modules\Medical\Services\StudentCbcReportService;
use App\Modules\Reports\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class StudentCbcReportController extends Controller
{
    public function __construct(
        private readonly StudentCbcReportService $service,
        private readonly ReportService $reportService,
    ) {
    }

    public function storeForMedicalRecord(StoreStudentCbcReportRequest $request, MedicalReferral $medicalReferral): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user instanceof User) {
                return response()->json(['message' => 'Authenticated doctor user not found.'], 422);
            }

            $record = StudentMedicalRecord::query()->findOrFail((int) $medicalReferral->id);
            $payload = $request->validated();
            $payload['student_medical_record_id'] = (int) $record->id;
            $payload['student_id'] = (int) $record->student_id;
            $payload['created_by'] = (int) $user->id;
            $report = $this->service->createForMedicalRecord($record, $payload, $user);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'CBC report saved successfully.',
            'data' => $this->mapReportRow($report->load(['doctor:id,name'])),
        ], 201);
    }

    public function storeStandalone(StoreStudentCbcReportRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user instanceof User) {
                return response()->json(['message' => 'Authenticated doctor user not found.'], 422);
            }

            $payload = $request->validated();
            $student = Student::query()->findOrFail((int) $payload['student_id']);
            $payload['created_by'] = (int) $user->id;
            $report = $this->service->createStandaloneForStudent($student, $payload, $user);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Standalone CBC report saved successfully.',
            'data' => $this->mapReportRow($report->load(['doctor:id,name'])),
        ], 201);
    }

    public function update(UpdateStudentCbcReportRequest $request, StudentCbcReport $cbcReport): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user instanceof User) {
                return response()->json(['message' => 'Authenticated user not found.'], 422);
            }

            $payload = $request->validated();
            $payload['created_by'] = (int) ($cbcReport->created_by ?? $user->id);
            $updated = $this->service->updateReport($cbcReport, $payload, $user);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'CBC report updated successfully.',
            'data' => $this->mapReportRow($updated),
        ]);
    }

    public function showForDoctor(Request $request, StudentCbcReport $cbcReport): View
    {
        abort_unless($request->user()?->can('view_cbc_report') ?? false, 403);
        abort_if((int) $cbcReport->doctor_id !== (int) $request->user()?->id, 403);

        $report = $this->service->getReportForPrint($cbcReport);

        return view('modules.medical.cbc.show', [
            'report' => $report,
            'printUrl' => route('doctor.cbc-reports.print', $report),
            'title' => 'Doctor CBC Report Detail',
        ]);
    }

    public function showForPrincipal(Request $request, StudentCbcReport $cbcReport): View
    {
        abort_unless(($request->user()?->can('view_all_cbc_reports') ?? false) || ($request->user()?->can('view_cbc_report') ?? false), 403);

        $report = $this->service->getReportForPrint($cbcReport);

        return view('modules.medical.cbc.show', [
            'report' => $report,
            'printUrl' => route('principal.cbc-reports.print', $report),
            'title' => 'CBC Report Detail',
        ]);
    }

    public function printForDoctor(Request $request, StudentCbcReport $cbcReport): View
    {
        abort_unless($request->user()?->can('print_cbc_report') ?? false, 403);
        abort_if((int) $cbcReport->doctor_id !== (int) $request->user()?->id, 403);

        $report = $this->service->getReportForPrint($cbcReport);

        return view('modules.medical.cbc.print', [
            'report' => $report,
            'school' => $this->reportService->schoolMeta(),
        ]);
    }

    public function printForPrincipal(Request $request, StudentCbcReport $cbcReport): View
    {
        abort_unless($request->user()?->can('print_cbc_report') ?? false, 403);

        $report = $this->service->getReportForPrint($cbcReport);

        return view('modules.medical.cbc.print', [
            'report' => $report,
            'school' => $this->reportService->schoolMeta(),
        ]);
    }

    private function mapReportRow(StudentCbcReport $report): array
    {
        return [
            'id' => (int) $report->id,
            'student_medical_record_id' => $report->student_medical_record_id ? (int) $report->student_medical_record_id : null,
            'student_id' => (int) $report->student_id,
            'doctor_name' => (string) ($report->doctor?->name ?? ''),
            'report_date' => optional($report->report_date)->format('Y-m-d'),
            'machine_report_no' => (string) ($report->machine_report_no ?? ''),
            'remarks' => (string) ($report->remarks ?? ''),
        ];
    }
}
