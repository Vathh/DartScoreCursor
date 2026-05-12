<?php

namespace App\Services\PointScheme;

use App\Domain\Tournament\PointSchemeDomain;
use App\Repositories\PointScheme\PointSchemeRepository;
use DomainException;

class PointSchemeService
{

    public function __construct(
        private PointSchemeRepository $pointSchemeRepository,
    )
    {
    }

    public function findByPlayersAmount(int $playersAmount): PointSchemeDomain
    {
        $candidates = $this->pointSchemeRepository->findAll()->filter(function (PointSchemeDomain $scheme) use ($playersAmount) {
            return $playersAmount >= $scheme->minPlayers && $playersAmount <= $scheme->maxPlayers;
        });

        if ($candidates->isEmpty()) {
            throw new DomainException(
                'Brak schematu punktowego dla liczby graczy: '.$playersAmount
                .'. Przedziały `min_players` / `max_players` definiuje `database/seeders/PointSchemeSeeder.php`.'
            );
        }

        // Przy nakładających się zakresach (np. 32 ∈ [25,32] i [32,39]) wybierz „wyższy” przedział — większe min_players.
        return $candidates->sortByDesc(fn (PointSchemeDomain $s) => $s->minPlayers)->first();
    }
}












