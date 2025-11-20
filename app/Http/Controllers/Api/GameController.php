<?php

namespace App\Http\Controllers\Api;

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
            'game_id' => 'required',
        ]);

        $this->gameService->setStatusInProgress($validated['game_id']);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_id' => 'required',
            'tournament_id' => 'required',
            'player1_score' => 'required',
            'player2_score' => 'required',
            'winner_id' => 'required',
            'group_number' => 'required',
        ]);

        $success = $this->gameService->update($validated['game_id'],
                                    $validated['tournament_id'],
                                    $validated['player1_score'],
                                    $validated['player2_score'],
                                    $validated['winner_id'],
                                    $validated['group_number']);

        return response()->json($success);
    }
}
