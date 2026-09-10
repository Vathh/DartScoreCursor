<?php

namespace App\Http\Controllers\Api;

use App\Services\Career\TrainingGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrainingGameController
{
    public function __construct(
        private TrainingGameService $trainingGameService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'clientUuid' => 'required|uuid',
            'gameType' => 'required|string|max:32',
            'completedAt' => 'required|date',
            'format' => 'nullable|array',
            'metrics' => 'required|array',
        ]);

        try {
            $game = $this->trainingGameService->ingest($request->user(), $validated);
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json([
            'id' => $game->id,
            'clientUuid' => $game->client_uuid,
        ], 201);
    }
}
