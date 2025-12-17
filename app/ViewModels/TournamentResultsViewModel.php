<?php

namespace App\ViewModels;

use App\Domain\GameDomain;
use App\Domain\GroupStandingDomain;
use App\Domain\SeasonDomain;
use App\Domain\TournamentDomain;
use App\Models\Tournament;
use Illuminate\Support\Collection;

class TournamentResultsViewModel
{

    public function __construct(
        public Tournament $tournament
    )
    {
    }

    public function groupStandings()
    {
        return $this->tournament
                    ->groupStandings
                    ->map(fn($standing) => GroupStandingDomain::fromEloquent($standing, ['player']))
                    ->groupBy('groupNumber');
    }

    public function games()
    {
        return $this->tournament
                    ->games->map(fn($game) => GameDomain::fromEloquent($game, ['player1', 'player2', 'winner']))
                    ->groupBy('groupNumber');
    }

    public function groupNumbers(): Collection
    {
        return $this->tournament
                    ->groupStandings
                    ->map(fn($standing) => $standing->groupNumber)
                    ->unique()
                    ->sort()
                    ->collect();
    }

    public function tournament(): TournamentDomain
    {
        return TournamentDomain::fromEloquent($this->tournament);
    }

    public function season(): SeasonDomain
    {
        return SeasonDomain::fromEloquent($this->tournament->season, ['league', 'admins']);
    }

    public function game(int $player1Id, int $player2Id): GameDomain
    {
        return GameDomain::fromEloquent(
            $this->tournament
                ->games
                ->first(fn($game) => in_array($player1Id, [$game->player1->id, $game->player2->id]) && in_array($player2Id, [$game->player1->id, $game->player2->id]))
        );
    }
}
