<?php

namespace App\Domain\Tournament;

use App\Domain\Concerns\AssertsRelationsLoaded;
use App\Domain\PlayerDomain;
use App\Domain\SeasonDomain;
use App\Enums\GameStage;
use App\Models\Tournament\TournamentResult;

class TournamentResultDomain
{
    use AssertsRelationsLoaded;

    /** @var list<string> */
    private const RELATIONS = ['season', 'tournament', 'player'];

    public function __construct(
        public readonly ?SeasonDomain $season,
        public readonly ?int $seasonId,
        public readonly ?TournamentDomain $tournament,
        public readonly ?int $tournamentId,
        public readonly ?PlayerDomain $player,
        public readonly ?int $playerId,
        public readonly ?int $points,
        public readonly ?int $place,
        public readonly ?GameStage $eliminationStage,
    ) {}

    public static function fromEloquent(TournamentResult $result, array $with = []): self
    {
        self::assertRelationsLoaded($result, $with, self::RELATIONS);

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
            place: $result->place,
            eliminationStage: $result->elimination_stage ?: null,
        );
    }
}
