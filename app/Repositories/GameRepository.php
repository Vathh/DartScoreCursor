<?php

namespace App\Repositories;

use App\Enums\GameStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GameRepository
{
    /**
     * @throws \Throwable
     */
    public function createGames(array $games): void
    {
        DB::table('games')->insert($games);
    }

    public function update(int $gameId, int $player1Score, int $player2Score, int $winnerId): void
    {
        DB::table('games')
            ->where('id', $gameId)
            ->update([
                'player1_score' => $player1Score,
                'player2_score' => $player2Score,
                'winner_id' => $winnerId,
                'status' => GameStatus::FINISHED
            ]);
    }

    public function setStatusInProgress(int $gameId): void
    {
        DB::table('games')
            ->where('id', $gameId)
            ->update([
                'status' => GameStatus::IN_PROGRESS
            ]);
    }

    public function getFinishedGroupGames(int $tournamentId, int $groupNumber): Collection
    {
        return DB::table('games')
            ->where('tournament_id', $tournamentId)
            ->where('group_number', $groupNumber)
            ->where('status', GameStatus::FINISHED)
            ->get();
    }
}
