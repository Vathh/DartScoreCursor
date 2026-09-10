<?php

namespace App\Support\QuickGameFfa;

use App\Events\QuickGameFfaStateUpdated;

/**
 * Jedna emisja ffa.state.updated. Payload buduje serwis trybu / StateBuilder.
 * Pole `you` jest widoczne tylko w odpowiedzi HTTP — na kanale WS każdy
 * klient ma inną kolejkę, więc spersonalizowane canInput nie może iść w evencie.
 */
final class FfaStateBroadcaster
{
    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public static function emit(int $lobbyId, array $state): array
    {
        $forChannel = $state;
        unset($forChannel['you']);
        broadcast(new QuickGameFfaStateUpdated($lobbyId, $forChannel));

        return $state;
    }
}
