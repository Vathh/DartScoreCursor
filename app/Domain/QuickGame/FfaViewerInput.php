<?php

namespace App\Domain\QuickGame;

/**
 * Kto widzi się jako strzelec w payloadzie GET/WS (`you.canInput` / `myPlayerIndex`).
 */
final class FfaViewerInput
{
    /**
     * @param  list<int>  $playerIds
     * @return array{canInput: bool, myPlayerIndex: int|null}
     */
    public static function resolve(
        string $scoringMode,
        bool $sessionInProgress,
        ?int $hostUserId,
        int $actingUserId,
        ?int $viewerPlayerId,
        array $playerIds,
        int $currentPlayerIndex,
    ): array {
        if ($scoringMode === 'one_device') {
            $isHost = $hostUserId !== null && $hostUserId === $actingUserId;

            return [
                'canInput' => $isHost && $sessionInProgress,
                'myPlayerIndex' => $isHost ? $currentPlayerIndex : null,
            ];
        }

        if ($viewerPlayerId === null) {
            return ['canInput' => false, 'myPlayerIndex' => null];
        }

        $idx = array_search($viewerPlayerId, $playerIds, true);
        if ($idx === false) {
            return ['canInput' => false, 'myPlayerIndex' => null];
        }

        $myPlayerIndex = (int) $idx;

        return [
            'canInput' => $myPlayerIndex === $currentPlayerIndex && $sessionInProgress,
            'myPlayerIndex' => $myPlayerIndex,
        ];
    }
}
