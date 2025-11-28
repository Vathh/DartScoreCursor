<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\GameResultRequest;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController
{
    public function __construct(
        private GameService $gameService,
    )
    {
    }

    public function setStatusInProgress(Request $request): void
    {
        $validated = $request->validate([
            'gameId' => 'required',
        ]);

        $this->gameService->setStatusInProgress($validated['gameId']);
    }

    public function update(GameResultRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $success = $this->gameService->update($validated['game'], $validated['achievements']);

        return response()->json($success);
    }

    public function getActiveGames(Request $request): JsonResponse
    {
        $validated = $request->validate([
           'tournamentId' => 'required|integer'
        ]);

        $games = $this->gameService->getActiveGames($validated['tournamentId']);

        return response()->json($games);
    }
}
