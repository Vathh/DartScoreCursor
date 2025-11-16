<?php

namespace App\Services;

use App\Domain\GameDomain;
use App\Domain\GroupStandingDomain;
use App\Models\GroupStanding;
use App\Repositories\GameRepository;
use App\Repositories\GroupStandingRepository;
use Illuminate\Support\Collection;

class GroupStandingService
{

    public function __construct(
        private GameRepository          $gameRepository,
        private GroupStandingRepository $groupStandingRepository,
    )
    {
    }

    public function updateGroupStandings(int $tournamentId, int $groupNumber): void
    {
        $finishedGames = $this->gameRepository->getFinishedGroupGames($tournamentId, $groupNumber);
        $groupStandings = $this->groupStandingRepository->getStandingsByGroupNumberAndTournamentId($tournamentId, $groupNumber);


    }


    /**
     * @param Collection<int, GroupStandingDomain> $groupStandings
     * @param Collection<int, GameDomain> $finishedGames
     * @return Collection<int, GroupStandingDomain>
     */
    public function sortStandings(Collection $groupStandings, Collection $finishedGames): Collection
    {
        $sortedStandings = $groupStandings->sortByDesc(function ($standing) {
                                                return [$standing->points, $standing->legs_difference];
                                            })->values()
                                            ->map(fn ($standing, $index) => $standing->withPlace($index + 1));

        $sortedStandingsGroupedByPointsAndLegsDifference = $sortedStandings->groupBy(function ($standing) {
            return $standing->points . '-' . $standing->legs_difference;
        });

        $result = collect();

        foreach ($sortedStandingsGroupedByPointsAndLegsDifference as $group) {
            if ($group->count() === 1) {
                $result->push($group->first());
                continue;
            }

            $sortedGroupStandings = $this->compareByDirectGame($group, $finishedGames);

            foreach ($sortedGroupStandings as $standing) {
                $result->push($standing);
            }
        }

        return $result;
    }


    /**
     * @param Collection<int, GroupStandingDomain> $standingsToCompare
     * @param Collection<int, GameDomain> $finishedGames
     * @return Collection<int, GroupStandingDomain>
     */
    public function compareByDirectGame(Collection $standingsToCompare, Collection $finishedGames): Collection
    {
        $playerIds = $standingsToCompare->map(fn($standing) => $standing->player->id)->toArray();

        $directGames = $finishedGames->filter(function ($game) use ($playerIds) {
            return in_array($game->player1->id, $playerIds) && in_array($game->player2->id, $playerIds);
        })->values();

        $result = collect();

        $everyPlayerWinsCount = $standingsToCompare->mapWithKeys(function ($standing) use ($directGames) {
            $playerId = $standing->player->id;

            $wins = $directGames
                        ->where('winner.id', $playerId)
                        ->count();

            return [$playerId => $wins];
        });

        $sortedStandingsPlaces = $standingsToCompare->map(fn($standing) => $standing->place)
                                                    ->sort()
                                                    ->values();


        if($everyPlayerWinsCount->duplicates()->isNotEmpty()) {
            $tiedPlayers = $everyPlayerWinsCount->groupBy(fn($value) => $value)
                                                ->filter(fn($group) => $group->count() > 0)
                                                ->flatMap(fn($group) => $group->keys());

            if($tiedPlayers->count() === $everyPlayerWinsCount->count()) {
                $sortedStandingsPlaces->shuffle();

                foreach ($standingsToCompare as $index => $standing) {
                    $result->push($standing->withPlace($sortedStandingsPlaces->get($index)));
                }
            } else {
                $tiedPlayersStandings = $standingsToCompare
                                                ->filter(fn($standing) => $tiedPlayers->contains($standing->player->id));

                $comparedTiedPlayersStandings = $this->compareByDirectGame($tiedPlayersStandings, $directGames);

                foreach ($comparedTiedPlayersStandings as $standing) {
                    $result->push($standing);
                }
            }
        }

        $index = 0;

        foreach($everyPlayerWinsCount as $playerId => $playerWins) {
            $standing = $standingsToCompare->first(fn($standing) => $standing->player->id === $playerId);

            $result->push($standing->withPlace($sortedStandingsPlaces[$index]));
            $index++;
        }

        return $result;
    }
}
