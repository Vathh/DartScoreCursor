<?php

namespace App\Http\Controllers\Api;

use App\Services\QuickGameSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickGameSessionController
{
    public function __construct(
        private QuickGameSessionService $sessionService
    ) {
    }

    /**
     * GET /api/quick-game/session/{sessionId}
     * Pobiera aktualny stan sesji (do pollingu).
     */
    public function get(Request $request, string $sessionId): JsonResponse
    {
        $currentUserId = $request->user()?->id;
        try {
            $data = $this->sessionService->getState((int) $sessionId, $currentUserId);
            return response()->json($data);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Sesja nie została znaleziona'], 404);
        }
    }

    /**
     * POST /api/quick-game/session/{sessionId}/visit
     * Zapisuje wizytę (rzut). Body: playerIndex (int), visitScore (int), bust (bool, opcjonalnie).
     */
    public function visit(Request $request, string $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'playerIndex' => 'required|integer|min:0',
            'visitScore' => 'required|integer|min:0|max:180',
            'bust' => 'nullable|boolean',
        ]);
        $userId = $request->user()->id;
        try {
            $data = $this->sessionService->submitVisit(
                (int) $sessionId,
                $userId,
                (int) $validated['playerIndex'],
                (int) $validated['visitScore'],
                (bool) ($validated['bust'] ?? false)
            );
            return response()->json($data);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Sesja nie została znaleziona'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
