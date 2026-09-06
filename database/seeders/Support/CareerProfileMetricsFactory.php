<?php

namespace Database\Seeders\Support;

use App\Domain\Career\CareerCollectorRegistry;
use App\Domain\Career\X01CareerCollector;
use App\Domain\GameScoring\MatchFormat;

/**
 * Deterministyczne metryki kariery (schema v2) do seeda profilu.
 */
final class CareerProfileMetricsFactory
{
    /**
     * @return array<string, mixed>
     */
    public static function forGameType(string $gameType, int $seed, float $form, bool $won = false): array
    {
        $metrics = match ($gameType) {
            MatchFormat::GAME_TYPE_CRICKET => self::cricket($seed, $won),
            MatchFormat::GAME_TYPE_BOB27 => self::bob27($seed, $form),
            MatchFormat::GAME_TYPE_ATC => self::atc($seed, $form),
            MatchFormat::GAME_TYPE_CATCH40 => self::catch40($seed, $form),
            MatchFormat::GAME_TYPE_CRICKET56 => self::cricket56($seed, $form),
            default => self::x01($seed, $form, $won),
        };

        $normalized = CareerCollectorRegistry::normalizeClient($gameType, $metrics);
        if ($normalized === null) {
            throw new \RuntimeException("Nie udało się znormalizować metryk {$gameType} (seed {$seed}).");
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public static function x01(int $seed, float $form, bool $won): array
    {
        $h = self::u($seed);
        $average = 49.0 + (16.0 * $form) + ((($h % 900) / 100) - 4.5);
        $average = max(38.0, min(78.0, $average));
        $legs = $won ? 2 : 1 + ($h % 2);
        $darts = (15 + ($h % 8)) * $legs + (($h >> 3) % 6);
        $points = (int) round(($average / 3) * $darts);
        $doublePct = 0.20 + (0.16 * $form) + ((($h >> 8) % 12) / 100);
        $attempts = 4 + ($h % 10) + $legs;
        $successes = max(1, min($attempts, (int) round($attempts * $doublePct)));

        $buckets = ['180' => 0, '170' => 0, '140' => 0, '100' => 0, '80' => 0, '60' => 0];
        $visits = (int) ceil($darts / 3);
        $high = (int) round($visits * (0.04 + 0.06 * $form));
        $buckets['180'] = ($h % 17) === 0 ? 1 : 0;
        $buckets['170'] = ($h % 23) === 0 ? 1 : 0;
        $buckets['140'] = (int) floor($high * 0.35);
        $buckets['100'] = (int) floor($high * 0.9) + ($h % 3);
        $buckets['80'] = 1 + ($h % 4);
        $buckets['60'] = 2 + ($h % 5);

        $closed = [];
        $checkouts = [];
        for ($i = 0; $i < $legs; $i++) {
            $legDarts = 15 + (($h >> ($i + 2)) % 10);
            $closed[] = ['darts' => $legDarts];
            $checkout = [32, 40, 36, 16, 50, 24, 60, 80][($h + $i) % 8];
            $checkouts[] = ['score' => $checkout, 'darts' => 1 + (($h + $i) % 3)];
        }

        $best = min(array_column($closed, 'darts'));

        return [
            'darts_thrown' => $darts,
            'points' => $points,
            'double_tracked' => true,
            'double_attempts' => $attempts,
            'double_successes' => $successes,
            'visit_scores' => $buckets,
            'closed_legs' => $closed,
            'checkouts' => $checkouts,
            'best_leg_darts' => $best,
            'sectors' => self::x01Sectors($h, $darts),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function cricket(int $seed, bool $won): array
    {
        $h = self::u($seed);
        $darts = 24 + ($h % 30);
        $marks = [];
        $points = [];
        $hits = [];
        foreach ([15, 16, 17, 18, 19, 20, 'bull'] as $key) {
            $k = (string) $key;
            $hits[$k] = 2 + (self::u($seed.$k) % 6);
            $marks[$k] = $hits[$k] + (self::u($seed.'m'.$k) % 4);
            $points[$k] = (self::u($seed.'p'.$k) % 5) === 0 ? (int) $key * (self::u($seed.'p'.$k) % 3) : 0;
        }

        return [
            'darts_thrown' => $darts,
            'marks' => $marks,
            'points' => $points,
            'hits' => $hits,
            'win_darts' => $won ? $darts : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function bob27(int $seed, float $form): array
    {
        $h = self::u($seed);
        $finished = ($h % 5) !== 0;
        $attempts = $finished ? 60 + ($h % 12) : 18 + ($h % 36);
        $pct = 0.28 + (0.18 * $form);
        $successes = max(1, min($attempts, (int) round($attempts * $pct)));
        $ended = $finished ? null : 'D'.(4 + ($h % 16));
        $score = $finished ? (20 + ($h % 90)) : max(0, 27 - (3 * (1 + ($h % 8))));

        return [
            'darts_thrown' => $attempts,
            'double_attempts' => $attempts,
            'double_successes' => $successes,
            'bob27_mode' => ($h % 4) === 0 ? MatchFormat::BOB27_MODE_EASY : MatchFormat::BOB27_MODE_HARD,
            'bob27_bull' => ($h % 3) === 0 ? MatchFormat::BOB27_BULL_WITHOUT : MatchFormat::BOB27_BULL_WITH,
            'score' => $score,
            'finished' => $finished,
            'ended_at_target' => $ended,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function atc(int $seed, float $form): array
    {
        $h = self::u($seed);
        $sectors = [];
        $darts = 0;
        $hitRate = 0.42 + (0.22 * $form);
        for ($n = 1; $n <= 20; $n++) {
            $attempts = 1 + (self::u($seed.'t'.$n) % 4);
            $successes = max(1, min($attempts, (int) round($attempts * $hitRate)));
            $sectors[(string) $n] = ['successes' => $successes, 'attempts' => $attempts];
            $darts += $attempts;
        }
        $bullAttempts = 1 + ($h % 3);
        $sectors['bull'] = [
            'successes' => ($h % 3) === 0 ? 1 : 0,
            'attempts' => $bullAttempts,
        ];
        $darts += $bullAttempts;

        return [
            'darts_thrown' => $darts,
            'sectors' => $sectors,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function catch40(int $seed, float $form): array
    {
        $h = self::u($seed);
        $cleared = 8 + (int) round(18 * $form) + ($h % 6);
        $cleared = max(4, min(40, $cleared));
        $outs = [];
        for ($i = 0; $i < $cleared; $i++) {
            $out = 61 + $i;
            $fail = ($i === $cleared - 1) && ($h % 3) === 0;
            $outs[] = [
                'out' => $out,
                'darts' => $fail ? null : 1 + (($h + $i) % 6),
            ];
        }
        $darts = 18 + $cleared * 3 + ($h % 12);
        $attempts = $cleared + 3 + ($h % 8);
        $successes = max(1, $cleared - (($h % 4) === 0 ? 1 : 0));

        return [
            'darts_thrown' => $darts,
            'score' => min(120, $cleared * 3),
            'double_tracked' => true,
            'double_attempts' => $attempts,
            'double_successes' => min($successes, $attempts),
            'outs' => $outs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function cricket56(int $seed, float $form): array
    {
        $h = self::u($seed);
        $score = 18 + (int) round(32 * $form) + ($h % 8);
        $sectors = [];
        foreach ([15, 16, 17, 18, 19, 20, 'bull'] as $key) {
            $hh = self::u($seed.(string) $key);
            $sectors[(string) $key] = [
                'S' => $hh % 3,
                'D' => ($hh >> 2) % 3,
                'T' => ($hh >> 4) % 2,
            ];
        }

        return [
            'score' => min(60, $score),
            'sectors' => $sectors,
        ];
    }

    /**
     * @return array<string, int>
     */
    private static function x01Sectors(int $h, int $darts): array
    {
        $sectors = X01CareerCollector::emptySectors();
        $left = $darts;
        $weights = [20 => 18, 19 => 12, 18 => 10, 1 => 8, 5 => 7, 17 => 6, 16 => 5, 12 => 4, 25 => 3, 0 => 9];
        $i = 0;
        foreach ($weights as $sector => $weight) {
            $count = (int) round($darts * ($weight / 82));
            $key = (string) $sector;
            if (! array_key_exists($key, $sectors)) {
                continue;
            }
            $sectors[$key] = min($left, max(0, $count + (($h >> ($i % 8)) % 3) - 1));
            $left -= $sectors[$key];
            $i++;
        }
        if ($left > 0) {
            $sectors['20'] += $left;
        }

        return $sectors;
    }

    private static function u(int|string $seed): int
    {
        return (int) sprintf('%u', crc32((string) $seed));
    }
}
