<?php

namespace Tests\Unit;

use App\Services\FederalBoardGradingService;
use PHPUnit\Framework\TestCase;

class FederalBoardGradingServiceTest extends TestCase
{
    public function test_every_boundary_and_fractional_score(): void
    {
        $service = new FederalBoardGradingService();
        foreach ([
            [100, 'A++'], [96, 'A++'], [95.99, 'A+'], [91, 'A+'],
            [90.99, 'A'], [86, 'A'], [85.99, 'B++'], [81, 'B++'],
            [80.99, 'B+'], [76, 'B+'], [75.99, 'B'], [71, 'B'],
            [70.99, 'C+'], [61, 'C+'], [60.99, 'C'], [51, 'C'],
            [50.99, 'D'], [40, 'D'], [39.99, 'U'], [0, 'U'],
        ] as [$percentage, $expected]) {
            $this->assertSame($expected, $service->grade($percentage), 'Percentage: '.$percentage);
        }
    }
}
