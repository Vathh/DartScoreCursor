<?php

namespace App\Http\Controllers\Api\Match;

use App\DTO\Match\CloseLegPlayerStatsDTO;
use App\DTO\Match\RecordVisitDTO;
use App\Http\Controllers\Controller;
use App\Services\Match\MatchScoringService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickGameScoringController extends Controller
{
    public function __construct(
        private MatchScoringService $matchScoringService,
    ) {
    }

    public function state(int $quickGameId): JsonResponse
    {
        try {
            [$context, $match] = $this->matchScoringService->resolveQuickGame($quickGameId);

            return response()->json($this->matchScoringService->getState($context, $match));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function startLeg(Request $request, int $quickGameId): JsonResponse
    {
        $validated = $request->validate([
            'player1DoubleTracked' => 'required|boolean',
            'player2DoubleTracked' => 'required|boolean',
        ]);

        try {
            [$context, $match] = $this->matchScoringService->resolveQuickGame($quickGameId);

            return response()->json(
                $this->matchScoringService->startLeg(
                    $context,
                    $match,
                    $validated['player1DoubleTracked'],
                    $validated['player2DoubleTracked'],
                )
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function recordVisit(Request $request, int $quickGameId, int $legId): JsonResponse
    {
        $validated = $request->validate([
            'playerId' => 'required|integer|exists:players,id',
            'score' => 'required|integer|min:0|max:180',
            'remainingBefore' => 'required|integer|min:0|max:501',
            'remainingAfter' => 'required|integer|min:0|max:501',
            'dartsInVisit' => 'required|integer|min:1|max:3',
            'closedLeg' => 'boolean',
            'bust' => 'boolean',
            'clientVisitId' => 'required|uuid',
        ]);

        try {
            [$context, $match] = $this->matchScoringService->resolveQuickGame($quickGameId);

            return response()->json(
                $this->matchScoringService->recordVisit(
                    $context,
                    $match,
                    $legId,
                    RecordVisitDTO::fromArray($validated),
                )
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function undoVisit(int $quickGameId, int $legId): JsonResponse
    {
        try {
            [$context, $match] = $this->matchScoringService->resolveQuickGame($quickGameId);

            return response()->json(
                $this->matchScoringService->undoLastVisit($context, $match, $legId)
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function closeLeg(Request $request, int $quickGameId, int $legId): JsonResponse
    {
        $validated = $request->validate([
            'winnerId' => 'required|integer|exists:players,id',
            'players' => 'required|array|min:1|max:2',
            'players.*.playerId' => 'required|integer|exists:players,id',
            'players.*.doubleTracked' => 'required|boolean',
            'players.*.doubleAttempts' => 'nullable|integer|min:0',
            'players.*.doubleSuccesses' => 'nullable|integer|min:0',
            'players.*.legAverage' => 'nullable|numeric',
            'players.*.firstNineAverage' => 'nullable|numeric',
            'players.*.highestVisit' => 'nullable|integer|min:0|max:180',
            'players.*.highestFinish' => 'nullable|integer|min:0|max:180',
            'players.*.dartsThrown' => 'nullable|integer|min:0',
            'players.*.checkoutDart' => 'nullable|integer|min:1|max:3',
        ]);

        try {
            [$context, $match] = $this->matchScoringService->resolveQuickGame($quickGameId);
            $playerStats = array_map(
                fn (array $row) => CloseLegPlayerStatsDTO::fromArray($row),
                $validated['players'],
            );

            return response()->json(
                $this->matchScoringService->closeLeg(
                    $context,
                    $match,
                    $legId,
                    (int) $validated['winnerId'],
                    $playerStats,
                )
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
