<?php

namespace App\Repositories;

use App\Models\Tournament;

class TournamentRepository
{
    public function create(
        int $seasonId,
        string  $name,
        ?string $date
    ): void
    {
        Tournament::create([
            'season_id' => $seasonId,
            'name' => $name,
            'date' => $date
        ]);
    }
}
