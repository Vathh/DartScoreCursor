<?php

namespace App\Domain\Tournament;

use App\Domain\PlayerDomain;
use App\Domain\SeasonDomain;
use App\Enums\EliminationStage;
use App\Models\TournamentResult;

class TournamentResultDomain
{

    public function __construct(
        public readonly ?SeasonDomain $season,
        public readonly ?int $seasonId,
        public readonly ?TournamentDomain $tournament,
        public readonly ?int $tournamentId,
        public readonly ?PlayerDomain $player,
        public readonly ?int $playerId,
        public readonly int $points,
        public readonly ?int $place,
        public readonly ?EliminationStage $eliminationStage,
    )
    {
    }

    /**
     * @param TournamentResult $result
     * @param array $with
     * @return self
     */
    public function fromEloquent(TournamentResult $result, array $with = []): self
    {
        $result->loadMissing(array_intersect($with, ['season', 'tournament', 'player']));

        return new self(
            season: in_array('season', $with)
                    ? SeasonDomain::fromEloquent($result->season)
                    : null,
            seasonId: $result->season_id,
            tournament: in_array('tournament', $with)
                    ? TournamentDomain::fromEloquent($result->tournament)
                    : null,
            tournamentId: $result->tournament_id,
            player: in_array('player', $with)
                    ? PlayerDomain::fromEloquent($result->player)
                    : null,
            playerId: $result->player_id,
            points: $result->points,
            place: $result->place ?: null,
            eliminationStage: $result->elimination_stage ?: null,
        );
    }
}
