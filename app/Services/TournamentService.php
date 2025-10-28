<?php

namespace App\Services;

use App\Repositories\TournamentRepository;

class TournamentService
{

    public function __construct(private TournamentRepository $tournamentRepository)
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
}
