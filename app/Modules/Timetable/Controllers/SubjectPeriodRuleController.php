<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Subject;
use App\Models\SubjectPeriodRule;
use App\Modules\Timetable\Requests\StoreSubjectPeriodRuleRequest;
use App\Modules\Timetable\Requests\SubjectPeriodRuleListRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubjectPeriodRuleController extends Controller
{
    public function index(): View
    {
        $sessions = $this->sessionOptions();

        return view('modules.principal.timetable.subject-rules', [
            'sessions' => $sessions,
            'defaultSession' => $sessions[1] ?? ($sessions[0] ?? now()->year.'-'.(now()->year + 1)),
        ]);
    }

    public function options(): JsonResponse
    {
        $sections = ClassSection::query()
            ->with('classRoom:id,name,section')
            ->orderBy('class_id')
            ->orderBy('section_name')
            ->get(['id', 'class_id', 'section_name']);

        $subjects = Subject::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json([
            'sections' => $sections->map(function (ClassSection $section): array {
                $classLabel = trim(($section->classRoom?->name ?? '').' '.($section->classRoom?->section ?? ''));

                return [
                    'id' => $section->id,
                    'class_id' => $section->class_id,
                    'section_name' => $section->section_name,
                    'display_name' => trim($classLabel.' - '.$section->section_name),
                ];
            })->values()->all(),
            'subjects' => $subjects,
        ]);
    }

    public function data(SubjectPeriodRuleListRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $search = (string) ($filters['search'] ?? '');
        $session = (string) ($filters['session'] ?? '');
        $classSectionId = isset($filters['class_section_id']) ? (int) $filters['class_section_id'] : null;
        $perPage = (int) ($filters['per_page'] ?? 20);

        $query = SubjectPeriodRule::query()
            ->with([
                'classSection:id,class_id,section_name',
                'classSection.classRoom:id,name,section',
                'subject:id,name,code',
            ])
            ->when($session !== '', fn ($q) => $q->where('session', $session))
            ->when($classSectionId, fn ($q) => $q->where('class_section_id', $classSectionId))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($w) use ($search): void {
                    $w->where('session', 'like', $search.'%')
                        ->orWhereHas('subject', fn ($sq) => $sq->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('classSection', function ($csq) use ($search): void {
                            $csq->where('section_name', 'like', $search.'%')
                                ->orWhereHas('classRoom', function ($cq) use ($search): void {
                                    $cq->where('name', 'like', '%'.$search.'%')
                                        ->orWhere('section', 'like', '%'.$search.'%');
                                });
                        });
                });
            })
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(function (SubjectPeriodRule $rule): array {
                $classLabel = trim(($rule->classSection?->classRoom?->name ?? '').' '.($rule->classSection?->classRoom?->section ?? ''));

                return [
                    'id' => $rule->id,
                    'session' => $rule->session,
                    'class_section_id' => $rule->class_section_id,
                    'class_section_name' => trim($classLabel.' - '.($rule->classSection?->section_name ?? '')),
                    'subject_id' => $rule->subject_id,
                    'subject_name' => $rule->subject?->name,
                    'periods_per_week' => (int) $rule->periods_per_week,
                ];
            })->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }

    public function store(StoreSubjectPeriodRuleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $rule = DB::transaction(function () use ($data): SubjectPeriodRule {
            return SubjectPeriodRule::query()->updateOrCreate(
                [
                    'session' => $data['session'],
                    'class_section_id' => (int) $data['class_section_id'],
                    'subject_id' => (int) $data['subject_id'],
                ],
                [
                    'periods_per_week' => (int) $data['periods_per_week'],
                ]
            );
        });

        return response()->json([
            'message' => 'Subject period rule saved successfully.',
            'id' => $rule->id,
        ], 201);
    }

    public function update(StoreSubjectPeriodRuleRequest $request, SubjectPeriodRule $subjectPeriodRule): JsonResponse
    {
        $data = $request->validated();

        $duplicate = SubjectPeriodRule::query()
            ->where('id', '!=', $subjectPeriodRule->id)
            ->where('session', $data['session'])
            ->where('class_section_id', (int) $data['class_section_id'])
            ->where('subject_id', (int) $data['subject_id'])
            ->exists();

        if ($duplicate) {
            return response()->json(['message' => 'Rule already exists for this session, class section and subject.'], 422);
        }

        $subjectPeriodRule->update([
            'session' => $data['session'],
            'class_section_id' => (int) $data['class_section_id'],
            'subject_id' => (int) $data['subject_id'],
            'periods_per_week' => (int) $data['periods_per_week'],
        ]);

        return response()->json(['message' => 'Subject period rule updated successfully.']);
    }

    public function destroy(SubjectPeriodRule $subjectPeriodRule): Response
    {
        $subjectPeriodRule->delete();

        return response()->noContent();
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

