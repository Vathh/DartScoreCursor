<?php

namespace App\Services\Badge;

use App\Domain\Badge\BadgeCategory;
use App\Domain\Badge\Checkout\CheckoutCatalog;
use App\Domain\Badge\Checkout\CheckoutLevelPolicy;
use App\Repositories\Badge\PlayerBadgeRepository;

class CheckoutWheelAssembler
{
    public function __construct(
        private PlayerBadgeRepository $playerBadgeRepository,
    ) {}

    /**
     * Mapa checkout → times_earned dla inline SVG / JS.
     *
     * @return array<int, int>
     */
    public function hitsForPlayer(int $playerId): array
    {
        $hits = [];
        foreach ($this->playerBadgeRepository->forPlayerCategory($playerId, BadgeCategory::Checkout->value) as $badge) {
            $key = (int) $badge->badge_key;
            if ($key > 0) {
                $hits[$key] = (int) $badge->times_earned;
            }
        }

        return $hits;
    }

    /**
     * @return list<array{key: string, timesEarned: int, level: int, levelName: string}>
     */
    public function itemsForPlayer(int $playerId): array
    {
        $hits = $this->hitsForPlayer($playerId);
        $items = [];
        foreach (CheckoutCatalog::all() as $key) {
            $times = $hits[$key] ?? 0;
            $items[] = [
                'key' => (string) $key,
                'timesEarned' => $times,
                'level' => CheckoutLevelPolicy::levelForHits($times),
                'levelName' => CheckoutLevelPolicy::nameForHits($times),
            ];
        }

        return $items;
    }
}
