<?php

namespace App\Domain\Career;

/**
 * Metryki jednego snapshotu kariery (JSON `metrics`).
 */
final class CareerSnapshotMetrics
{
    public const SCHEMA_VERSION = 2;

    /** @var list<string> */
    public const NON_X01_GAME_TYPES = [
        'cricket',
        'bob27',
        'atc',
        'catch40',
        'cricket56',
    ];

    public static function isX01(string $gameType): bool
    {
        return ! in_array($gameType, self::NON_X01_GAME_TYPES, true);
    }

    /**
     * @param  iterable<mixed>  $visits  obiekty z score, darts_in_visit, bust
     * @return array<string, mixed>|null null gdy brak lotek (walkower)
     */
    public static function fromX01Visits(
        iterable $visits,
        bool $doubleTracked,
        ?int $doubleAttempts,
        ?int $doubleSuccesses,
    ): ?array {
        $darts = 0;
        $points = 0;
        $count = 0;

        foreach ($visits as $visit) {
            $count++;
            $darts += (int) (is_array($visit) ? ($visit['darts_in_visit'] ?? $visit['dartsInVisit'] ?? 0) : $visit->darts_in_visit);
            $bust = (bool) (is_array($visit) ? ($visit['bust'] ?? false) : $visit->bust);
            if (! $bust) {
                $points += (int) (is_array($visit) ? ($visit['score'] ?? 0) : $visit->score);
            }
        }

        if ($count === 0 || $darts <= 0) {
            return null;
        }

        return self::pack(
            average: round(($points / $darts) * 3, 2),
            dartsThrown: $darts,
            points: $points,
            doubleTracked: $doubleTracked,
            doubleAttempts: $doubleTracked ? (int) ($doubleAttempts ?? 0) : null,
            doubleSuccesses: $doubleTracked ? (int) ($doubleSuccesses ?? 0) : null,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $dartLog
     * @return array<string, mixed>|null
     */
    public static function fromBob27DartLog(array $dartLog, int $playerId): ?array
    {
        $attempts = 0;
        $successes = 0;
        $dartsThrown = 0;
        $perDouble = [];

        foreach ($dartLog as $entry) {
            if ((int) ($entry['playerId'] ?? 0) !== $playerId) {
                continue;
            }

            $targetIndex = (int) ($entry['currentTargetIndex'] ?? 0);
            $key = self::bob27DoubleKey($targetIndex);
            $perDouble[$key] ??= ['attempts' => 0, 'successes' => 0];

            $kind = (string) ($entry['kind'] ?? 'dart');
            if ($kind === 'visit') {
                $hits = max(0, min(3, (int) ($entry['hits'] ?? 0)));
                $attempts += 3;
                $successes += $hits;
                $dartsThrown += 3;
                $perDouble[$key]['attempts'] += 3;
                $perDouble[$key]['successes'] += $hits;
            } else {
                $hit = (bool) ($entry['hit'] ?? false);
                $attempts++;
                $successes += $hit ? 1 : 0;
                $dartsThrown++;
                $perDouble[$key]['attempts']++;
                $perDouble[$key]['successes'] += $hit ? 1 : 0;
            }
        }

        if ($dartsThrown <= 0) {
            return null;
        }

        $ordered = [];
        for ($n = 1; $n <= 20; $n++) {
            $k = 'D'.$n;
            if (isset($perDouble[$k])) {
                $ordered[$k] = $perDouble[$k];
            }
        }
        if (isset($perDouble['Bull'])) {
            $ordered['Bull'] = $perDouble['Bull'];
        }

        return self::pack(
            average: null,
            dartsThrown: $dartsThrown,
            points: 0,
            doubleTracked: true,
            doubleAttempts: $attempts,
            doubleSuccesses: $successes,
            extra: ['per_double' => $ordered],
        );
    }

    /**
     * Snapshot bez średniej X01 (cricket / ATC / Catch40 / …).
     *
     * @return array<string, mixed>|null
     */
    public static function fromNonX01(?int $dartsThrown): ?array
    {
        $darts = (int) $dartsThrown;
        if ($darts <= 0) {
            return null;
        }

        return self::pack(
            average: null,
            dartsThrown: $darts,
            points: 0,
            doubleTracked: false,
            doubleAttempts: null,
            doubleSuccesses: null,
        );
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function pack(
        ?float $average,
        int $dartsThrown,
        int $points,
        bool $doubleTracked,
        ?int $doubleAttempts,
        ?int $doubleSuccesses,
        array $extra = [],
    ): array {
        return array_merge([
            'schema_version' => self::SCHEMA_VERSION,
            'average' => $average,
            'darts_thrown' => $dartsThrown,
            'points' => $points,
            'double_attempts' => $doubleAttempts,
            'double_successes' => $doubleSuccesses,
            'double_tracked' => $doubleTracked,
        ], $extra);
    }

    private static function bob27DoubleKey(int $targetIndex): string
    {
        if ($targetIndex >= 20) {
            return 'Bull';
        }

        return 'D'.($targetIndex + 1);
    }
}
