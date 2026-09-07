<?php

namespace Tests\Unit\Player;

use App\Domain\Player\OverviewActivityStats;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OverviewActivityStatsTest extends TestCase
{
    #[Test]
    public function empty_dates_have_zero_streaks(): void
    {
        $stats = OverviewActivityStats::fromDates([], '2026-09-07');

        $this->assertSame(0, $stats['activity_days']);
        $this->assertSame(0, $stats['current_streak']);
        $this->assertSame(0, $stats['longest_streak']);
        $this->assertNull($stats['last_activity_on']);
    }

    #[Test]
    public function current_streak_survives_skipping_today_but_not_two_days(): void
    {
        $alive = OverviewActivityStats::fromDates(['2026-09-05', '2026-09-06'], '2026-09-07');
        $this->assertSame(2, $alive['current_streak']);
        $this->assertSame(2, $alive['longest_streak']);
        $this->assertSame('2026-09-06', $alive['last_activity_on']);

        $broken = OverviewActivityStats::fromDates(['2026-09-04', '2026-09-05'], '2026-09-07');
        $this->assertSame(0, $broken['current_streak']);
        $this->assertSame(2, $broken['longest_streak']);
    }

    #[Test]
    public function longest_streak_ignores_gaps(): void
    {
        $stats = OverviewActivityStats::fromDates([
            '2026-09-01',
            '2026-09-02',
            '2026-09-03',
            '2026-09-06',
            '2026-09-07',
        ], '2026-09-07');

        $this->assertSame(5, $stats['activity_days']);
        $this->assertSame(2, $stats['current_streak']);
        $this->assertSame(3, $stats['longest_streak']);
    }

    #[Test]
    public function recent_days_marks_played_days_oldest_first(): void
    {
        $days = OverviewActivityStats::recentDays(['2026-09-07', '2026-09-05'], '2026-09-07', 7);

        $this->assertCount(7, $days);
        $this->assertSame('2026-09-01', $days[0]['date']);
        $this->assertSame('2026-09-07', $days[6]['date']);
        $this->assertFalse($days[0]['played']);
        $this->assertTrue($days[4]['played']);
        $this->assertFalse($days[5]['played']);
        $this->assertTrue($days[6]['played']);
        $this->assertSame('pn', $days[6]['label']);
    }
}
