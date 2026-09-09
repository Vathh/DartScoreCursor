<?php

namespace App\Support\QuickGameFfa;

use App\Events\QuickGameFfaStateUpdated;

/**
 * Jedna emisja ffa.state.updated. Payload buduje serwis trybu / StateBuilder.
 */
final class FfaStateBroadcaster
{
    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public static function emit(int $lobbyId, array $state): array
    {
        broadcast(new QuickGameFfaStateUpdated($lobbyId, $state));

        return $state;
    }
}
