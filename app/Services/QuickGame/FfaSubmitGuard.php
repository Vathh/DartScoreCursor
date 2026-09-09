<?php

namespace App\Services\QuickGame;

use App\Domain\QuickGame\FfaSubmitRules;
use App\Models\QuickGame\QuickGameFfaSession;
use App\Repositories\Player\PlayerRepository;
use App\Repositories\QuickGame\QuickGameFfaPresenceRepository;

/**
 * Łączy sesję FFA z {@see FfaSubmitRules} (player + left z repozytoriów).
 */
final class FfaSubmitGuard
{
    public function __construct(
        private PlayerRepository $playerRepository,
        private QuickGameFfaPresenceRepository $presenceRepository,
    ) {
    }

    public function assert(QuickGameFfaSession $session, int $userId, ?int $targetPlayerId): void
    {
        $lobby = $session->lobby;
        $player = $this->playerRepository->findByUserId($userId);

        FfaSubmitRules::assert(
            lobbyExists: $lobby !== null,
            scoringMode: (string) $session->scoring_mode,
            hostUserId: (int) ($lobby?->host_id ?? 0),
            actingUserId: $userId,
            submitterPlayerId: $player !== null ? (int) $player->id : null,
            targetPlayerId: $targetPlayerId,
            playerIds: array_map('intval', $session->player_order ?? []),
            leftPlayerIds: $this->presenceRepository->getLeftPlayerIds($session),
        );
    }
}
