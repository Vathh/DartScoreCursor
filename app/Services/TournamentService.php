<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Repositories\GameRepository;
use App\Repositories\TournamentRepository;

class TournamentService
{

    public function __construct(
        private TournamentRepository $tournamentRepository,
        private GameRepository $gameRepository
    )
    {
    }

    public function create(
        int $seasonId,
        string  $name,
        ?string $date = null
    ): void
    {
        $this->tournamentRepository->create($seasonId, $name, $date);
    }

    public function createGroups(array $playerIds, int $groupsCount): array
    {
        shuffle($playerIds);

        $result = [];

        for ($i = 0; $i < $groupsCount; $i++) {
            $result[$i%$groupsCount][] = $playerIds[$i];
        }

        return $result;
    }

    public function createGames($tournamentId, array $groups): void
    {
        $gamesToInsert = [];

        foreach($groups as $groupIndex => $group) {
            foreach ($this->generateGamesForGroup($group) as $game) {
                $gamesToInsert[] = [
                    'tournament_id' => $tournamentId,
                    'player1_id' => $game['player1_id'],
                    'player2_id' => $game['player2_id'],
                    'group_number' => $groupIndex + 1,
                    'status' => GameStatus::SCHEDULED,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        $this->gameRepository->createGames($gamesToInsert);
    }

    private function generateGamesForGroup(array $group): array
    {
        $games = [];

        for($i = 0; $i < count($group); $i++) {
            for($j = $i + 1; $j < count($group); $j++) {
                $games[] = ['player1_id' => $group[$i], 'player2_id' => $group[$j]];
            }
        }

        return $games;
    }
}
