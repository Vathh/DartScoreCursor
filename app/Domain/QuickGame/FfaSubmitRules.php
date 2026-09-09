<?php

namespace App\Domain\QuickGame;

use DomainException;

/**
 * Kto może wpisać rzut / cofnąć wizytę w FFA (one_device vs each_own, left).
 * Czyste reguły — bez Eloquent. Ten sam kontrakt dla X01 i trybów specialty.
 */
final class FfaSubmitRules
{
    /**
     * @param  list<int>  $playerIds
     * @param  list<int>  $leftPlayerIds
     */
    public static function assert(
        bool $lobbyExists,
        string $scoringMode,
        int $hostUserId,
        int $actingUserId,
        ?int $submitterPlayerId,
        ?int $targetPlayerId,
        array $playerIds,
        array $leftPlayerIds,
    ): void {
        if (! $lobbyExists) {
            throw new DomainException('Lobby nie istnieje.');
        }

        if ($targetPlayerId !== null && in_array($targetPlayerId, $leftPlayerIds, true)) {
            throw new DomainException('Ten gracz opuścił mecz.');
        }

        if ($scoringMode === 'one_device') {
            if ($hostUserId !== $actingUserId) {
                throw new DomainException('W trybie jednego urządzenia punkty wpisuje tylko host.');
            }

            return;
        }

        if ($submitterPlayerId === null) {
            throw new DomainException('Nie znaleziono gracza.');
        }

        if (in_array($submitterPlayerId, $leftPlayerIds, true)) {
            throw new DomainException('Opuszczono ten mecz — nie możesz wpisywać rzutów.');
        }

        if ($targetPlayerId === null) {
            if (! in_array($submitterPlayerId, $playerIds, true)) {
                throw new DomainException('Nie jesteś uczestnikiem tego meczu.');
            }

            return;
        }

        if ($submitterPlayerId !== $targetPlayerId) {
            throw new DomainException('Możesz wpisywać tylko własne rzuty.');
        }
    }
}
