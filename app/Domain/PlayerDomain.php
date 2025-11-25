<?php
namespace App\Domain;

use App\Models\League;
use App\Models\Player;
use Carbon\Carbon;

class PlayerDomain
{

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?array $achievements
    )
    {}

    public static function fromEloquent(Player $player, array $with = []): self
    {
        $player->loadMissing(array_intersect($with, ['achievements']));

        return new self(
            id: $player->id,
            name: $player->name,
            achievements: in_array('achievements', $with)
                ?   $player->achievements->map(fn($achievement) => AchievementDomain::fromEloquent($achievement))->values()
                :   collect()
        );
    }
}
