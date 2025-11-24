<?php

namespace App\Domain;

use App\Models\Tournament;
use Carbon\Carbon;

class TournamentDomain
{

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?Carbon $date,
        public readonly ?SeasonDomain $season,
        public readonly ?Carbon $updatedAt,
    )
    {
    }

    public static function fromEloquent(Tournament $tournament, array $with = []): self
    {
        $tournament->loadMissing(array_intersect($with, ['season']));

        return new self(
            id: $tournament->id,
            name: $tournament->name,
            date: $tournament->date,
            season: in_array('season', $with)
                ? SeasonDomain::fromEloquent($tournament->season)
                : null,
            updatedAt: $tournament->updated_at
        );
    }

    public function getDate(): ?string
    {
        return $this->date?->format('Y-m-d');
    }

    public function getUpdatedAtDate(): string
    {
        return $this->updatedAt?->format('Y-m-d');
    }
}
