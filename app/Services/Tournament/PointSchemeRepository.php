<?php

namespace App\Repositories\Tournament;

use App\Domain\Tournament\PointSchemeDomain;
use App\Models\PointScheme;

class PointSchemeRepository
{
    public function findById(int $pointSchemeId): ?PointSchemeDomain
    {
        $scheme = PointScheme::with('rules')->findOrFail($pointSchemeId);

        return PointSchemeDomain::fromEloquent($scheme, ['rules']);
    }
}
