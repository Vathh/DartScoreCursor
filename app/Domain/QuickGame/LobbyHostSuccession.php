<?php

namespace App\Domain\QuickGame;

/**
 * Kto przejmuje waiting lobby po wyjściu hosta.
 * Hostem może być tylko zarejestrowany gracz (konto). Goście nie dziedziczą.
 */
final class LobbyHostSuccession
{
    /**
     * @param  list<array{userId: int|null, isRegistered: bool}>  $remainingPlayers
     */
    public static function nextHostUserId(array $remainingPlayers, int $leavingUserId): ?int
    {
        foreach ($remainingPlayers as $player) {
            if (! ($player['isRegistered'] ?? false)) {
                continue;
            }
            $userId = $player['userId'] ?? null;
            if ($userId === null || (int) $userId === $leavingUserId) {
                continue;
            }

            return (int) $userId;
        }

        return null;
    }
}
