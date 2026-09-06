<?php

namespace App\Domain\Career;

use App\Domain\GameScoring\VisitDartPayload;
use App\Domain\QuickGame\Catch40Rules;

final class Catch40CareerCollector
{
    /**
     * @param  list<array<string, mixed>>  $dartLog
     * @param  array<string, mixed>  $finalBoard
     * @return array<string, mixed>|null
     */
    public static function fromDartLog(array $dartLog, int $playerId, array $finalBoard = []): ?array
    {
        $board = Catch40Rules::emptyBoard();
        $dartsThrown = 0;
        $dartRows = [];
        $outs = [];
        $hasDarts = false;

        foreach ($dartLog as $entry) {
            if ((int) ($entry['playerId'] ?? 0) !== $playerId) {
                continue;
            }
            if (($entry['kind'] ?? 'visit') !== 'visit') {
                continue;
            }

            $outBefore = (int) ($board['outNumber'] ?? Catch40Rules::FIRST_OUT);
            $dartsUsedBefore = (int) ($board['dartsUsed'] ?? 0);
            $score = (int) ($entry['score'] ?? 0);
            $remainingAfter = (int) ($entry['remainingAfter'] ?? $board['remaining']);
            $dartsInVisit = (int) ($entry['dartsInVisit'] ?? 3);
            $bust = (bool) ($entry['bust'] ?? false);
            $checkout = (bool) ($entry['checkout'] ?? false);
            $dartsThrown += $dartsInVisit;

            $normalized = VisitDartPayload::normalize($entry['darts'] ?? null);
            if ($normalized !== null && $normalized !== []) {
                $hasDarts = true;
                foreach ($normalized as $dart) {
                    $dartRows[] = $dart;
                }
            }

            try {
                $next = Catch40Rules::applyVisit(
                    $board,
                    $score,
                    $remainingAfter,
                    $dartsInVisit,
                    $bust,
                    $checkout,
                );
            } catch (\DomainException) {
                continue;
            }

            $outChanged = (int) $next['outNumber'] !== $outBefore || ! empty($next['finished']);
            if ($outChanged) {
                $outs[] = [
                    'out' => $outBefore,
                    'darts' => $checkout ? $dartsUsedBefore + $dartsInVisit : null,
                ];
            }

            $board = $next;
        }

        if ($dartsThrown <= 0) {
            return null;
        }

        $score = (int) ($finalBoard['catch40Score'] ?? $board['catch40Score'] ?? 0);
        $doubles = $hasDarts
            ? DoubleOutRules::countFromDarts($dartRows)
            : ['attempts' => 0, 'successes' => 0];

        return [
            'schema_version' => CareerSnapshotMetrics::SCHEMA_VERSION,
            'darts_thrown' => $dartsThrown,
            'score' => $score,
            'double_tracked' => $hasDarts,
            'double_attempts' => $hasDarts ? $doubles['attempts'] : null,
            'double_successes' => $hasDarts ? $doubles['successes'] : null,
            'outs' => $outs,
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
                is_array($raw['board'] ?? null) ? $raw['board'] : [],
            );
        }

        $darts = (int) ($raw['darts_thrown'] ?? 0);
        if ($darts <= 0) {
            return null;
        }

        $tracked = (bool) ($raw['double_tracked'] ?? false);
        $outs = [];
        if (is_array($raw['outs'] ?? null)) {
            foreach ($raw['outs'] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $outs[] = [
                    'out' => (int) ($row['out'] ?? 0),
                    'darts' => array_key_exists('darts', $row) && $row['darts'] !== null
                        ? (int) $row['darts']
                        : null,
                ];
            }
        }

        return [
            'schema_version' => CareerSnapshotMetrics::SCHEMA_VERSION,
            'darts_thrown' => $darts,
            'score' => (int) ($raw['score'] ?? 0),
            'double_tracked' => $tracked,
            'double_attempts' => $tracked ? (int) ($raw['double_attempts'] ?? 0) : null,
            'double_successes' => $tracked ? (int) ($raw['double_successes'] ?? 0) : null,
            'outs' => $outs,
        ];
    }
}
