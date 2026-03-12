<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Modules\Timetable\Requests\GenerateTimetableRequest;
use App\Modules\Timetable\Services\TimetableGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TimetableGenerationController extends Controller
{
    public function index(): View
    {
        $sessions = $this->sessionOptions();

        $classSections = ClassSection::query()
            ->with('classRoom:id,name,section')
            ->orderBy('class_id')
            ->orderBy('section_name')
            ->get(['id', 'class_id', 'section_name'])
            ->map(function (ClassSection $section): array {
                $classLabel = trim(($section->classRoom?->name ?? '').' '.($section->classRoom?->section ?? ''));

                return [
                    'id' => $section->id,
                    'class_id' => $section->class_id,
                    'section_name' => $section->section_name,
                    'display_name' => trim($classLabel.' - '.$section->section_name),
                ];
            })
            ->values();

        return view('modules.principal.timetable.generate', [
            'sessions' => $sessions,
            'defaultSession' => $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1)),
            'classSections' => $classSections,
        ]);
    }

    public function generate(
        GenerateTimetableRequest $request,
        TimetableGeneratorService $generatorService
    ): JsonResponse {
        $data = $request->validated();

        $result = $generatorService->generate(
            (string) $data['session'],
            array_values($data['class_section_ids'])
        );

        return response()->json($result);
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
