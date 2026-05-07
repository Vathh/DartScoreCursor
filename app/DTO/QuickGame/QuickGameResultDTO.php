<?php

namespace App\DTO\QuickGame;

use App\DTO\GameAchievementDTO;

class QuickGameResultDTO
{
    /**
     * @param PlayerResultDTO[] $players
     * @param GameAchievementDTO[] $achievements
     * @param QuickGameLegDTO[] $legs
     */
    public function __construct(
        public array $players,
        public array $achievements,
        public array $legs = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        $players = array_map(fn($array) => PlayerResultDTO::fromArray($array), $data['players'] ?? []);
        
        // Dla quick games tournamentId jest zawsze null
        $achievements = array_map(function ($array) {
            $array['tournamentId'] = null; // Quick games nie mają tournamentId
            return GameAchievementDTO::fromArray($array);
        }, $data['achievements'] ?? []);
        
        $legs = array_map(fn($array) => QuickGameLegDTO::fromArray($array), $data['legs'] ?? []);

        return new self(
            players: $players,
            achievements: $achievements,
            legs: $legs
        );
    }
}

