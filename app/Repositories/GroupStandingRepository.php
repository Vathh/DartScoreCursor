<?php

namespace App\Repositories;

use App\Domain\GroupStandingDomain;
use App\Models\GroupStanding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GroupStandingRepository
{
    public function createEmptyStandings(int $tournamentId, array $groups)
    {
        $standingsToInsert = [];

        foreach ($groups as $index => $group) {
            foreach ($group as $playerId) {
                $standingsToInsert[] = [
                    'tournament_id' => $tournamentId,
                    'group_number' => $index + 1,
                    'player_id' => $playerId,
                    'matches_played' => 0,
                    'matches_won' => 0,
                    'matches_lost' => 0,
                    'legs_won' => 0,
                    'legs_lost' => 0,
                    'points' => 0
                ];
            }
        }
        GroupStanding::insert($standingsToInsert);
    }
}
