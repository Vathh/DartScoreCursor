<?php

namespace App\Domain\Career;

use App\Domain\GameScoring\VisitDartPayload;

final class X01CareerCollector
{
    /**
     * @param  iterable<mixed>  $visits
     * @param  list<array{darts?: int}>  $closedLegs
     * @return array<string, mixed>|null
     */
    public static function fromVisits(
        iterable $visits,
        array $closedLegs = [],
        bool $legacyDoubleTracked = false,
        ?int $legacyDoubleAttempts = null,
        ?int $legacyDoubleSuccesses = null,
    ): ?array {
        $list = [];
        foreach ($visits as $visit) {
            $list[] = $visit;
        }

        $base = CareerSnapshotMetrics::fromX01Visits(
            $list,
            false,
            null,
            null,
        );
        if ($base === null) {
            return null;
        }

        $buckets = ['180' => 0, '170' => 0, '140' => 0, '100' => 0, '80' => 0, '60' => 0];
        $checkouts = [];
        $dartRows = [];
        $hasDarts = false;

        foreach ($list as $visit) {
            $bust = (bool) CareerVisitFields::get($visit, 'bust', default: false);
            $score = (int) CareerVisitFields::get($visit, 'score', default: 0);
            $closed = (bool) CareerVisitFields::get($visit, 'closed_leg', 'closedLeg', false);
            $dartsInVisit = (int) CareerVisitFields::get($visit, 'darts_in_visit', 'dartsInVisit', 0);
            if (! $bust) {
                $key = self::visitScoreBucket($score);
                if ($key !== null) {
                    $buckets[$key]++;
                }
            }
            if ($closed && ! $bust) {
                $checkouts[] = [
                    'score' => $score,
                    'darts' => $dartsInVisit,
                ];
            }
            $rawDarts = CareerVisitFields::get($visit, 'darts', default: null);
            $normalized = VisitDartPayload::normalize(is_array($rawDarts) ? $rawDarts : null);
            if ($normalized !== null && $normalized !== []) {
                $hasDarts = true;
                foreach ($normalized as $dart) {
                    $dartRows[] = $dart;
                }
            }
        }

        $closed = $closedLegs !== [] ? $closedLegs : self::closedLegsFromVisits($list);
        $closedDarts = [];
        foreach ($closed as $row) {
            $n = (int) ($row['darts'] ?? 0);
            if ($n > 0) {
                $closedDarts[] = ['darts' => $n];
            }
        }

        $best = null;
        foreach ($closedDarts as $row) {
            $best = $best === null ? $row['darts'] : min($best, $row['darts']);
        }

        $doubleTracked = $hasDarts;
        $attempts = null;
        $successes = null;
        if ($hasDarts) {
            $counted = DoubleOutRules::countFromDarts($dartRows);
            $attempts = $counted['attempts'];
            $successes = $counted['successes'];
        } elseif ($legacyDoubleTracked) {
            $doubleTracked = true;
            $attempts = (int) ($legacyDoubleAttempts ?? 0);
            $successes = (int) ($legacyDoubleSuccesses ?? 0);
        }

        $base['visit_scores'] = $buckets;
        $base['closed_legs'] = $closedDarts;
        $base['checkouts'] = $checkouts;
        $base['best_leg_darts'] = $best;
        $base['double_tracked'] = $doubleTracked;
        $base['double_attempts'] = $doubleTracked ? (int) ($attempts ?? 0) : null;
        $base['double_successes'] = $doubleTracked ? (int) ($successes ?? 0) : null;
        $base['sectors'] = $hasDarts ? self::countSectors($dartRows) : null;

        return $base;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>|null
     */
    public static function normalizeClient(array $raw): ?array
    {
        if (isset($raw['visits']) && is_array($raw['visits'])) {
            return self::fromVisits(
                $raw['visits'],
                is_array($raw['closed_legs'] ?? null) ? $raw['closed_legs'] : [],
                (bool) ($raw['double_tracked'] ?? false),
                isset($raw['double_attempts']) ? (int) $raw['double_attempts'] : null,
                isset($raw['double_successes']) ? (int) $raw['double_successes'] : null,
            );
        }

        $darts = (int) ($raw['darts_thrown'] ?? 0);
        $points = (int) ($raw['points'] ?? 0);
        if ($darts <= 0) {
            return null;
        }

        $tracked = (bool) ($raw['double_tracked'] ?? false);
        $metrics = CareerSnapshotMetrics::pack(
            average: round(($points / $darts) * 3, 2),
            dartsThrown: $darts,
            points: $points,
            doubleTracked: $tracked,
            doubleAttempts: $tracked ? (int) ($raw['double_attempts'] ?? 0) : null,
            doubleSuccesses: $tracked ? (int) ($raw['double_successes'] ?? 0) : null,
            extra: [
                'visit_scores' => self::normalizeBuckets($raw['visit_scores'] ?? null),
                'closed_legs' => self::normalizeClosedLegs($raw['closed_legs'] ?? null),
                'checkouts' => self::normalizeCheckouts($raw['checkouts'] ?? null),
                'best_leg_darts' => isset($raw['best_leg_darts']) ? (int) $raw['best_leg_darts'] : null,
                'sectors' => self::normalizeSectors($raw['sectors'] ?? null),
            ],
        );

        return self::mergeExtras($raw, $metrics);
    }

    /**
     * @param  list<mixed>  $visits
     * @return list<array{darts: int}>
     */
    private static function closedLegsFromVisits(array $visits): array
    {
        $groups = [];
        foreach ($visits as $visit) {
            $legKey = CareerVisitFields::get($visit, 'game_leg_id', 'gameLegId')
                ?? CareerVisitFields::get($visit, 'leg_number', 'legNumber')
                ?? '_';
            $key = (string) $legKey;
            $groups[$key] ??= ['darts' => 0, 'closed' => false];
            $groups[$key]['darts'] += (int) CareerVisitFields::get($visit, 'darts_in_visit', 'dartsInVisit', 0);
            if (CareerVisitFields::get($visit, 'closed_leg', 'closedLeg', false)) {
                $groups[$key]['closed'] = true;
            }
        }

        $out = [];
        foreach ($groups as $group) {
            if ($group['closed'] && $group['darts'] > 0) {
                $out[] = ['darts' => $group['darts']];
            }
        }

        return $out;
    }

    public static function visitScoreBucket(int $score): ?string
    {
        return match (true) {
            $score === 180 => '180',
            $score >= 170 => '170',
            $score >= 140 => '140',
            $score >= 100 => '100',
            $score >= 80 => '80',
            $score >= 60 => '60',
            default => null,
        };
    }

    /**
     * @param  list<array{sector?: int}>  $darts
     * @return array<string, int>
     */
    private static function countSectors(array $darts): array
    {
        $counts = self::emptySectors();
        foreach ($darts as $dart) {
            $sector = (int) ($dart['sector'] ?? 0);
            $key = $sector === 25 ? '25' : (string) $sector;
            if (! array_key_exists($key, $counts)) {
                $key = '0';
            }
            $counts[$key]++;
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    public static function emptySectors(): array
    {
        $out = [];
        for ($n = 1; $n <= 20; $n++) {
            $out[(string) $n] = 0;
        }
        $out['25'] = 0;
        $out['0'] = 0;

        return $out;
    }

    /**
     * @return array<string, int>
     */
    private static function normalizeBuckets(mixed $raw): array
    {
        $out = ['180' => 0, '170' => 0, '140' => 0, '100' => 0, '80' => 0, '60' => 0];
        if (! is_array($raw)) {
            return $out;
        }
        foreach ($out as $key => $_) {
            $out[$key] = (int) ($raw[$key] ?? 0);
        }

        return $out;
    }

    /**
     * @return list<array{darts: int}>
     */
    private static function normalizeClosedLegs(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $n = (int) ($row['darts'] ?? 0);
            if ($n > 0) {
                $out[] = ['darts' => $n];
            }
        }

        return $out;
    }

    /**
     * @return list<array{score: int, darts: int}>
     */
    private static function normalizeCheckouts(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'score' => (int) ($row['score'] ?? 0),
                'darts' => (int) ($row['darts'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, int>|null
     */
    private static function normalizeSectors(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }
        $out = self::emptySectors();
        foreach ($out as $key => $_) {
            $out[$key] = (int) ($raw[$key] ?? 0);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $metrics
     * @return array<string, mixed>
     */
    private static function mergeExtras(array $raw, array $metrics): array
    {
        foreach ($raw as $key => $value) {
            if (! array_key_exists($key, $metrics)) {
                $metrics[$key] = $value;
            }
        }

        return $metrics;
    }
}
