<?php

namespace App\Services\Player;

use App\Domain\Career\CareerWindow;
use App\Models\Player\Player;
use App\Models\Player\PlayerOverviewStat;
use App\Repositories\Player\PlayerOverviewStatRepository;
use App\Repositories\Player\PlayerRepository;
use App\Services\Friends\FriendshipService;
use Carbon\CarbonImmutable;

class PlayerOverviewService
{
    public function __construct(
        private PlayerOverviewStatRepository $overviewStatRepository,
        private PlayerRepository $playerRepository,
        private FriendshipService $friendshipService,
    ) {
    }

    public function rebuild(int $playerId): void
    {
        $attrs = $this->overviewStatRepository->compute($playerId);
        $this->overviewStatRepository->upsert($playerId, $attrs);
    }

    /**
     * @param  list<int|null>  $playerIds
     */
    public function rebuildRegistered(array $playerIds): void
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id) => (int) $id, $playerIds),
            static fn (int $id) => $id > 0,
        )));
        if ($ids === []) {
            return;
        }

        foreach ($this->playerRepository->getRegisteredIds($ids) as $playerId) {
            $this->rebuild($playerId);
        }
    }

    /**
     * @return array{
     *     record: array{
     *         overall: array{played: int, wins: int, winRate: string|null},
     *         tournament: array{played: int, wins: int, place1: int, place2: int, place3: int},
     *         league: array{played: int, wins: int, titles: int},
     *         quick: array{played: int, wins: int}
     *     },
     *     activity: array{
     *         days: int,
     *         gamesLast30: int,
     *         longestStreak: int,
     *         currentStreak: int,
     *         lastActivityOn: string|null,
     *         gamesPerDay: string|null,
     *         recentDays: list<array{date: string, label: string, played: bool}>
     *     },
     *     social: array{
     *         friends: int,
     *         uniqueOpponents: int,
     *         rivals: list<array{id: int, name: string, games: int}>
     *     }
     * }
     */
    public function forProfile(Player $player): array
    {
        $row = $this->overviewStatRepository->findForPlayer((int) $player->id);
        if ($row === null || $row->top_opponents === null) {
            $this->rebuild((int) $player->id);
            $row = $this->overviewStatRepository->findForPlayer((int) $player->id);
        }

        $window = CareerWindow::fromQuery('30d');
        $recent = $this->overviewStatRepository->competitiveRecordBetween(
            (int) $player->id,
            $window->startUtc(),
            $window->endExclusiveUtc(),
        );
        $played = $recent['played'];
        $wins = $recent['wins'];
        $gamesLast30 = $played;
        $lastOn = $row?->last_activity_on;
        $friends = $player->user_id
            ? $this->friendshipService->countFriends((int) $player->user_id)
            : 0;
        $activityDays = (int) ($row?->activity_days ?? 0);
        $gamesTotal = (int) ($row?->games_total ?? 0);

        return [
            'record' => [
                'overall' => [
                    'played' => $played,
                    'wins' => $wins,
                    'winRate' => $this->formatWinRate($played, $wins),
                ],
                'tournament' => [
                    'played' => (int) ($row?->games_tournament ?? 0),
                    'wins' => (int) ($row?->wins_tournament ?? 0),
                    'place1' => (int) ($row?->tournament_place_1 ?? 0),
                    'place2' => (int) ($row?->tournament_place_2 ?? 0),
                    'place3' => (int) ($row?->tournament_place_3 ?? 0),
                ],
                'league' => [
                    'played' => (int) ($row?->games_league ?? 0),
                    'wins' => (int) ($row?->wins_league ?? 0),
                    'titles' => (int) ($row?->league_titles ?? 0),
                ],
                'quick' => [
                    'played' => (int) ($row?->games_quick ?? 0),
                    'wins' => (int) ($row?->wins_quick ?? 0),
                ],
            ],
            'activity' => [
                'days' => $activityDays,
                'gamesLast30' => $gamesLast30,
                'longestStreak' => (int) ($row?->longest_streak ?? 0),
                'currentStreak' => (int) ($row?->current_streak ?? 0),
                'lastActivityOn' => $lastOn?->format('d.m.Y'),
                'gamesPerDay' => $this->gamesPerCalendarDay($player, $gamesTotal),
                'recentDays' => $this->overviewStatRepository->recentActivityDays((int) $player->id),
            ],
            'social' => [
                'friends' => $friends,
                'uniqueOpponents' => (int) ($row?->unique_opponents ?? 0),
                'rivals' => $this->rivalsFromRow($row),
            ],
        ];
    }

    private function formatWinRate(int $played, int $wins): ?string
    {
        if ($played === 0) {
            return null;
        }

        return number_format(round(100 * $wins / $played, 1), 1, '.', '').'%';
    }

    private function gamesPerCalendarDay(Player $player, int $gamesTotal): ?string
    {
        $createdAt = $player->user?->created_at;
        if ($createdAt === null) {
            return null;
        }

        $registered = CarbonImmutable::parse($createdAt)->timezone(CareerWindow::TIMEZONE)->startOfDay();
        $today = CarbonImmutable::now(CareerWindow::TIMEZONE)->startOfDay();
        $days = (int) $registered->diffInDays($today) + 1;
        if ($days < 1) {
            return null;
        }

        return number_format($gamesTotal / $days, 1, '.', '');
    }

    /**
     * @return list<array{id: int, name: string, games: int}>
     */
    private function rivalsFromRow(?PlayerOverviewStat $row): array
    {
        $stored = is_array($row?->top_opponents) ? $row->top_opponents : [];
        $ids = [];
        foreach ($stored as $item) {
            $id = (int) ($item['player_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $names = $this->playerRepository->getNamesByIds($ids);
        $rivals = [];
        foreach ($stored as $item) {
            $id = (int) ($item['player_id'] ?? 0);
            $name = $names[$id] ?? $names[(string) $id] ?? null;
            if ($id < 1 || ! is_string($name) || $name === '') {
                continue;
            }
            $rivals[] = [
                'id' => $id,
                'name' => $name,
                'games' => (int) ($item['games'] ?? 0),
            ];
        }

        return $rivals;
    }
}
