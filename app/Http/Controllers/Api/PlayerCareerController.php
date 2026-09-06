<?php

namespace App\Http\Controllers\Api;

use App\Models\Player\Player;
use App\Services\Career\PlayerCareerStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PlayerCareerController
{
    public function __construct(
        private PlayerCareerStatsService $playerCareerStatsService,
    ) {
    }

    public function show(Request $request, Player $player): JsonResponse
    {
        try {
            return response()->json(
                $this->playerCareerStatsService->build(
                    $player,
                    $request->user(),
                    $request->query('window'),
                    $request->query('source'),
                ),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
