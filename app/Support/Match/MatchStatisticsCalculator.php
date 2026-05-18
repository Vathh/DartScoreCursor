<?php

namespace App\Support\Match;

use App\Models\Game\GameVisit;
use Illuminate\Support\Collection;

class MatchStatisticsCalculator
{
    /**
     * @param  Collection<int, GameVisit>  $legVisits  wizyty jednego gracza w legu (aktywne)
     */
    public static function legAverage(Collection $legVisits): ?float
    {
        $darts = $legVisits->sum('darts_in_visit');
        if ($darts <= 0) {
            return null;
        }
        $points = $legVisits->where('bust', false)->sum('score');

        return round(($points / $darts) * 3, 2);
    }

    /**
     * @param  Collection<int, GameVisit>  $legVisits
     */
    public static function firstNineAverage(Collection $legVisits): ?float
    {
        $firstThree = $legVisits->where('bust', false)->take(3);
        if ($firstThree->count() < 3) {
            return null;
        }
        $darts = $firstThree->sum('darts_in_visit');
        if ($darts <= 0) {
            return null;
        }
        $points = $firstThree->sum('score');

        return round(($points / $darts) * 3, 2);
    }

    /**
     * @param  Collection<int, GameVisit>  $legVisits
     */
    public static function highestVisit(Collection $legVisits): ?int
    {
        $max = $legVisits->where('bust', false)->max('score');

        return $max !== null ? (int) $max : null;
    }

    /**
     * @param  Collection<int, GameVisit>  $legVisits
     */
    public static function highestFinish(Collection $legVisits): ?int
    {
        $checkout = $legVisits->where('closed_leg', true)->where('bust', false)->first();

        return $checkout ? (int) $checkout->score : null;
    }

    /**
     * @param  Collection<int, GameVisit>  $legVisits
     */
    public static function dartsThrown(Collection $legVisits): int
    {
        return (int) $legVisits->sum('darts_in_visit');
    }

    /**
     * @param  Collection<int, GameVisit>  $legVisits
     */
    public static function checkoutDart(Collection $legVisits): ?int
    {
        $checkout = $legVisits->where('closed_leg', true)->where('bust', false)->first();

        return $checkout ? (int) $checkout->darts_in_visit : null;
    }

    /**
     * @param  Collection<int, GameVisit>  $matchVisits  wszystkie wizyty gracza w meczu
     */
    public static function matchAverage(Collection $matchVisits): ?float
    {
        return self::legAverage($matchVisits);
    }

    /**
     * @param  Collection<int, GameVisit>  $legStats  kolekcja GameLegPlayerStat
     */
    public static function matchDoublePercent(Collection $legStats, int $playerId): ?float
    {
        $rows = $legStats->where('player_id', $playerId)->where('double_tracked', true);
        $attempts = $rows->sum('double_attempts');
        if ($attempts <= 0) {
            return null;
        }
        $successes = $rows->sum('double_successes');

        return round(($successes / $attempts) * 100, 1);
    }
}
