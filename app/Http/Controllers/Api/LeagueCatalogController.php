<?php

namespace App\Http\Controllers\Api;

use App\Models\League\League;
use App\Models\League\LeagueDivision;
use App\Services\League\LeagueService;
use Illuminate\Http\JsonResponse;

class LeagueCatalogController
{
    public function __construct(
        private LeagueService $leagueService,
    ) {
    }

    /**
     * GET /api/leagues/{league}
     */
    public function show(League $league): JsonResponse
    {
        return response()->json($this->leagueService->showForApi($league->id));
    }

    /**
     * GET /api/leagues/{league}/divisions/{division}
     */
    public function showDivision(League $league, LeagueDivision $division): JsonResponse
    {
        return response()->json($this->leagueService->divisionArchiveForApi($league->id, $division->id));
    }
}
