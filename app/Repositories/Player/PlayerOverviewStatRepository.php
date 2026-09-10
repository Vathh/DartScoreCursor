<?php

namespace App\Repositories\Player;

use App\Domain\Career\CareerWindow;
use App\Domain\Player\OverviewActivityStats;
use App\Enums\GameStatus;
use App\Enums\LeagueGameStatus;
use App\Enums\LeagueSeasonStatus;
use App\Models\Career\PlayerGameSnapshot;
use App\Models\Game\Game;
use App\Models\League\LeagueGame;
use App\Models\League\LeagueSeason;
use App\Models\Player\PlayerOverviewStat;
use App\Models\PlayoffGame\PlayoffGame;
use App\Models\QuickGame\QuickGame;
use App\Models\QuickGame\QuickGameResult;
use App\Models\Tournament\TournamentResult;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PlayerOverviewStatRepository
{
    public function __construct(
        private PlayerRepository $playerRepository,
    ) {}

    public function findForPlayer(int $playerId): ?PlayerOverviewStat
    {
        return PlayerOverviewStat::query()->where('player_id', $playerId)->first();
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    public function upsert(int $playerId, array $attrs): PlayerOverviewStat
    {
        $row = $this->findForPlayer($playerId);
        if ($row === null) {
            $row = new PlayerOverviewStat(['player_id' => $playerId]);
        }
        $row->fill($attrs);
        $row->save();

        return $row;
    }

    /**
     * @return array<string, int|string|null>
     */
    public function compute(int $playerId): array
    {
        [$tournamentPlayed, $tournamentWins, $tournamentOpponents] = $this->h2hRecord(
            Game::query()
                ->where('status', GameStatus::FINISHED)
                ->where(function ($query) use ($playerId) {
                    $query->where('player1_id', $playerId)->orWhere('player2_id', $playerId);
                })
                ->get(['id', 'player1_id', 'player2_id', 'winner_id', 'updated_at', 'created_at']),
            $playerId,
        );
        [$playoffPlayed, $playoffWins, $playoffOpponents] = $this->h2hRecord(
            PlayoffGame::query()
                ->where('status', GameStatus::FINISHED)
                ->where(function ($query) use ($playerId) {
                    $query->where('player1_id', $playerId)->orWhere('player2_id', $playerId);
                })
                ->get(['id', 'player1_id', 'player2_id', 'winner_id', 'updated_at', 'created_at']),
            $playerId,
        );
        [$leaguePlayed, $leagueWins, $leagueOpponents] = $this->h2hRecord(
            LeagueGame::query()
                ->where('status', LeagueGameStatus::FINISHED)
                ->where(function ($query) use ($playerId) {
                    $query->where('player1_id', $playerId)->orWhere('player2_id', $playerId);
                })
                ->get(['id', 'player1_id', 'player2_id', 'winner_id', 'updated_at', 'created_at']),
            $playerId,
        );

        [$quickPlayed, $quickWins, $quickOpponents] = $this->quickRecord($playerId);

        $gamesTournament = $tournamentPlayed + $playoffPlayed;
        $winsTournament = $tournamentWins + $playoffWins;
        $gamesTotal = $gamesTournament + $leaguePlayed + $quickPlayed;
        $winsTotal = $winsTournament + $leagueWins + $quickWins;

        $opponentIds = array_merge(
            $tournamentOpponents,
            $playoffOpponents,
            $leagueOpponents,
            $quickOpponents,
        );
        $registeredIds = $this->playerRepository->getRegisteredIds(array_values(array_unique($opponentIds)));
        $uniqueOpponents = count($registeredIds);
        $topOpponents = $this->topRegisteredOpponents($opponentIds, $registeredIds);

        $activity = OverviewActivityStats::fromDates(
            $this->activityDates($playerId),
            CarbonImmutable::now(CareerWindow::TIMEZONE)->toDateString(),
        );

        return [
            'games_total' => $gamesTotal,
            'wins_total' => $winsTotal,
            'games_tournament' => $gamesTournament,
            'wins_tournament' => $winsTournament,
            'games_league' => $leaguePlayed,
            'wins_league' => $leagueWins,
            'games_quick' => $quickPlayed,
            'wins_quick' => $quickWins,
            'tournament_place_1' => TournamentResult::query()->where('player_id', $playerId)->where('place', 1)->count(),
            'tournament_place_2' => TournamentResult::query()->where('player_id', $playerId)->where('place', 2)->count(),
            'tournament_place_3' => TournamentResult::query()->where('player_id', $playerId)->where('place', 3)->count(),
            'league_titles' => LeagueSeason::query()
                ->where('champion_player_id', $playerId)
                ->where('status', LeagueSeasonStatus::FINISHED)
                ->count(),
            'unique_opponents' => $uniqueOpponents,
            'top_opponents' => $topOpponents,
            'activity_days' => $activity['activity_days'],
            'current_streak' => $activity['current_streak'],
            'longest_streak' => $activity['longest_streak'],
            'last_activity_on' => $activity['last_activity_on'],
        ];
    }

    public function countCompetitiveGamesBetween(int $playerId, CarbonInterface $startUtc, CarbonInterface $endExclusiveUtc): int
    {
        return $this->competitiveRecordBetween($playerId, $startUtc, $endExclusiveUtc)['played'];
    }

    /**
     * @return list<array{date: string, label: string, played: bool}>
     */
    public function recentActivityDays(int $playerId, int $days = 7): array
    {
        return OverviewActivityStats::recentDays(
            $this->activityDates($playerId),
            CarbonImmutable::now(CareerWindow::TIMEZONE)->toDateString(),
            $days,
        );
    }

    /**
     * @return array{played: int, wins: int}
     */
    public function competitiveRecordBetween(int $playerId, CarbonInterface $startUtc, CarbonInterface $endExclusiveUtc): array
    {
        $inWindow = function ($query) use ($startUtc, $endExclusiveUtc) {
            $query->where('updated_at', '>=', $startUtc)->where('updated_at', '<', $endExclusiveUtc);
        };

        [$tournamentPlayed, $tournamentWins] = $this->h2hRecord(
            Game::query()
                ->where('status', GameStatus::FINISHED)
                ->where(function ($query) use ($playerId) {
                    $query->where('player1_id', $playerId)->orWhere('player2_id', $playerId);
                })
                ->where($inWindow)
                ->get(['id', 'player1_id', 'player2_id', 'winner_id']),
            $playerId,
        );
        [$playoffPlayed, $playoffWins] = $this->h2hRecord(
            PlayoffGame::query()
                ->where('status', GameStatus::FINISHED)
                ->where(function ($query) use ($playerId) {
                    $query->where('player1_id', $playerId)->orWhere('player2_id', $playerId);
                })
                ->where($inWindow)
                ->get(['id', 'player1_id', 'player2_id', 'winner_id']),
            $playerId,
        );
        [$leaguePlayed, $leagueWins] = $this->h2hRecord(
            LeagueGame::query()
                ->where('status', LeagueGameStatus::FINISHED)
                ->where(function ($query) use ($playerId) {
                    $query->where('player1_id', $playerId)->orWhere('player2_id', $playerId);
                })
                ->where($inWindow)
                ->get(['id', 'player1_id', 'player2_id', 'winner_id']),
            $playerId,
        );
        [$quickPlayed, $quickWins] = $this->quickRecord($playerId, $inWindow);

        return [
            'played' => $tournamentPlayed + $playoffPlayed + $leaguePlayed + $quickPlayed,
            'wins' => $tournamentWins + $playoffWins + $leagueWins + $quickWins,
        ];
    }

    /**
     * @param  Collection<int, Model>  $games
     * @return array{0: int, 1: int, 2: list<int>}
     */
    private function h2hRecord(Collection $games, int $playerId): array
    {
        $played = $games->count();
        $wins = $games->where('winner_id', $playerId)->count();
        $opponents = [];
        foreach ($games as $game) {
            $other = (int) $game->player1_id === $playerId ? (int) $game->player2_id : (int) $game->player1_id;
            if ($other > 0) {
                $opponents[] = $other;
            }
        }

        return [$played, $wins, $opponents];
    }

    /**
     * @param  (callable(\Illuminate\Database\Eloquent\Builder): mixed)|null  $inWindow
     * @return array{0: int, 1: int, 2: list<int>}
     */
    private function quickRecord(int $playerId, ?callable $inWindow = null): array
    {
        $windowGameIds = null;
        if ($inWindow !== null) {
            $windowGameIds = QuickGame::query()
                ->where('status', GameStatus::FINISHED)
                ->where($inWindow)
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->all();
            if ($windowGameIds === []) {
                return [0, 0, []];
            }
        }

        $results = QuickGameResult::query()
            ->where('player_id', $playerId)
            ->when($windowGameIds !== null, fn ($query) => $query->whereIn('quick_game_id', $windowGameIds))
            ->get(['quick_game_id', 'place']);
        $resultGameIds = $results->pluck('quick_game_id')->map(static fn ($id) => (int) $id)->unique()->values()->all();

        $played = $results->count();
        $wins = $results->filter(static fn ($row) => (int) $row->place === 1)->count();
        $opponents = [];
        if ($resultGameIds !== []) {
            $opponents = QuickGameResult::query()
                ->whereIn('quick_game_id', $resultGameIds)
                ->where('player_id', '!=', $playerId)
                ->pluck('player_id')
                ->map(static fn ($id) => (int) $id)
                ->all();
        }

        $h2h = QuickGame::query()
            ->where('status', GameStatus::FINISHED)
            ->where(function ($query) use ($playerId) {
                $query->where('player1_id', $playerId)->orWhere('player2_id', $playerId);
            })
            ->when($inWindow !== null, fn ($query) => $query->where($inWindow))
            ->when($resultGameIds !== [], fn ($query) => $query->whereNotIn('id', $resultGameIds))
            ->get(['id', 'player1_id', 'player2_id', 'winner_id']);

        $played += $h2h->count();
        $wins += $h2h->where('winner_id', $playerId)->count();
        foreach ($h2h as $game) {
            $other = (int) $game->player1_id === $playerId ? (int) $game->player2_id : (int) $game->player1_id;
            if ($other > 0) {
                $opponents[] = $other;
            }
        }

        return [$played, $wins, $opponents];
    }

    /**
     * @return list<string>
     */
    private function activityDates(int $playerId): array
    {
        $dates = [];
        $snapshotKeys = [];
        $snapshots = PlayerGameSnapshot::query()
            ->where('player_id', $playerId)
            ->get(['occurred_at', 'sourceable_type', 'sourceable_id']);

        foreach ($snapshots as $snapshot) {
            if ($snapshot->occurred_at !== null) {
                $dates[] = CarbonImmutable::parse($snapshot->occurred_at)
                    ->timezone(CareerWindow::TIMEZONE)
                    ->toDateString();
            }
            if ($snapshot->sourceable_type && $snapshot->sourceable_id) {
                $snapshotKeys[$snapshot->sourceable_type.':'.$snapshot->sourceable_id] = true;
            }
        }

        $wonWithoutSnapshot = [
            'game' => Game::query()
                ->where('status', GameStatus::FINISHED)
                ->where('winner_id', $playerId)
                ->get(['id', 'updated_at', 'created_at']),
            'playoff_game' => PlayoffGame::query()
                ->where('status', GameStatus::FINISHED)
                ->where('winner_id', $playerId)
                ->get(['id', 'updated_at', 'created_at']),
            'quick_game' => QuickGame::query()
                ->where('status', GameStatus::FINISHED)
                ->where('winner_id', $playerId)
                ->get(['id', 'updated_at', 'created_at']),
            'league_game' => LeagueGame::query()
                ->where('status', LeagueGameStatus::FINISHED)
                ->where('winner_id', $playerId)
                ->get(['id', 'updated_at', 'created_at']),
        ];

        foreach ($wonWithoutSnapshot as $morph => $games) {
            foreach ($games as $game) {
                if (isset($snapshotKeys[$morph.':'.$game->id])) {
                    continue;
                }
                $at = $game->updated_at ?? $game->created_at ?? now();
                $dates[] = CarbonImmutable::parse($at)
                    ->timezone(CareerWindow::TIMEZONE)
                    ->toDateString();
            }
        }

        return $dates;
    }

    /**
     * @param  list<int>  $opponentIds
     * @param  list<int>  $registeredIds
     * @return list<array{player_id: int, games: int}>
     */
    private function topRegisteredOpponents(array $opponentIds, array $registeredIds, int $limit = 3): array
    {
        if ($opponentIds === [] || $registeredIds === []) {
            return [];
        }

        $allowed = array_fill_keys($registeredIds, true);
        $counts = [];
        foreach ($opponentIds as $id) {
            if (! isset($allowed[$id])) {
                continue;
            }
            $counts[$id] = ($counts[$id] ?? 0) + 1;
        }
        arsort($counts);

        $top = [];
        foreach ($counts as $id => $games) {
            $top[] = ['player_id' => (int) $id, 'games' => (int) $games];
            if (count($top) >= $limit) {
                break;
            }
        }

        return $top;
    }
}
