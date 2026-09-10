<?php

namespace App\Domain;

use App\Domain\Concerns\AssertsRelationsLoaded;
use App\Domain\Tournament\TournamentDomain;
use App\Enums\AchievementType;
use App\Models\Achievements\Achievement;

class AchievementDomain
{
    use AssertsRelationsLoaded;

    /** @var list<string> */
    private const RELATIONS = ['tournament', 'player'];

    public function __construct(
        public readonly int $id,
        public readonly ?TournamentDomain $tournament,
        public readonly ?PlayerDomain $player,
        public readonly AchievementType $type,
        public readonly ?int $value
    ) {}

    public static function fromEloquent(Achievement $achievement, array $with = []): AchievementDomain
    {
        self::assertRelationsLoaded($achievement, $with, self::RELATIONS);

        return new self(
            id: $achievement->id,
            tournament: in_array('tournament', $with)
                ? TournamentDomain::fromEloquent($achievement->tournament)
                : null,
            player: in_array('player', $with)
                ? PlayerDomain::fromEloquent($achievement->player)
                : null,
            type: $achievement->type,
            value: $achievement->value
        );
    }
}
