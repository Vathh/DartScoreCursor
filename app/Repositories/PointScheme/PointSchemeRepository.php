<?php

namespace App\Repositories\PointScheme;

use App\Domain\Tournament\PointSchemeDomain;
use App\Models\PointScheme\PointScheme;
use Illuminate\Support\Collection;

class PointSchemeRepository
{
    /**
     * @return Collection<PointSchemeDomain>
     */
    public function findAll(): Collection
    {
        return PointScheme::all()->map(fn ($scheme) => PointSchemeDomain::fromEloquent($scheme));
    }
}
