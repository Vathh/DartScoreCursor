<?php

namespace App\Services\Game;

use App\DTO\GameLegDTO;
use App\Repositories\Game\GameLegRepository;

class GameLegService
{
    public function __construct(
        private GameLegRepository $gameLegRepository
    ) {}

    /**
     * @param  GameLegDTO[]  $legs
     */
    public function createMany(array $legs, ?int $gameId = null, ?int $playoffGameId = null, ?int $quickGameId = null): void
    {
        if (empty($legs)) {
            return;
        }

        $this->gameLegRepository->createMany($legs, $gameId, $playoffGameId, $quickGameId);
    }
}
