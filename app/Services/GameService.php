<?php

namespace App\Services;

use App\Repositories\GameRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

class GameService
{

    public function __construct(
        private GameRepository $gameRepository,
        private GroupStandingService $groupStandingService
    )
    {
    }

    public function setStatusInProgress(int $gameId): void
    {
        $this->gameRepository->setStatusInProgress($gameId);
    }

    public function update(int $gameId, int $tournamentId, int $player1Score, int $player2Score, int $winnerId, int $groupNumber): bool
    {
        try{
            DB::transaction(function () use ($gameId, $tournamentId, $player1Score, $player2Score, $winnerId, $groupNumber) {
                $this->gameRepository->update($gameId, $player1Score, $player2Score, $winnerId);
                $this->groupStandingService->updateGroupStandings($tournamentId, $groupNumber);
            });

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
