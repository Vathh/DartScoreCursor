<?php

namespace App\Domain\Career;

/**
 * Próg „czy jest paliwo pod radę”. Nie sędziuje jakości gry — tylko czy digest ma co powiedzieć.
 */
final class CoachReadiness
{
    public const MIN_GAMES = 3;

    public const MIN_DOUBLE_ATTEMPTS = 9;

    public const MIN_SECTOR_ATTEMPTS = 6;

    public const WEAK_DOUBLE_PCT = 40.0;

    public const DROPPING_DOUBLE_DELTA = -3.0;

    public const DROPPING_X01_DELTA = -2.0;

    public const FLAT_X01_ABS = 1.0;

    /**
     * @return array{ready: bool, reasons: list<string>}
     */
    public static function evaluate(int $games, bool $hasX01, bool $hasDoubles, int $doubleAttempts): array
    {
        $reasons = [];
        $enoughVolume = $games >= self::MIN_GAMES
            || ($hasDoubles && $doubleAttempts >= self::MIN_DOUBLE_ATTEMPTS);
        if (! $enoughVolume) {
            $reasons[] = 'too_few_games';
        }
        if (! $hasX01 && ! $hasDoubles) {
            $reasons[] = 'no_tracked_metrics';
        }

        return [
            'ready' => $reasons === [],
            'reasons' => $reasons,
        ];
    }
}
