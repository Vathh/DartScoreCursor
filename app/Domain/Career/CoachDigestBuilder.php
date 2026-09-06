<?php

namespace App\Domain\Career;

use Carbon\CarbonImmutable;

/**
 * Składa digest z wierszy snapshotów. Bez I/O, bez PII.
 */
final class CoachDigestBuilder
{
    /**
     * @param  list<array{source: string, game_type: string, metrics: array<string, mixed>}>  $current
     * @param  list<array{source: string, game_type: string, metrics: array<string, mixed>}>  $previous
     */
    public static function fromSnapshotRows(
        string $windowKey,
        array $current,
        array $previous,
        CarbonImmutable $generatedAt,
    ): CoachDigest {
        $hero = self::aggregateHero($current);
        $prev = $previous === [] ? null : self::aggregateHero($previous);
        $hero['x01AverageDelta'] = self::delta($hero['x01Average'], $prev['x01Average'] ?? null);
        $hero['doublePctDelta'] = self::delta($hero['doublePct'], $prev['doublePct'] ?? null);

        $readiness = CoachReadiness::evaluate(
            $hero['games'],
            $hero['hasX01'],
            $hero['hasDoubles'],
            (int) ($hero['doubleAttempts'] ?? 0),
        );

        [$perDouble, $weakest, $weakestPct] = self::aggregatePerDouble($current);

        return new CoachDigest(
            window: $windowKey,
            generatedAt: $generatedAt->toIso8601String(),
            ready: $readiness['ready'],
            notReadyReasons: $readiness['reasons'],
            hero: $hero,
            sources: self::countBy($current, 'source'),
            gameTypes: self::countBy($current, 'game_type'),
            perDouble: $perDouble,
            weakestDouble: $weakest,
            weakestDoublePct: $weakestPct,
            focusHints: self::focusHints($readiness['ready'], $hero, $weakest),
            modes: CoachModeCatalog::all(),
        );
    }

    /**
     * @param  list<array{source: string, game_type: string, metrics: array<string, mixed>}>  $rows
     * @return array{
     *     games: int,
     *     x01Average: float|null,
     *     x01AverageDelta: float|null,
     *     doublePct: float|null,
     *     doublePctDelta: float|null,
     *     doubleAttempts: int|null,
     *     doubleSuccesses: int|null,
     *     doubleLabel: string|null,
     *     hasX01: bool,
     *     hasDoubles: bool
     * }
     */
    private static function aggregateHero(array $rows): array
    {
        $games = count($rows);
        $darts = 0;
        $points = 0;
        $attempts = 0;
        $successes = 0;
        $hasX01 = false;
        $hasDoubles = false;

        foreach ($rows as $row) {
            $metrics = $row['metrics'];
            $gameDarts = (int) ($metrics['darts_thrown'] ?? 0);
            if (CareerSnapshotMetrics::isX01((string) $row['game_type']) && $gameDarts > 0) {
                $hasX01 = true;
                $darts += $gameDarts;
                $points += (int) ($metrics['points'] ?? 0);
            }
            if (! empty($metrics['double_tracked'])) {
                $hasDoubles = true;
                $attempts += (int) ($metrics['double_attempts'] ?? 0);
                $successes += (int) ($metrics['double_successes'] ?? 0);
            }
        }

        $x01Average = $hasX01 && $darts > 0 ? round(($points / $darts) * 3, 2) : null;
        $doublePct = $hasDoubles && $attempts > 0 ? round(($successes / $attempts) * 100, 1) : null;

        return [
            'games' => $games,
            'x01Average' => $x01Average,
            'x01AverageDelta' => null,
            'doublePct' => $doublePct,
            'doublePctDelta' => null,
            'doubleAttempts' => $hasDoubles ? $attempts : null,
            'doubleSuccesses' => $hasDoubles ? $successes : null,
            'doubleLabel' => $hasDoubles && $attempts > 0 ? $successes.'/'.$attempts : null,
            'hasX01' => $hasX01,
            'hasDoubles' => $hasDoubles,
        ];
    }

    /**
     * @param  list<array{source: string, game_type: string, metrics: array<string, mixed>}>  $rows
     * @return array{0: array<string, array{attempts: int, successes: int, pct: float|null}>, 1: string|null, 2: float|null}
     */
    private static function aggregatePerDouble(array $rows): array
    {
        $merged = [];

        foreach ($rows as $row) {
            if ((string) $row['game_type'] !== CoachModeCatalog::BOB27) {
                continue;
            }
            $per = $row['metrics']['per_double'] ?? [];
            if (! is_array($per)) {
                continue;
            }
            foreach ($per as $key => $stats) {
                if (! is_string($key) || ! is_array($stats)) {
                    continue;
                }
                $merged[$key] ??= ['attempts' => 0, 'successes' => 0];
                $merged[$key]['attempts'] += (int) ($stats['attempts'] ?? 0);
                $merged[$key]['successes'] += (int) ($stats['successes'] ?? 0);
            }
        }

        $out = [];
        $weakest = null;
        $weakestPct = null;

        foreach ($merged as $key => $stats) {
            $attempts = $stats['attempts'];
            $pct = $attempts > 0 ? round(($stats['successes'] / $attempts) * 100, 1) : null;
            $out[$key] = [
                'attempts' => $attempts,
                'successes' => $stats['successes'],
                'pct' => $pct,
            ];
            if ($attempts < CoachReadiness::MIN_SECTOR_ATTEMPTS || $pct === null) {
                continue;
            }
            if ($weakestPct === null || $pct < $weakestPct) {
                $weakest = $key;
                $weakestPct = $pct;
            }
        }

        return [$out, $weakest, $weakestPct];
    }

    /**
     * @param  list<array{source: string, game_type: string, metrics: array<string, mixed>}>  $rows
     * @return array<string, int>
     */
    private static function countBy(array $rows, string $field): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $key = (string) ($row[$field] ?? '');
            if ($key === '') {
                continue;
            }
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $hero
     * @return list<string>
     */
    private static function focusHints(bool $ready, array $hero, ?string $weakestDouble): array
    {
        if (! $ready) {
            return ['insufficient_data'];
        }

        $hints = [];
        $doublePct = $hero['doublePct'];
        $doubleDelta = $hero['doublePctDelta'];
        $x01Delta = $hero['x01AverageDelta'];

        if (is_numeric($doublePct) && (float) $doublePct < CoachReadiness::WEAK_DOUBLE_PCT) {
            $hints[] = 'doubles_weak';
        }
        if (is_numeric($doubleDelta) && (float) $doubleDelta <= CoachReadiness::DROPPING_DOUBLE_DELTA) {
            $hints[] = 'doubles_dropping';
        }
        if (is_numeric($x01Delta) && (float) $x01Delta <= CoachReadiness::DROPPING_X01_DELTA) {
            $hints[] = 'x01_dropping';
        }
        if (is_numeric($x01Delta) && abs((float) $x01Delta) < CoachReadiness::FLAT_X01_ABS && $hero['hasX01']) {
            $hints[] = 'x01_flat';
        }
        if ($weakestDouble !== null) {
            $hints[] = 'bob27_sector';
        }
        if ($hints === []) {
            $hints[] = 'steady';
        }

        return $hints;
    }

    private static function delta(?float $current, ?float $previous): ?float
    {
        if ($current === null || $previous === null) {
            return null;
        }

        return round($current - $previous, 2);
    }
}
