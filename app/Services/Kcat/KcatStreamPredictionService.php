<?php

namespace App\Services\Kcat;

use App\Models\KcatAttempt;
use App\Models\KcatStreamRecommendation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KcatStreamPredictionService
{
    private const STREAM_WEIGHTS = [
        'Pre-Medical' => [
            'verbal_reasoning' => 25,
            'quantitative_reasoning' => 25,
            'non_verbal_reasoning' => 25,
            'spatial_reasoning' => 25,
        ],
        'Pre-Engineering' => [
            'verbal_reasoning' => 10,
            'quantitative_reasoning' => 35,
            'non_verbal_reasoning' => 25,
            'spatial_reasoning' => 30,
        ],
        'Computer Science' => [
            'verbal_reasoning' => 10,
            'quantitative_reasoning' => 35,
            'non_verbal_reasoning' => 35,
            'spatial_reasoning' => 20,
        ],
        'Commerce / Business' => [
            'verbal_reasoning' => 30,
            'quantitative_reasoning' => 35,
            'non_verbal_reasoning' => 20,
            'spatial_reasoning' => 15,
        ],
        'Law / Civil Services' => [
            'verbal_reasoning' => 45,
            'quantitative_reasoning' => 15,
            'non_verbal_reasoning' => 25,
            'spatial_reasoning' => 15,
        ],
        'Teaching / Humanities' => [
            'verbal_reasoning' => 45,
            'quantitative_reasoning' => 10,
            'non_verbal_reasoning' => 25,
            'spatial_reasoning' => 20,
        ],
        'Arts / Media' => [
            'verbal_reasoning' => 30,
            'quantitative_reasoning' => 10,
            'non_verbal_reasoning' => 35,
            'spatial_reasoning' => 25,
        ],
        'Technical / Vocational' => [
            'verbal_reasoning' => 10,
            'quantitative_reasoning' => 20,
            'non_verbal_reasoning' => 25,
            'spatial_reasoning' => 45,
        ],
    ];

    public function generateRecommendations(KcatAttempt $attempt): Collection
    {
        $attempt->loadMissing('scores');
        $scores = $attempt->scores->keyBy('section_code');
        $sectionScores = [
            'verbal_reasoning' => (float) ($scores->get('verbal_reasoning')?->percentage ?? 0),
            'quantitative_reasoning' => (float) ($scores->get('quantitative_reasoning')?->percentage ?? 0),
            'non_verbal_reasoning' => (float) ($scores->get('non_verbal_reasoning')?->percentage ?? 0),
            'spatial_reasoning' => (float) ($scores->get('spatial_reasoning')?->percentage ?? 0),
        ];

        $base = collect(self::STREAM_WEIGHTS)
            ->map(function (array $weights, string $stream) use ($sectionScores): array {
                $score = $this->calculateStreamScore($sectionScores, $weights);

                return [
                    'stream_name' => $stream,
                    'match_score' => $score,
                    'confidence_band' => $this->determineConfidenceBand($score),
                    'reasoning_summary' => $this->buildReasoningSummary($stream, $sectionScores, $score),
                ];
            })
            ->sortByDesc('match_score')
            ->values();

        $topThree = $base->take(3)->values();
        if (
            $topThree->count() === 3
            && abs((float) $topThree[0]['match_score'] - (float) $topThree[2]['match_score']) <= 5.0
        ) {
            $multiScore = round((float) $topThree->avg('match_score'), 2);
            $multiplePathways = [
                'stream_name' => 'Multiple Pathways',
                'match_score' => $multiScore,
                'confidence_band' => $this->determineConfidenceBand($multiScore),
                'reasoning_summary' => 'Top stream matches are closely grouped, so multiple pathways remain realistic options.',
            ];

            return collect([$multiplePathways, $topThree[0], $topThree[1]])
                ->values()
                ->map(function (array $row, int $index): array {
                    $row['rank'] = $index + 1;
                    return $row;
                });
        }

        return $topThree
            ->map(function (array $row, int $index): array {
                $row['rank'] = $index + 1;
                return $row;
            })
            ->values();
    }

    public function calculateStreamScore(array $sectionScores, array $weights): float
    {
        $weighted = 0.0;
        foreach ($weights as $sectionCode => $weight) {
            $weighted += ((float) ($sectionScores[$sectionCode] ?? 0)) * (((float) $weight) / 100);
        }

        return round($weighted, 2);
    }

    public function determineConfidenceBand(float $score): string
    {
        return match (true) {
            $score >= 85 => 'very_high',
            $score >= 70 => 'high',
            $score >= 55 => 'moderate',
            default => 'exploratory',
        };
    }

    public function buildReasoningSummary(string $stream, array $sectionScores, float $score): string
    {
        $ordered = collect($sectionScores)
            ->mapWithKeys(fn (float $value, string $key): array => [str_replace('_', ' ', $key) => $value])
            ->sortDesc();
        $strongest = (string) $ordered->keys()->first();
        $nextStrong = (string) ($ordered->keys()->get(1) ?? $strongest);

        return sprintf(
            '%s matched at %.2f%%, mainly supported by %s and %s performance.',
            $stream,
            $score,
            ucfirst($strongest),
            ucfirst($nextStrong)
        );
    }

    public function saveRecommendations(KcatAttempt $attempt, array $recommendations): void
    {
        DB::transaction(function () use ($attempt, $recommendations): void {
            KcatStreamRecommendation::query()
                ->where('kcat_attempt_id', $attempt->id)
                ->delete();

            $rows = collect($recommendations)
                ->take(3)
                ->map(function (array $recommendation) use ($attempt): array {
                    return [
                        'kcat_attempt_id' => $attempt->id,
                        'stream_name' => $recommendation['stream_name'],
                        'match_score' => (float) $recommendation['match_score'],
                        'confidence_band' => $recommendation['confidence_band'] ?? null,
                        'reasoning_summary' => $recommendation['reasoning_summary'] ?? null,
                        'rank' => (int) ($recommendation['rank'] ?? 1),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })
                ->values()
                ->all();

            if ($rows !== []) {
                KcatStreamRecommendation::query()->insert($rows);
            }

            $primary = $rows[0] ?? null;
            $attempt->update([
                'recommended_stream' => $primary['stream_name'] ?? null,
                'recommendation_summary' => $primary['reasoning_summary'] ?? null,
            ]);
        });
    }
}

