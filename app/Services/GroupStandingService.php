<?php

namespace App\Services;

use App\Models\GroupStanding;
use App\Repositories\GameRepository;
use App\Repositories\GroupStandingRepository;
use Illuminate\Support\Facades\DB;

class GroupStandingService
{

    public function __construct(
        private GameRepository $gameRepository,
        private GroupStandingRepository $groupStandingRepository,
    )
    {
    }

    public function updateGroupStandings(int $tournamentId, int $groupNumber): void
    {
        $this->gameRepository->getFinishedGroupGames($tournamentId, $groupNumber);


    }
}
