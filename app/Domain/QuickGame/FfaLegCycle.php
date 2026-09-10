<?php

namespace App\Domain\QuickGame;

use App\Models\QuickGame\QuickGameFfaSession;

/**
 * Rotacja openera i numeru lega po zamknięciu lega FFA.
 */
final class FfaLegCycle
{
    /**
     * @param  list<int>  $playerIds
     * @param  list<int>  $leftPlayerIds
     */
    public static function startNextLeg(
        QuickGameFfaSession $session,
        array $playerIds,
        array $leftPlayerIds,
        ?array $currentPlayerSkipIds = null,
    ): void {
        $session->leg_opener_index = FfaTurnRotationDomain::nextIndexAfter(
            (int) $session->leg_opener_index,
            $playerIds,
            $leftPlayerIds,
        );
        $skip = $currentPlayerSkipIds ?? $leftPlayerIds;
        $session->current_player_index = FfaTurnRotationDomain::normalizeIndexAt(
            (int) $session->leg_opener_index,
            $playerIds,
            $skip,
        );
        $session->current_leg_number = (int) $session->current_leg_number + 1;
    }
}
