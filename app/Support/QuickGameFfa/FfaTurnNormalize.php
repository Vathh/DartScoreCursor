<?php

namespace App\Support\QuickGameFfa;

use App\Domain\QuickGame\FfaTurnRotationDomain;
use App\Models\QuickGame\QuickGameFfaSession;

/**
 * Zapisuje na sesji znormalizowane indeksy tury/openera (skip = left i/lub wyeliminowani).
 */
final class FfaTurnNormalize
{
    /**
     * @param  list<int>  $playerIds
     * @param  list<int>  $skipIds
     */
    public static function apply(QuickGameFfaSession $session, array $playerIds, array $skipIds): void
    {
        [$session->current_player_index, $session->leg_opener_index] = FfaTurnRotationDomain::normalizeTurnPair(
            (int) $session->current_player_index,
            (int) $session->leg_opener_index,
            $playerIds,
            $skipIds,
        );
    }
}
