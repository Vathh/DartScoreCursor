<?php

namespace App\Domain\Badge;

use App\Domain\GameScoring\MatchFormat;
use App\Enums\GameKind;

final class GameEligibility
{
    private const CHECKOUT_STARTING_SCORE = 501;

    public static function allowsCheckoutBadges(FinishedGame $game): bool
    {
        if (! in_array($game->kind, [GameKind::GROUP, GameKind::PLAYOFF, GameKind::LEAGUE], true)) {
            return false;
        }

        return $game->gameType === MatchFormat::DEFAULT_GAME_TYPE
            && $game->startingScore === self::CHECKOUT_STARTING_SCORE;
    }

    public static function isRegistered(FinishedGame $game, int $playerId): bool
    {
        return in_array($playerId, $game->registeredPlayerIds, true);
    }
}
