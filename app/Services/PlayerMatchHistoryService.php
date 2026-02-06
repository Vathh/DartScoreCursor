<?php

namespace App\Services;

use App\Repositories\PlayerMatchHistoryRepository;

class PlayerMatchHistoryService
{
    public function __construct(
        private PlayerMatchHistoryRepository $playerMatchHistoryRepository
    ) {
    }

    /**
     * Zwraca stronę historii meczów gracza (5 na stronę).
     *
     * @return array{items: array, has_more: bool}
     */
    public function getHistoryPage(int $playerId, int $page): array
    {
        return $this->playerMatchHistoryRepository->getHistoryPage($playerId, max(1, $page));
    }
}
