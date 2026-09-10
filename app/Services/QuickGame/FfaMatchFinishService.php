<?php

namespace App\Services\QuickGame;

use App\Domain\GameScoring\MatchFormat;
use App\DTO\QuickGame\PlayerResultDTO;
use App\Enums\GameStatus;
use App\Models\QuickGame\QuickGameFfaSession;
use App\Repositories\QuickGame\QuickGameLobbyRepository;
use App\Repositories\QuickGame\QuickGameRepository;
use App\Services\Career\PlayerCareerSnapshotService;

/**
 * Zapis wyniku i zamknięcie sesji FFA — wspólne dla X01 i trybów specialty.
 */
final class FfaMatchFinishService
{
    public function __construct(
        private QuickGameRepository $quickGameRepository,
        private QuickGameLobbyRepository $lobbyRepository,
        private PlayerCareerSnapshotService $careerSnapshotService,
    ) {}

    /**
     * @param  list<int>  $playerIds
     * @param  array<int, int>  $legsWon
     * @return list<array{playerId: int, score: int}>
     */
    public function rankedByLegsWon(array $playerIds, array $legsWon): array
    {
        return collect($playerIds)
            ->map(fn ($pid) => ['playerId' => (int) $pid, 'score' => (int) ($legsWon[$pid] ?? 0)])
            ->sortByDesc('score')
            ->values()
            ->all();
    }

    /**
     * @param  list<PlayerResultDTO>  $results
     * @param  array<int, int>  $legsWon
     * @param  array<string, mixed>|null  $gameState
     */
    public function persist(
        QuickGameFfaSession $session,
        MatchFormat $format,
        array $results,
        array $legsWon,
        ?array $gameState = null,
    ): int {
        $playerIds = array_map('intval', $session->player_order ?? []);
        $quickGameId = $this->quickGameRepository->createWithResults($playerIds, $session->lobby_id);
        $this->quickGameRepository->saveResults($quickGameId, $results);

        $winnerId = $results[0]->playerId ?? null;
        $p1 = $playerIds[0] ?? null;
        $p2 = $playerIds[1] ?? null;

        $this->quickGameRepository->updateResultFields($quickGameId, array_merge(
            [
                'player1_score' => (int) ($legsWon[$p1] ?? 0),
                'player2_score' => (int) ($legsWon[$p2] ?? 0),
                'winner_id' => $winnerId,
                'status' => GameStatus::FINISHED,
            ],
            $format->toDatabaseColumns(),
        ));

        $session->status = QuickGameFfaSession::STATUS_FINISHED;
        $session->quick_game_id = $quickGameId;
        $session->finished_at = now();

        $session->loadMissing('lobby');
        $lobby = $session->lobby;
        if ($lobby !== null) {
            $this->lobbyRepository->markFinished($lobby->id, $quickGameId);
        }

        $this->careerSnapshotService->recordFinishedQuickGame($quickGameId, $session, $gameState);

        return $quickGameId;
    }
}
