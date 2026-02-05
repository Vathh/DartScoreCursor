<?php

namespace App\Http\Controllers\Api;

use App\Services\QuickGameLobbyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickGameLobbyController
{
    public function __construct(
        private QuickGameLobbyService $lobbyService
    ) {
    }

    /**
     * Tworzy nowe lobby
     * POST /api/quick-game/lobby/create
     */
    public function create(Request $request): JsonResponse
    {
        // Wymaga autoryzacji - tylko zalogowani mogą tworzyć lobby
        $userId = $request->user()->id;

        try {
            $lobby = $this->lobbyService->create($userId);

            return response()->json([
                'lobby' => $this->formatLobby($lobby, $request)
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Dołącza do lobby po kodzie
     * POST /api/quick-game/lobby/join
     */
    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
            'tempPlayerName' => 'nullable|string|max:50', // Dla graczy tymczasowych
        ]);

        $userId = $request->user()?->id; // Opcjonalne - może być null

        try {
            $lobby = $this->lobbyService->joinByCode(
                $validated['code'],
                $userId,
                $validated['tempPlayerName'] ?? null
            );

            return response()->json([
                'lobby' => $this->formatLobby($lobby, $request)
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Dołącza do lobby przez zaproszenie (z panelu znajomych)
     * POST /api/quick-game/lobby/{lobbyId}/join
     */
    public function joinById(Request $request, int $lobbyId): JsonResponse
    {
        // Wymaga autoryzacji
        $userId = $request->user()->id;

        try {
            $lobby = $this->lobbyService->joinById($lobbyId, $userId);

            return response()->json([
                'lobby' => $this->formatLobby($lobby, $request)
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Opuszcza lobby
     * POST /api/quick-game/lobby/{lobbyId}/leave
     */
    public function leave(Request $request, int $lobbyId): JsonResponse
    {
        $validated = $request->validate([
            'tempPlayerName' => 'nullable|string|max:50', // Dla graczy tymczasowych
        ]);

        $userId = $request->user()?->id; // Opcjonalne

        try {
            $this->lobbyService->leave(
                $lobbyId,
                $userId,
                $validated['tempPlayerName'] ?? null
            );

            return response()->json([
                'message' => 'Opuszczono lobby'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Pobiera stan lobby (polling)
     * GET /api/quick-game/lobby/{lobbyId}
     */
    public function get(Request $request, int $lobbyId): JsonResponse
    {
        try {
            $lobby = $this->lobbyService->get($lobbyId);

            return response()->json([
                'lobby' => $this->formatLobby($lobby, $request)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Lobby nie zostało znalezione'
            ], 404);
        }
    }

    /**
     * Pobiera lobby po kodzie
     * GET /api/quick-game/lobby/code/{code}
     */
    public function getByCode(Request $request, string $code): JsonResponse
    {
        $lobby = $this->lobbyService->getByCode($code);

        if (!$lobby) {
            return response()->json([
                'message' => 'Lobby nie zostało znalezione'
            ], 404);
        }

        return response()->json([
            'lobby' => $this->formatLobby($lobby, $request)
        ]);
    }

    /**
     * Ustawia status gotowości gracza
     * POST /api/quick-game/lobby/{lobbyId}/ready
     */
    public function setReady(Request $request, int $lobbyId): JsonResponse
    {
        // Wymaga autoryzacji
        $userId = $request->user()->id;

        $validated = $request->validate([
            'isReady' => 'required|boolean',
        ]);

        try {
            $lobby = $this->lobbyService->setReady($lobbyId, $userId, $validated['isReady']);

            return response()->json([
                'lobby' => $this->formatLobby($lobby, $request)
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Rozpoczyna mecz
     * POST /api/quick-game/lobby/{lobbyId}/start
     */
    public function start(Request $request, int $lobbyId): JsonResponse
    {
        // Wymaga autoryzacji - tylko host może rozpocząć
        $userId = $request->user()->id;

        try {
            $lobby = $this->lobbyService->startGame($lobbyId, $userId);

            return response()->json([
                'lobby' => $this->formatLobby($lobby, $request),
                'message' => 'Mecz rozpoczęty'
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Zaprasza znajomego do lobby
     * POST /api/quick-game/lobby/{lobbyId}/invite
     */
    public function invite(Request $request, int $lobbyId): JsonResponse
    {
        // Wymaga autoryzacji
        $userId = $request->user()->id;

        $validated = $request->validate([
            'friendId' => 'required|integer|exists:users,id',
        ]);

        // TODO: W przyszłości można dodać system powiadomień/zaproszeń do lobby
        // Na razie zwracamy informację o lobby, które znajomy może dołączyć
        try {
            $lobby = $this->lobbyService->get($lobbyId);

            // Sprawdź czy użytkownik jest w lobby
            $isInLobby = $lobby->players()
                ->whereHas('player', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->exists();

            if (!$isInLobby && $lobby->host_id !== $userId) {
                return response()->json([
                    'message' => 'Nie jesteś w tym lobby'
                ], 403);
            }

            return response()->json([
                'lobby' => $this->formatLobby($lobby, $request),
                'message' => 'Zaproszenie wysłane. Znajomy może dołączyć używając kodu: ' . $lobby->code
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Formatuje lobby do odpowiedzi JSON
     */
    private function formatLobby($lobby, Request $request): array
    {
        $userId = $request->user()?->id;
        $isHost = $userId && $lobby->host_id === $userId;
        $currentPlayerEntry = null;
        if ($userId) {
            $hostPlayerId = $lobby->host->player?->id;
            foreach ($lobby->players as $lp) {
                if ($lp->is_registered && $lp->player && $lp->player->user_id === $userId) {
                    $currentPlayerEntry = [
                        'id' => $lp->id,
                        'playerId' => $lp->player_id,
                        'name' => $lp->player?->name ?? 'Brak nazwy',
                        'isRegistered' => true,
                        'isReady' => $lp->is_ready,
                    ];
                    break;
                }
            }
        }

        return [
            'id' => $lobby->id,
            'code' => $lobby->code,
            'status' => $lobby->status,
            'isHost' => $isHost,
            'currentPlayer' => $currentPlayerEntry,
            'host' => [
                'id' => $lobby->host_id,
                'name' => $lobby->host->player?->name ?? 'Brak nazwy',
                'playerId' => $lobby->host->player?->id ?? null,
            ],
            'players' => $lobby->players->map(function ($lobbyPlayer) {
                return [
                    'id' => $lobbyPlayer->id,
                    'playerId' => $lobbyPlayer->player_id,
                    'name' => $lobbyPlayer->is_registered 
                        ? ($lobbyPlayer->player?->name ?? 'Brak nazwy')
                        : $lobbyPlayer->temp_player_name,
                    'isRegistered' => $lobbyPlayer->is_registered,
                    'isReady' => $lobbyPlayer->is_ready,
                ];
            }),
            'startedAt' => $lobby->started_at?->toIso8601String(),
            'createdAt' => $lobby->created_at->toIso8601String(),
        ];
    }
}
