<?php

namespace App\Domain\Career;

use App\Domain\GameScoring\MatchFormat;

final class CareerCollectorRegistry
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>|null
     */
    public static function normalizeClient(string $gameType, array $raw): ?array
    {
        $type = MatchFormat::normalizeGameType($gameType);

        return match ($type) {
            MatchFormat::GAME_TYPE_CRICKET => CricketCareerCollector::normalizeClient($raw),
            MatchFormat::GAME_TYPE_BOB27 => Bob27CareerCollector::normalizeClient($raw),
            MatchFormat::GAME_TYPE_ATC => AtcCareerCollector::normalizeClient($raw),
            MatchFormat::GAME_TYPE_CATCH40 => Catch40CareerCollector::normalizeClient($raw),
            MatchFormat::GAME_TYPE_CRICKET56 => Cricket56CareerCollector::normalizeClient($raw),
            default => X01CareerCollector::normalizeClient($raw),
        };
    }
}
