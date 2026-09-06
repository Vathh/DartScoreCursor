<?php

namespace App\Domain\Career;

use App\Domain\QuickGame\AroundTheClockRules;

final class AtcCareerCollector
{
    /**
     * @param  list<array<string, mixed>>  $dartLog
     * @return array<string, mixed>|null
     */
    public static function fromDartLog(array $dartLog, int $playerId): ?array
    {
        $dartsThrown = 0;
        $sectors = self::emptySectors();

        foreach ($dartLog as $entry) {
            if ((int) ($entry['playerId'] ?? 0) !== $playerId) {
                continue;
            }
            if (($entry['kind'] ?? 'visit') !== 'visit') {
                continue;
            }

            $hits = max(0, min(3, (int) ($entry['hits'] ?? 0)));
            $finished = (bool) ($entry['finished'] ?? false);
            $targetBefore = self::targetIndexFromSnapshot($entry, $playerId);
            $attemptsThisVisit = $finished ? max(1, $hits) : 3;
            $dartsThrown += $attemptsThisVisit;

            for ($i = 0; $i < $hits; $i++) {
                $key = self::sectorKey($targetBefore + $i);
                $sectors[$key]['successes']++;
                $sectors[$key]['attempts']++;
            }
            $misses = $attemptsThisVisit - $hits;
            if ($misses > 0) {
                $key = self::sectorKey($targetBefore + $hits);
                $sectors[$key]['attempts'] += $misses;
            }
        }

        if ($dartsThrown <= 0) {
            return null;
        }

        return [
            'schema_version' => CareerSnapshotMetrics::SCHEMA_VERSION,
            'darts_thrown' => $dartsThrown,
            'sectors' => $sectors,
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>|null
     */
    public static function normalizeClient(array $raw): ?array
    {
        if (isset($raw['dart_log']) && is_array($raw['dart_log'])) {
            return self::fromDartLog($raw['dart_log'], (int) ($raw['player_id'] ?? 0));
        }

        $darts = (int) ($raw['darts_thrown'] ?? 0);
        if ($darts <= 0) {
            return null;
        }

        $sectors = self::emptySectors();
        if (is_array($raw['sectors'] ?? null)) {
            foreach ($sectors as $key => $_) {
                $row = $raw['sectors'][$key] ?? [];
                $sectors[$key] = [
                    'successes' => (int) (is_array($row) ? ($row['successes'] ?? 0) : 0),
                    'attempts' => (int) (is_array($row) ? ($row['attempts'] ?? 0) : 0),
                ];
            }
        }

        return [
            'schema_version' => CareerSnapshotMetrics::SCHEMA_VERSION,
            'darts_thrown' => $darts,
            'sectors' => $sectors,
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private static function targetIndexFromSnapshot(array $entry, int $playerId): int
    {
        $boards = $entry['boardsSnapshot'] ?? [];
        if (is_array($boards)) {
            $board = $boards[(string) $playerId] ?? $boards[$playerId] ?? null;
            if (is_array($board) && isset($board['targetIndex'])) {
                return (int) $board['targetIndex'];
            }
        }

        return (int) ($entry['targetIndex'] ?? $entry['currentTargetIndex'] ?? 0);
    }

    private static function sectorKey(int $targetIndex): string
    {
        if ($targetIndex >= AroundTheClockRules::LAST_TARGET_INDEX) {
            return 'bull';
        }
        $n = $targetIndex + 1;

        return (string) max(1, min(20, $n));
    }

    /**
     * @return array<string, array{successes: int, attempts: int}>
     */
    public static function emptySectors(): array
    {
        $out = [];
        for ($n = 1; $n <= 20; $n++) {
            $out[(string) $n] = ['successes' => 0, 'attempts' => 0];
        }
        $out['bull'] = ['successes' => 0, 'attempts' => 0];

        return $out;
    }
}
