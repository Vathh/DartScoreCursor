<?php

namespace App\Domain\Career;

use App\Domain\QuickGame\Cricket56Rules;

final class Cricket56CareerCollector
{
    /**
     * @param  list<array<string, mixed>>  $dartLog
     * @param  array<string, mixed>  $finalBoard
     * @return array<string, mixed>|null
     */
    public static function fromDartLog(array $dartLog, int $playerId, array $finalBoard = []): ?array
    {
        $sectors = self::emptySectors();
        $hasMarks = false;

        foreach ($dartLog as $entry) {
            if ((int) ($entry['playerId'] ?? 0) !== $playerId) {
                continue;
            }
            if (($entry['kind'] ?? 'visit') !== 'visit') {
                continue;
            }
            $roundIndex = (int) ($entry['currentRoundIndex'] ?? 0);
            $target = Cricket56Rules::targetAt($roundIndex);
            $key = $target === 'bull' ? 'bull' : (string) $target;
            $marks = $entry['marks'] ?? null;
            if (! is_array($marks) || $marks === []) {
                continue;
            }
            $hasMarks = true;
            foreach (array_slice($marks, 0, 3) as $mark) {
                $n = Cricket56Rules::clampMark((int) $mark, $roundIndex);
                if ($n === 1) {
                    $sectors[$key]['S']++;
                } elseif ($n === 2) {
                    $sectors[$key]['D']++;
                } elseif ($n === 3) {
                    $sectors[$key]['T']++;
                }
            }
        }

        $score = (int) ($finalBoard['score'] ?? 0);
        if ($score <= 0 && ! $hasMarks) {
            return null;
        }

        return [
            'schema_version' => CareerSnapshotMetrics::SCHEMA_VERSION,
            'score' => $score,
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
            return self::fromDartLog(
                $raw['dart_log'],
                (int) ($raw['player_id'] ?? 0),
                is_array($raw['board'] ?? null) ? $raw['board'] : ['score' => $raw['score'] ?? 0],
            );
        }

        $score = (int) ($raw['score'] ?? 0);
        $sectors = self::emptySectors();
        $hasData = $score > 0;
        if (is_array($raw['sectors'] ?? null)) {
            foreach ($sectors as $key => $_) {
                $row = $raw['sectors'][$key] ?? [];
                if (! is_array($row)) {
                    continue;
                }
                $sectors[$key] = [
                    'S' => (int) ($row['S'] ?? $row['s'] ?? 0),
                    'D' => (int) ($row['D'] ?? $row['d'] ?? 0),
                    'T' => (int) ($row['T'] ?? $row['t'] ?? 0),
                ];
                if ($sectors[$key]['S'] + $sectors[$key]['D'] + $sectors[$key]['T'] > 0) {
                    $hasData = true;
                }
            }
        }

        if (! $hasData) {
            return null;
        }

        return [
            'schema_version' => CareerSnapshotMetrics::SCHEMA_VERSION,
            'score' => $score,
            'sectors' => $sectors,
        ];
    }

    /**
     * @return array<string, array{S: int, D: int, T: int}>
     */
    public static function emptySectors(): array
    {
        $out = [];
        foreach ([15, 16, 17, 18, 19, 20, 'bull'] as $key) {
            $out[(string) $key] = ['S' => 0, 'D' => 0, 'T' => 0];
        }

        return $out;
    }
}
