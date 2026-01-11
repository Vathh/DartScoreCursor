<?php /** @noinspection PhpParamsInspection */

namespace App\Factories;

use App\Domain\GroupStandingDomain;
use App\Domain\Tournament\TournamentResultDomain;
use App\Enums\EliminationStage;
use Illuminate\Support\Collection;

class TournamentResultsFactory
{
    /**
     * @param Collection<GroupStandingDomain> $groupStandings
     * @return Collection
     */
    public function createForGroups(Collection $groupStandings, Collection $pointSchemeRules): Collection
    {
        return $groupStandings->map(function ($standing) use ($pointSchemeRules) {
                    return new TournamentResultDomain(
                        tournamentId: $standing->tournament->id,
                        playerId: $standing->player->id,
                        points: $pointSchemeRules->where('elimination_stage', EliminationStage::GROUP->value)
                                                    ->where('place', $standing->place)
                                                    ->points,
                        place: $standing->place,
                        eliminationStage: EliminationStage::GROUP->value,
                    );
                });
    }
}
