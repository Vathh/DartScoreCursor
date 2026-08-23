<?php

namespace Tests\Unit\League;

use App\Domain\League\LeagueSeasonStartReadiness;
use DomainException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeagueSeasonStartReadinessTest extends TestCase
{
    #[Test]
    public function empty_league_cannot_start(): void
    {
        $readiness = LeagueSeasonStartReadiness::inspect([]);

        $this->assertFalse($readiness->canStart);
        $this->assertSame('Liga nie ma szczebli.', $readiness->reason);
    }

    #[Test]
    public function single_incomplete_division_can_start(): void
    {
        $readiness = LeagueSeasonStartReadiness::inspect([
            $this->row('Jedyna', 8, 3),
        ]);

        $this->assertTrue($readiness->canStart);
        $this->assertNull($readiness->reason);
    }

    #[Test]
    public function higher_division_must_be_full(): void
    {
        $readiness = LeagueSeasonStartReadiness::inspect([
            $this->row('Ekstraklasa', 4, 3),
            $this->row('1. liga', 4, 4),
        ]);

        $this->assertFalse($readiness->canStart);
        $this->assertSame(['Ekstraklasa (3/4)'], $readiness->unfilledLabels);
        $this->assertStringContainsString('Ekstraklasa (3/4)', (string) $readiness->reason);
    }

    #[Test]
    public function last_division_may_have_vacancies(): void
    {
        $readiness = LeagueSeasonStartReadiness::inspect([
            $this->row('Ekstraklasa', 4, 4),
            $this->row('1. liga', 8, 5),
        ]);

        $this->assertTrue($readiness->canStart);
    }

    #[Test]
    public function middle_division_must_be_full_in_three_tier_pyramid(): void
    {
        $readiness = LeagueSeasonStartReadiness::inspect([
            $this->row('Ekstraklasa', 4, 4),
            $this->row('1. liga', 8, 7),
            $this->row('2. liga', 8, 2),
        ]);

        $this->assertFalse($readiness->canStart);
        $this->assertSame(['1. liga (7/8)'], $readiness->unfilledLabels);
    }

    #[Test]
    public function assert_can_start_throws_when_blocked(): void
    {
        $this->expectException(DomainException::class);

        LeagueSeasonStartReadiness::inspect([
            $this->row('Ekstraklasa', 4, 2),
            $this->row('1. liga', 4, 4),
        ])->assertCanStart();
    }

    /**
     * @return array{name: string, capacity: int, memberCount: int}
     */
    private function row(string $name, int $capacity, int $memberCount): array
    {
        return [
            'name' => $name,
            'capacity' => $capacity,
            'memberCount' => $memberCount,
        ];
    }
}
