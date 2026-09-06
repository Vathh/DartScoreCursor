<?php

namespace Tests\Unit\Career;

use App\Domain\Career\CareerSnapshotMetrics;
use App\Domain\Career\CareerWindow;
use App\Domain\Career\DoubleOutRules;
use PHPUnit\Framework\TestCase;

class CareerMetricsTest extends TestCase
{
    public function test_double_out_remaining(): void
    {
        $this->assertTrue(DoubleOutRules::isCheckoutRemaining(40));
        $this->assertTrue(DoubleOutRules::isCheckoutRemaining(2));
        $this->assertTrue(DoubleOutRules::isCheckoutRemaining(50));
        $this->assertFalse(DoubleOutRules::isCheckoutRemaining(58));
        $this->assertFalse(DoubleOutRules::isCheckoutRemaining(41));
        $this->assertFalse(DoubleOutRules::isCheckoutRemaining(1));
    }

    public function test_count_from_darts_example_58_18_then_miss_then_d20(): void
    {
        $counted = DoubleOutRules::countFromDarts([
            ['remainingBefore' => 58, 'label' => '18', 'points' => 18],
            ['remainingBefore' => 40, 'label' => '0', 'points' => 0],
            ['remainingBefore' => 40, 'label' => 'D20', 'points' => 40],
        ]);

        $this->assertSame(2, $counted['attempts']);
        $this->assertSame(1, $counted['successes']);
    }

    public function test_bust_on_double_is_miss_not_padded(): void
    {
        $counted = DoubleOutRules::countFromDarts([
            ['remainingBefore' => 32, 'label' => 'T20', 'points' => 60, 'bust' => true],
        ]);

        $this->assertSame(1, $counted['attempts']);
        $this->assertSame(0, $counted['successes']);
    }

    public function test_x01_average_weighted_by_darts(): void
    {
        $visits = [
            (object) ['score' => 60, 'darts_in_visit' => 3, 'bust' => false],
            (object) ['score' => 0, 'darts_in_visit' => 3, 'bust' => true],
            (object) ['score' => 40, 'darts_in_visit' => 1, 'bust' => false],
        ];

        $metrics = CareerSnapshotMetrics::fromX01Visits($visits, false, null, null);

        $this->assertSame(7, $metrics['darts_thrown']);
        $this->assertSame(100, $metrics['points']);
        $this->assertEquals(42.86, $metrics['average']);
        $this->assertFalse($metrics['double_tracked']);
    }

    public function test_walkover_without_visits_returns_null(): void
    {
        $this->assertNull(CareerSnapshotMetrics::fromX01Visits([], false, null, null));
    }

    public function test_bob27_visit_counts_three_attempts(): void
    {
        $log = [
            [
                'playerId' => 5,
                'kind' => 'visit',
                'hits' => 2,
                'currentTargetIndex' => 0,
            ],
            [
                'playerId' => 5,
                'kind' => 'visit',
                'hits' => 0,
                'currentTargetIndex' => 19,
            ],
            [
                'playerId' => 9,
                'kind' => 'visit',
                'hits' => 3,
                'currentTargetIndex' => 0,
            ],
        ];

        $metrics = CareerSnapshotMetrics::fromBob27DartLog($log, 5);

        $this->assertSame(6, $metrics['darts_thrown']);
        $this->assertTrue($metrics['double_tracked']);
        $this->assertSame(6, $metrics['double_attempts']);
        $this->assertSame(2, $metrics['double_successes']);
        $this->assertSame(['attempts' => 3, 'successes' => 2], $metrics['per_double']['D1']);
        $this->assertSame(['attempts' => 3, 'successes' => 0], $metrics['per_double']['D20']);
    }

    public function test_window_default_is_90_days(): void
    {
        $window = CareerWindow::fromQuery(null);
        $this->assertSame('90d', $window->key);
        $this->assertSame(30, CareerWindow::fromQuery('30d')->days);
        $this->assertNull(CareerWindow::fromQuery('all')->days);
    }
}
