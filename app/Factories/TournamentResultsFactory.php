<?php

namespace App\Factories;

use Illuminate\Support\Collection;

class TournamentResultsFactory
{
    public function createForGroups(Collection $groupStandings): Collection
    {
        $groupStandings->map(function ($group) {})
    }
}
