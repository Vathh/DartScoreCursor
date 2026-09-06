<?php

namespace App\Domain\Career;

final class CricketCareerCollector
{
    /**
     * @param  list<array<string, mixed>>  $dartLog
     * @return array<string, mixed>|null
     */
    public static function fromDartLog(array $dartLog, int $playerId, bool $won): ?array
    {
        $dartsThrown = 0;
        $marks = self::emptySectors();
        $points = self::emptySectors();
        $hits = self::emptySectors();

        foreach ($dartLog as $entry) {
            if ((int) ($entry['playerId'] ?? 0) !== $playerId) {
                continue;
            }
            $kind = (string) ($entry['kind'] ?? '');
            if ($kind !== 'hit' && $kind !== 'miss') {
                continue;
            }
            $dartsThrown++;
            if ($kind !== 'hit') {
                continue;
            }
            $key = self::segmentKey($entry['segment'] ?? '');
            if (! array_key_exists($key, $marks)) {
                continue;
            }
            $hits[$key]++;
            $marks[$key] += max(1, min(3, (int) ($entry['multiplier'] ?? 1)));
            $points[$key] += (int) ($entry['pointsScored'] ?? 0);
        }

        if ($dartsThrown <= 0) {
            return null;
        }

        return [
            'schema_version' => CareerSnapshotMetrics::SCHEMA_VERSION,
            'darts_thrown' => $dartsThrown,
            'marks' => $marks,
            'points' => $points,
            'hits' => $hits,
            'win_darts' => $won ? $dartsThrown : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>|null
     */
    public static function normalizeClient(array $raw): ?array
    {
        if (isset($raw['dart_log']) && is_array($raw['dart_log'])) {
            return self::fromDartLog(
                $raw['dart_log'],
                (int) ($raw['player_id'] ?? 0),
                (bool) ($raw['won'] ?? false),
            );
        }

        $darts = (int) ($raw['darts_thrown'] ?? 0);
        if ($darts <= 0) {
            return null;
        }

        return [
            'schema_version' => CareerSnapshotMetrics::SCHEMA_VERSION,
            'darts_thrown' => $darts,
            'marks' => self::normalizeSectorMap($raw['marks'] ?? null),
            'points' => self::normalizeSectorMap($raw['points'] ?? null),
            'hits' => self::normalizeSectorMap($raw['hits'] ?? null),
            'win_darts' => array_key_exists('win_darts', $raw) && $raw['win_darts'] !== null
                ? (int) $raw['win_darts']
                : null,
        ];
    }

    private static function segmentKey(int|string $segment): string
    {
        return $segment === 'bull' || $segment === 25 || $segment === '25' ? 'bull' : (string) $segment;
    }

    /**
     * @return array<string, int>
     */
    public static function emptySectors(): array
    {
        return [
            '20' => 0,
            '19' => 0,
            '18' => 0,
            '17' => 0,
            '16' => 0,
            '15' => 0,
            'bull' => 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private static function normalizeSectorMap(mixed $raw): array
    {
        $out = self::emptySectors();
        if (! is_array($raw)) {
            return $out;
        }
        foreach ($out as $key => $_) {
            $out[$key] = (int) ($raw[$key] ?? 0);
        }

        return $out;
    }
}
