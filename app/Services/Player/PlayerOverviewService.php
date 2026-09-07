<?php

namespace App\Services\Player;

use App\Domain\Career\CareerWindow;
use App\Models\Player\Player;
use App\Repositories\Player\PlayerOverviewStatRepository;
use App\Repositories\Player\PlayerRepository;
use App\Services\Friends\FriendshipService;

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
     *         lastActivityOn: string|null
     *     },
     *     social: array{friends: int, uniqueOpponents: int}
     * }
     */
    public function forProfile(Player $player): array
    {
        $row = $this->overviewStatRepository->findForPlayer((int) $player->id);
        if ($row === null) {
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
                'days' => (int) ($row?->activity_days ?? 0),
                'gamesLast30' => $gamesLast30,
                'longestStreak' => (int) ($row?->longest_streak ?? 0),
                'currentStreak' => (int) ($row?->current_streak ?? 0),
                'lastActivityOn' => $lastOn?->format('d.m.Y'),
            ],
            'social' => [
                'friends' => $friends,
                'uniqueOpponents' => (int) ($row?->unique_opponents ?? 0),
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
}
