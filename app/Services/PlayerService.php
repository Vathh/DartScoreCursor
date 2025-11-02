<?php

namespace App\Services;

use App\Domain\PlayerDomain;
use App\Enums\AssignableEntityType;
use App\Repositories\PlayerRepository;
use Illuminate\Support\Collection;

class PlayerService
{
    public function __construct(private PlayerRepository $playerRepository)
    {
    }

    public function create(string $name, int $userId): void
    {
        $this->playerRepository->create($name, $userId);
    }

    /**
     * @throws \Throwable
     */
    public function createGuest(string $name, int $targetId, AssignableEntityType $targetType): void
    {
        $this->playerRepository->createGuest($name, $targetId, $targetType);
    }

    public function removeGuest(int $playerId): void
    {
        $this->playerRepository->removeGuest($playerId);
    }

    /**
     * @throws \Throwable
     */
    public function getRelatedPlayers(int $seasonId): Collection
    {
        return $this->playerRepository->getRelatedPlayers($seasonId);
    }
}
