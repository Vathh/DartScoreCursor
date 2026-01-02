<?php

namespace App\Repositories;

use App\Domain\PlayoffGameDomain;
use App\Enums\GameStatus;
use App\Models\PlayoffGame;
use Illuminate\Support\Collection;

class PlayoffGameRepository
{
    /**
     * @param Collection<PlayoffGameDomain> $games
     * @return void
     */
    public function createMany(Collection $games): void
    {
        foreach ($games as $game) {
            PlayoffGame::create([
                'tournament_id' => $game->tournamentId,
                'round' => $game->round,
                'slot' => $game->slot,
                'player1_id' => $game->player1Id ?: null,
                'player2_id' => $game->player2Id ?: null,
                'winner_destination_slot' => $game->winnerDestinationSlot ?: null,
            ]);
        }
    }

    public function getActive(int $tournamentId): Collection
    {
        return PlayoffGame::where('tournament_id', $tournamentId)
                            ->where('status', GameStatus::SCHEDULED)
                            ->get()
                            ->map(fn($game) => PlayoffGameDomain::fromEloquent($game, ['tournament', 'player1', 'player2']));
    }
}
