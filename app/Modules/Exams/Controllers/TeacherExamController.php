<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Exams\Requests\ExamSheetRequest;
use App\Modules\Exams\Requests\SaveMarksRequest;
use App\Modules\Exams\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class TeacherExamController extends Controller
{
    public function __construct(private readonly ExamService $service)
    {
    }

    public function index(): View
    {
        $options = $this->service->optionsForTeacher((int) auth()->id());

        return view('modules.teacher.exams.index', [
            'sessions' => $options['sessions'],
            'assignments' => $options['assignments'],
            'examTypes' => $options['exam_types'],
            'hasAssignments' => ! empty($options['assignments']),
        ]);
    }

    public function options(): JsonResponse
    {
        $options = $this->service->optionsForTeacher((int) auth()->id());

        return response()->json($options);
    }

    public function sheet(ExamSheetRequest $request): JsonResponse
    {
        try {
            $sheet = $this->service->sheet(
                (int) auth()->id(),
                (int) $request->input('class_id'),
                (int) $request->input('subject_id'),
                $request->string('session')->toString(),
                $request->string('exam_type')->toString()
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => config('app.debug')
                    ? $exception->getMessage()
                    : 'Unexpected error while loading marks sheet.',
            ], 500);
        }

        return response()->json($sheet);
    }

    public function save(SaveMarksRequest $request): JsonResponse
    {
        try {
            $this->service->saveMarks(
                (int) auth()->id(),
                (int) $request->input('class_id'),
                (int) $request->input('subject_id'),
                $request->string('session')->toString(),
                $request->string('exam_type')->toString(),
                $request->filled('total_marks') ? (int) $request->input('total_marks') : null,
                $request->input('records', [])
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => config('app.debug')
                    ? $exception->getMessage()
                    : 'Unexpected error while saving assessment entries. Please contact admin.',
            ], 500);
        }

        return response()->json(['message' => 'Marks saved successfully. Teacher CGPA and ACR metrics have been updated.']);
    }
}
