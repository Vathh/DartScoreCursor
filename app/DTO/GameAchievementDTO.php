<?php

namespace App\DTO;

use App\Enums\AchievementType;

class GameAchievementDTO
{

    public function __construct(
        public int $playerId,
        public int $tournamentId,
        public int $value,
        public AchievementType $achievementType,
    )
    {
    }

    public static function fromArray(array $data): GameAchievementDTO
    {
        return new self(
            playerId: $data['playerId'],
            tournamentId: $data['tournamentId'],
            value: $data['value'],
            achievementType: AchievementType::from($data['type'])
        );
    }
}
