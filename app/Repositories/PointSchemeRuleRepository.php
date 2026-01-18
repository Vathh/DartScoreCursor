<?php

namespace App\Repositories;

use App\Domain\Tournament\PointSchemeRuleDomain;
use App\Enums\GameStage;
use App\Models\PointSchemeRule;

class PointSchemeRuleRepository
{
    /**
     * @param int $schemeId
     * @param GameStage $stage
     * @param int $place
     * @return PointSchemeRuleDomain|null
     */
    public function find(int $schemeId, GameStage $stage, int $place): ?PointSchemeRuleDomain
    {
        return PointSchemeRule::where('point_scheme_id', $schemeId)
            ->where('elimination_stage', $stage->value)
            ->where('place', $place)
            ->firstOrFail();
    }
}
