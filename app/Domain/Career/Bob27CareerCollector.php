<?php

namespace App\Domain\Career;

use App\Domain\GameScoring\MatchFormat;
use App\Domain\QuickGame\Bob27Rules;

final class Bob27CareerCollector
{
    /**
     * @param  list<array<string, mixed>>  $dartLog
     * @param  array<string, mixed>  $board
     * @return array<string, mixed>|null
     */
    public static function fromDartLog(
        array $dartLog,
        int $playerId,
        array $board,
        string $mode,
        bool $includeBull,
    ): ?array {
        $base = CareerSnapshotMetrics::fromBob27DartLog($dartLog, $playerId);
        if ($base === null) {
            return null;
        }

        $eliminated = (bool) ($board['eliminated'] ?? false);
        $score = (int) ($board['score'] ?? 0);
        $lastIndex = self::lastTargetIndex($dartLog, $playerId);
        $lastAllowed = Bob27Rules::lastTargetIndex($includeBull);
        $finished = ! $eliminated && $lastIndex !== null && $lastIndex >= $lastAllowed;

        $base['bob27_mode'] = Bob27Rules::normalizeMode($mode);
        $base['bob27_bull'] = $includeBull
            ? MatchFormat::BOB27_BULL_WITH
            : MatchFormat::BOB27_BULL_WITHOUT;
        $base['score'] = $score;
        $base['finished'] = $finished;
        $base['ended_at_target'] = $finished || $lastIndex === null
            ? null
            : Bob27Rules::targetLabel($lastIndex, $includeBull);

        return $base;
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
                is_array($raw['board'] ?? null) ? $raw['board'] : [],
                (string) ($raw['bob27_mode'] ?? $raw['mode'] ?? Bob27Rules::MODE_HARD),
                ($raw['bob27_bull'] ?? 'with') !== MatchFormat::BOB27_BULL_WITHOUT
                    && ($raw['includeBull'] ?? true),
            );
        }

        $attempts = (int) ($raw['double_attempts'] ?? 0);
        $successes = (int) ($raw['double_successes'] ?? 0);
        if ($attempts <= 0 && (int) ($raw['darts_thrown'] ?? 0) <= 0) {
            return null;
        }

        $extra = [
            'bob27_mode' => Bob27Rules::normalizeMode((string) ($raw['bob27_mode'] ?? Bob27Rules::MODE_HARD)),
            'bob27_bull' => MatchFormat::normalizeBob27Bull((string) ($raw['bob27_bull'] ?? MatchFormat::BOB27_BULL_WITH)),
            'score' => (int) ($raw['score'] ?? 0),
            'finished' => (bool) ($raw['finished'] ?? false),
            'ended_at_target' => isset($raw['ended_at_target']) ? $raw['ended_at_target'] : null,
        ];
        if (is_array($raw['per_double'] ?? null)) {
            $extra['per_double'] = $raw['per_double'];
        }

        return CareerSnapshotMetrics::pack(
            average: null,
            dartsThrown: (int) ($raw['darts_thrown'] ?? $attempts),
            points: 0,
            doubleTracked: true,
            doubleAttempts: $attempts,
            doubleSuccesses: $successes,
            extra: $extra,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $dartLog
     */
    private static function lastTargetIndex(array $dartLog, int $playerId): ?int
    {
        $last = null;
        foreach ($dartLog as $entry) {
            if ((int) ($entry['playerId'] ?? 0) !== $playerId) {
                continue;
            }
            if (! array_key_exists('currentTargetIndex', $entry)) {
                continue;
            }
            $last = (int) $entry['currentTargetIndex'];
        }

        return $last;
    }
}
