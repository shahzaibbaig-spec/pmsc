<?php

namespace App\Services;

class FederalBoardGradingService
{
    public const SOURCE_URL = 'https://www.fbise.edu.pk/notifications/grading_scheme_new.pdf';

    // FBISE notification of 7 October 2025. Fractional percentages are
    // compared directly with the lower bounds, without rounding upwards.
    public const BANDS = [
        ['minimum' => 96, 'grade' => 'A++', 'description' => 'Extraordinary'],
        ['minimum' => 91, 'grade' => 'A+', 'description' => 'Exceptional'],
        ['minimum' => 86, 'grade' => 'A', 'description' => 'Outstanding'],
        ['minimum' => 81, 'grade' => 'B++', 'description' => 'Excellent'],
        ['minimum' => 76, 'grade' => 'B+', 'description' => 'Very Good'],
        ['minimum' => 71, 'grade' => 'B', 'description' => 'Good'],
        ['minimum' => 61, 'grade' => 'C+', 'description' => 'Fairly Good'],
        ['minimum' => 51, 'grade' => 'C', 'description' => 'Above Average'],
        ['minimum' => 40, 'grade' => 'D', 'description' => 'Emerging'],
        ['minimum' => 0, 'grade' => 'U', 'description' => 'Ungraded'],
    ];

    public function grade(float $percentage): string
    {
        foreach (self::BANDS as $band) {
            if ($percentage >= $band['minimum']) {
                return $band['grade'];
            }
        }

        return 'U';
    }
}
