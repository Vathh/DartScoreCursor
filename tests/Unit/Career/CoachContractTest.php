<?php

namespace Tests\Unit\Career;

use App\Domain\Career\CareerSnapshotMetrics;
use App\Domain\Career\CoachDigestBuilder;
use App\Domain\Career\CoachFallbackPlanner;
use App\Domain\Career\CoachModeCatalog;
use App\Domain\Career\CoachPlan;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CoachContractTest extends TestCase
{
    public function test_catalog_contains_known_modes_and_rejects_unknown(): void
    {
        $ids = CoachModeCatalog::ids();
        $this->assertContains('x01', $ids);
        $this->assertContains('bob27', $ids);
        $this->assertContains('catch40', $ids);
        $this->assertContains('atc', $ids);
        $this->assertContains('cricket', $ids);
        $this->assertContains('cricket56', $ids);
        $this->assertTrue(CoachModeCatalog::has('bob27'));
        $this->assertFalse(CoachModeCatalog::has('killer'));
    }

    public function test_digest_has_no_pii_and_aggregates_per_double(): void
    {
        $current = [
            $this->x01Row('quick', 60, 3, 60, false),
            $this->x01Row('training', 45, 3, 45, true, 2, 1),
            $this->bob27Row('training', [
                'D16' => ['attempts' => 9, 'successes' => 1],
                'D20' => ['attempts' => 9, 'successes' => 6],
            ]),
            $this->bob27Row('quick', [
                'D16' => ['attempts' => 3, 'successes' => 0],
            ]),
        ];
        $previous = [
            $this->x01Row('quick', 70, 3, 70, true, 4, 3),
        ];

        $digest = CoachDigestBuilder::fromSnapshotRows(
            '90d',
            $current,
            $previous,
            CarbonImmutable::parse('2026-09-06T12:00:00+02:00'),
        )->toArray();

        $this->assertSame(1, $digest['schemaVersion']);
        $this->assertSame('90d', $digest['window']);
        $this->assertTrue($digest['ready']);
        $this->assertArrayNotHasKey('playerId', $digest);
        $this->assertArrayNotHasKey('name', $digest);
        $this->assertSame(4, $digest['hero']['games']);
        $this->assertSame(2, $digest['sources']['training']);
        $this->assertSame(2, $digest['sources']['quick']);
        $this->assertSame(12, $digest['bob27']['perDouble']['D16']['attempts']);
        $this->assertSame(1, $digest['bob27']['perDouble']['D16']['successes']);
        $this->assertSame('D16', $digest['bob27']['weakest']);
        $this->assertContains('bob27_sector', $digest['focusHints']);
        $this->assertSame(CoachModeCatalog::ids(), array_column($digest['modes'], 'id'));
        $json = json_encode($digest);
        $this->assertIsString($json);
        $this->assertStringNotContainsString('Tom', $json);
    }

    public function test_digest_not_ready_without_volume(): void
    {
        $digest = CoachDigestBuilder::fromSnapshotRows(
            '90d',
            [$this->x01Row('quick', 60, 3, 60, false)],
            [],
            CarbonImmutable::now(),
        )->toArray();

        $this->assertFalse($digest['ready']);
        $this->assertContains('too_few_games', $digest['notReadyReasons']);
        $this->assertSame(['insufficient_data'], $digest['focusHints']);
    }

    public function test_plan_rejects_unknown_mode_and_bad_duration(): void
    {
        $valid = $this->validPlanPayload();

        $badMode = $valid;
        $badMode['cards'][0]['modeId'] = 'killer';
        try {
            CoachPlan::fromArray($badMode);
            $this->fail('Oczekiwano odrzucenia nieznanego trybu.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('tryb', $e->getMessage());
        }

        $badDuration = $valid;
        $badDuration['cards'][0]['durationMin'] = 45;
        try {
            CoachPlan::fromArray($badDuration);
            $this->fail('Oczekiwano odrzucenia złej długości.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('30', $e->getMessage());
        }
    }

    public function test_fallback_plan_is_schema_valid_and_uses_catalog(): void
    {
        $digest = CoachDigestBuilder::fromSnapshotRows(
            '90d',
            [
                $this->x01Row('quick', 50, 9, 150, true, 6, 1),
                $this->x01Row('training', 48, 9, 144, true, 6, 1),
                $this->bob27Row('training', [
                    'D8' => ['attempts' => 9, 'successes' => 1],
                    'D16' => ['attempts' => 9, 'successes' => 5],
                ]),
            ],
            [
                $this->x01Row('quick', 55, 9, 165, true, 6, 3),
            ],
            CarbonImmutable::now(),
        );

        $plan = CoachFallbackPlanner::plan($digest);
        $payload = $plan->toArray();

        $this->assertSame(CoachPlan::SOURCE_TEMPLATE, $payload['source']);
        $this->assertSame(1, $payload['schemaVersion']);
        $this->assertCount(3, $payload['cards']);
        $this->assertSame([30, 60, 90], array_column($payload['cards'], 'durationMin'));
        foreach ($payload['cards'] as $card) {
            $this->assertTrue(CoachModeCatalog::has($card['modeId']));
        }
        $this->assertSame($payload, CoachPlan::fromArray($payload)->toArray());
    }

    public function test_x01_game_type_helper(): void
    {
        $this->assertTrue(CareerSnapshotMetrics::isX01('x01'));
        $this->assertTrue(CareerSnapshotMetrics::isX01('501'));
        $this->assertFalse(CareerSnapshotMetrics::isX01('bob27'));
        $this->assertFalse(CareerSnapshotMetrics::isX01('cricket56'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validPlanPayload(): array
    {
        return [
            'source' => CoachPlan::SOURCE_LLM,
            'headline' => 'Pracuj nad dublem',
            'focus' => 'Duble',
            'cards' => [
                [
                    'durationMin' => 30,
                    'modeId' => 'bob27',
                    'focus' => 'Bob’s 27',
                    'why' => 'D16 jest najsłabszy.',
                    'steps' => ['Zagraj rundę Bob’s 27.'],
                ],
            ],
        ];
    }

    /**
     * @return array{source: string, game_type: string, metrics: array<string, mixed>}
     */
    private function x01Row(
        string $source,
        float $average,
        int $darts,
        int $points,
        bool $doubleTracked,
        ?int $attempts = null,
        ?int $successes = null,
    ): array {
        return [
            'source' => $source,
            'game_type' => 'x01',
            'metrics' => [
                'average' => $average,
                'darts_thrown' => $darts,
                'points' => $points,
                'double_tracked' => $doubleTracked,
                'double_attempts' => $attempts,
                'double_successes' => $successes,
            ],
        ];
    }

    /**
     * @param  array<string, array{attempts: int, successes: int}>  $perDouble
     * @return array{source: string, game_type: string, metrics: array<string, mixed>}
     */
    private function bob27Row(string $source, array $perDouble): array
    {
        $attempts = 0;
        $successes = 0;
        foreach ($perDouble as $row) {
            $attempts += $row['attempts'];
            $successes += $row['successes'];
        }

        return [
            'source' => $source,
            'game_type' => 'bob27',
            'metrics' => [
                'average' => null,
                'darts_thrown' => $attempts,
                'points' => 0,
                'double_tracked' => true,
                'double_attempts' => $attempts,
                'double_successes' => $successes,
                'per_double' => $perDouble,
            ],
        ];
    }
}
