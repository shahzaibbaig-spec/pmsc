<?php

namespace App\Services;

use App\Models\CognitiveAssessmentAttempt;
use App\Models\CognitiveAssessmentSection;
use Illuminate\Support\Collection;

class CognitiveAssessmentReportService
{
    public function __construct(private readonly CognitiveAssessmentService $assessmentService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildStudentProfileReport(int $attemptId): array
    {
        /** @var CognitiveAssessmentAttempt $attempt */
        $attempt = CognitiveAssessmentAttempt::query()
            ->with(['assessment', 'student.classRoom'])
            ->findOrFail($attemptId);

        if ($attempt->status === CognitiveAssessmentAttempt::STATUS_IN_PROGRESS && $attempt->isExpired()) {
            $attempt = $this->assessmentService->submitAttempt($attempt, true);
        }

        $result = $this->assessmentService->buildStudentResult($attempt);
        $reviewSections = $this->assessmentService->buildAttemptReview($attempt);
        $scores = $this->profileScoreMap($result);
        $interpretation = $this->generateInterpretation($scores);
        $pathway = $this->generatePathwayRecommendation($scores);

        return [
            'attempt' => [
                'id' => (int) $attempt->id,
                'status' => (string) $attempt->status,
                'assessment_date' => optional($attempt->submitted_at ?: $attempt->created_at)->format('Y-m-d H:i'),
                'started_at' => optional($attempt->started_at)->format('Y-m-d H:i'),
                'submitted_at' => optional($attempt->submitted_at)->format('Y-m-d H:i'),
            ],
            'student' => $result['student'],
            'assessment' => $result['assessment'],
            'sections' => $result['sections'],
            'summary' => $result['summary'],
            'review_sections' => $reviewSections,
            'interpretation' => $interpretation,
            'pathway' => $pathway,
        ];
    }

    /**
     * @param array<string, mixed> $scores
     * @return array{
     *   summary_paragraph:string,
     *   strengths:array<int, string>,
     *   development_areas:array<int, string>
     * }
     */
    public function generateInterpretation(array $scores): array
    {
        $areas = collect($scores['areas'] ?? []);
        $ordered = $areas->sortByDesc('percentage')->values();
        $highest = $ordered->first();
        $lowest = $ordered->last();
        $range = (float) (($highest['percentage'] ?? 0) - ($lowest['percentage'] ?? 0));
        $overall = (float) ($scores['overall_percentage'] ?? 0);

        $strengths = $ordered
            ->filter(function (array $area) use ($highest): bool {
                return (float) $area['percentage'] >= max(((float) ($highest['percentage'] ?? 0)) - 5, 60);
            })
            ->map(fn (array $area): string => $area['label'].' is currently one of the stronger reasoning areas in this internal profile.')
            ->values();

        if ($strengths->isEmpty() && $highest) {
            $strengths = collect([
                $highest['label'].' currently stands out as the strongest reasoning area in this internal profile.',
            ]);
        }

        $developmentAreas = $ordered
            ->filter(function (array $area) use ($lowest, $highest): bool {
                return (float) $area['percentage'] <= 50
                    || (float) $area['percentage'] <= ((float) ($highest['percentage'] ?? 0) - 20)
                    || ((float) $area['percentage'] === (float) ($lowest['percentage'] ?? -1) && (float) $area['percentage'] < 65);
            })
            ->map(fn (array $area): string => $area['label'].' would benefit from continued reinforcement and guided practice.')
            ->unique()
            ->values();

        $topLabels = $ordered->take(2)->pluck('key')->all();
        $summary = match (true) {
            $overall < 40 || $developmentAreas->count() >= 2
                => 'This internal reasoning profile suggests the student currently needs more structured support across multiple reasoning areas before progressing to more demanding assessment tasks.',
            $range <= 10
                => 'This internal reasoning profile appears balanced across verbal, non-verbal, spatial, and quantitative reasoning, with no single area showing a large gap from the others.',
            ($highest['key'] ?? null) === CognitiveAssessmentSection::SKILL_VERBAL
                && (($scores['areas'][CognitiveAssessmentSection::SKILL_VERBAL]['percentage'] ?? 0) - ($scores['areas'][CognitiveAssessmentSection::SKILL_QUANTITATIVE]['percentage'] ?? 0) >= 12)
                => 'This internal reasoning profile suggests the student is currently stronger in verbal reasoning than quantitative reasoning, indicating more confidence with language-led understanding and verbal analysis.',
            in_array(CognitiveAssessmentSection::SKILL_SPATIAL, $topLabels, true)
                && in_array(CognitiveAssessmentSection::SKILL_NON_VERBAL, $topLabels, true)
                => 'This internal reasoning profile suggests stronger performance in spatial and non-verbal reasoning, with the student responding well to visual, pattern-based, and structural tasks.',
            in_array(CognitiveAssessmentSection::SKILL_QUANTITATIVE, $topLabels, true)
                && in_array(CognitiveAssessmentSection::SKILL_SPATIAL, $topLabels, true)
                => 'This internal reasoning profile suggests stronger quantitative and spatial reasoning, showing relative confidence with structured problem solving and visual-spatial thinking.',
            default
                => 'This internal reasoning profile shows a mixed pattern of strengths and development needs, with some areas performing more strongly than others at this stage.',
        };

        if ($developmentAreas->isNotEmpty() && $overall >= 40) {
            $summary .= ' The main current development focus is '.mb_strtolower(implode(', ', $ordered
                ->filter(fn (array $area): bool => $developmentAreas->contains($area['label'].' would benefit from continued reinforcement and guided practice.'))
                ->pluck('label')
                ->take(2)
                ->all())).'.';
        }

        return [
            'summary_paragraph' => $summary,
            'strengths' => $strengths->values()->all(),
            'development_areas' => $developmentAreas->values()->all(),
        ];
    }

    /**
     * @param array<string, mixed> $scores
     * @return array{pathway:string,support_direction:string,text:string}
     */
    public function generatePathwayRecommendation(array $scores): array
    {
        $verbal = (float) ($scores['areas'][CognitiveAssessmentSection::SKILL_VERBAL]['percentage'] ?? 0);
        $nonVerbal = (float) ($scores['areas'][CognitiveAssessmentSection::SKILL_NON_VERBAL]['percentage'] ?? 0);
        $quantitative = (float) ($scores['areas'][CognitiveAssessmentSection::SKILL_QUANTITATIVE]['percentage'] ?? 0);
        $spatial = (float) ($scores['areas'][CognitiveAssessmentSection::SKILL_SPATIAL]['percentage'] ?? 0);
        $overall = (float) ($scores['overall_percentage'] ?? 0);
        $range = (float) (($scores['max_percentage'] ?? 0) - ($scores['min_percentage'] ?? 0));
        $weakAreas = collect($scores['areas'] ?? [])->filter(fn (array $area): bool => (float) $area['percentage'] < 45)->count();

        return match (true) {
            $overall < 45 || $weakAreas >= 2 => [
                'pathway' => 'Targeted intervention pathway',
                'support_direction' => 'Suggested academic support direction',
                'text' => 'This internal profile suggests closer scaffolding across weaker reasoning areas before making broader pathway decisions. A targeted support plan with monitored progress, structured practice, and regular review would currently be the safest direction.',
            ],
            $range <= 10 => [
                'pathway' => 'Balanced academic pathway',
                'support_direction' => 'Suggested academic support direction',
                'text' => 'This internal profile appears broadly balanced across the four reasoning areas. A mixed academic pathway with continued exposure to language, mathematics, analytical, and visual-spatial tasks would be an appropriate internal support direction.',
            ],
            $quantitative >= max($verbal, $nonVerbal, $spatial) - 2
                && $spatial >= max($verbal, $nonVerbal, $quantitative) - 2
                && $nonVerbal >= 60 => [
                'pathway' => 'STEM-oriented support pathway',
                'support_direction' => 'Suggested academic support direction',
                'text' => 'This internal profile suggests relatively stronger quantitative, spatial, and non-verbal reasoning. It may support wider exposure to mathematics, science, structured problem-solving, technology, or design-rich learning while continuing balanced language support.',
            ],
            $verbal >= max($nonVerbal, $quantitative, $spatial)
                && ($verbal - $quantitative) >= 10 => [
                'pathway' => 'Language and humanities support pathway',
                'support_direction' => 'Suggested academic support direction',
                'text' => 'This internal profile suggests relatively stronger verbal reasoning. It may support deeper work in reading, writing, communication, analysis, and humanities-led learning while continuing to strengthen quantitative confidence.',
            ],
            default => [
                'pathway' => 'Balanced academic pathway',
                'support_direction' => 'Suggested academic support direction',
                'text' => 'This internal profile shows a mixed distribution of reasoning strengths. A balanced academic pathway with selective reinforcement in weaker areas would currently be the most suitable internal guidance.',
            ],
        };
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function buildPrincipalSummary(array $filters = []): array
    {
        $payload = $this->assessmentService->buildAdminReport($filters);
        $attempts = collect($payload['attempts']->items());
        $sectionTotals = collect($payload['assessment']->sections ?? [])
            ->mapWithKeys(fn ($section): array => [(string) $section->skill => max((int) $section->total_marks, 1)]);
        $profilePathways = $attempts
            ->filter(fn (CognitiveAssessmentAttempt $attempt): bool => $attempt->overall_percentage !== null)
            ->map(function (CognitiveAssessmentAttempt $attempt) use ($sectionTotals): string {
                $verbalPercentage = round((((float) $attempt->verbal_score) / (float) $sectionTotals->get(CognitiveAssessmentSection::SKILL_VERBAL, 1)) * 100, 2);
                $nonVerbalPercentage = round((((float) $attempt->non_verbal_score) / (float) $sectionTotals->get(CognitiveAssessmentSection::SKILL_NON_VERBAL, 1)) * 100, 2);
                $quantitativePercentage = round((((float) $attempt->quantitative_score) / (float) $sectionTotals->get(CognitiveAssessmentSection::SKILL_QUANTITATIVE, 1)) * 100, 2);
                $spatialPercentage = round((((float) $attempt->spatial_score) / (float) $sectionTotals->get(CognitiveAssessmentSection::SKILL_SPATIAL, 1)) * 100, 2);

                $scores = [
                    'areas' => [
                        CognitiveAssessmentSection::SKILL_VERBAL => [
                            'percentage' => $verbalPercentage,
                        ],
                        CognitiveAssessmentSection::SKILL_NON_VERBAL => [
                            'percentage' => $nonVerbalPercentage,
                        ],
                        CognitiveAssessmentSection::SKILL_QUANTITATIVE => [
                            'percentage' => $quantitativePercentage,
                        ],
                        CognitiveAssessmentSection::SKILL_SPATIAL => [
                            'percentage' => $spatialPercentage,
                        ],
                    ],
                    'overall_percentage' => (float) $attempt->overall_percentage,
                    'max_percentage' => collect([
                        $verbalPercentage,
                        $nonVerbalPercentage,
                        $quantitativePercentage,
                        $spatialPercentage,
                    ])->max(),
                    'min_percentage' => collect([
                        $verbalPercentage,
                        $nonVerbalPercentage,
                        $quantitativePercentage,
                        $spatialPercentage,
                    ])->min(),
                ];

                return $this->generatePathwayRecommendation($scores)['pathway'];
            })
            ->countBy()
            ->all();

        $payload['profile_summary'] = [
            'completed_on_page' => $attempts->whereNotNull('overall_percentage')->count(),
            'average_percentage_on_page' => round((float) ($attempts->whereNotNull('overall_percentage')->avg('overall_percentage') ?? 0), 2),
            'pathway_counts' => $profilePathways,
        ];

        return $payload;
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function profileScoreMap(array $result): array
    {
        $areas = collect($result['sections'] ?? [])
            ->mapWithKeys(function (array $section): array {
                return [
                    (string) $section['skill'] => [
                        'key' => (string) $section['skill'],
                        'label' => (string) $section['title'],
                        'score' => (int) $section['awarded_marks'],
                        'available' => (int) $section['available_marks'],
                        'percentage' => round((float) $section['percentage'], 2),
                    ],
                ];
            });

        return [
            'areas' => $areas->all(),
            'overall_percentage' => round((float) (($result['summary']['overall_percentage'] ?? 0)), 2),
            'performance_band' => (string) ($result['summary']['performance_band'] ?? 'Not Graded'),
            'max_percentage' => (float) $areas->max('percentage'),
            'min_percentage' => (float) $areas->min('percentage'),
        ];
    }
}
