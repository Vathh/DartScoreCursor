<?php

namespace App\Services;

use App\Repositories\GameRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class GameService
{

    public function __construct(
        private GameRepository       $gameRepository,
        private GroupStandingService $groupStandingService,
        private AchievementsService  $achievementsService
    )
    {
    }

    public function setStatusInProgress(int $gameId): void
    {
        $this->gameRepository->setStatusInProgress($gameId);
    }

    public function update(array $game, array $achievements): bool
    {
        try {
            DB::transaction(function () use ($game, $achievements) {
                $this->gameRepository->update($game['id'], $game['player1Score'], $game['player2Score'], $game['winnerId']);
                $this->achievementsService->createMany($achievements);
                $this->groupStandingService->updateGroupStandings($game['tournamentId'], $game['groupNumber']);
            });

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getActiveGames(int $tournamentId): Collection
    {
        return $this->gameRepository->getActiveGames($tournamentId);
    }
}
