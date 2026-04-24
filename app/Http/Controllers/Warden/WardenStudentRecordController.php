<?php

namespace App\Http\Controllers\Warden;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warden\WardenStudentRecordFilterRequest;
use App\Models\Student;
use App\Services\WardenStudentRecordService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class WardenStudentRecordController extends Controller
{
    public function __construct(
        private readonly WardenStudentRecordService $wardenStudentRecordService
    ) {
    }

    public function index(WardenStudentRecordFilterRequest $request): View
    {
        $records = $this->wardenStudentRecordService->getStudents(
            $request->validated(),
            $request->user()
        );

        return view('warden.students.index', $records);
    }

    public function show(Student $student, Request $request): View
    {
        $validated = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $record = $this->wardenStudentRecordService->getStudentRecord(
                $student,
                $validated['session'] ?? null,
                $request->user()
            );
        } catch (RuntimeException $exception) {
            abort(403, $exception->getMessage());
        }

        return view('warden.students.show', $record);
    }
}
