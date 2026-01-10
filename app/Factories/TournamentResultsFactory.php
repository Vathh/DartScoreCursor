<?php

namespace App\Factories;

use App\Domain\GroupStandingDomain;
use Illuminate\Support\Collection;

class TournamentResultsFactory
{
    /**
     * @param Collection<GroupStandingDomain> $groupStandings
     * @return Collection
     */
    public function createForGroups(Collection $groupStandings): Collection
    {
        $groupStandings->map(function ($group) {})
    }
}
