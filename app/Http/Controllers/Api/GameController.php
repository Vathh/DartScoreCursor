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
            'game_id' => 'required',
        ]);

        $this->gameService->setStatusInProgress($validated['game_id']);
    }

    public function update(GameResultRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $success = $this->gameService->update($validated['game'], $validated['achievements']);

        return response()->json($success);
    }

    public function
}
