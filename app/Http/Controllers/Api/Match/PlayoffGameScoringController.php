<?php

namespace App\Http\Controllers\Api\Match;

use App\DTO\Match\CloseLegPlayerStatsDTO;
use App\DTO\Match\RecordVisitDTO;
use App\Http\Controllers\Controller;
use App\Services\Match\MatchScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayoffGameScoringController extends Controller
{
    public function __construct(
        private MatchScoringService $matchScoringService,
    ) {
    }

    public function state(int $playoffGameId): JsonResponse
    {
        [$context, $match] = $this->matchScoringService->resolvePlayoffGame($playoffGameId);

        return response()->json($this->matchScoringService->getState($context, $match));
    }

    public function startLeg(Request $request, int $playoffGameId): JsonResponse
    {
        $validated = $request->validate([
            'player1DoubleTracked' => 'required|boolean',
            'player2DoubleTracked' => 'required|boolean',
        ]);

        [$context, $match] = $this->matchScoringService->resolvePlayoffGame($playoffGameId);

        return response()->json(
            $this->matchScoringService->startLeg(
                $context,
                $match,
                $validated['player1DoubleTracked'],
                $validated['player2DoubleTracked'],
            )
        );
    }

    public function recordVisit(Request $request, int $playoffGameId, int $legId): JsonResponse
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

        [$context, $match] = $this->matchScoringService->resolvePlayoffGame($playoffGameId);

        return response()->json(
            $this->matchScoringService->recordVisit(
                $context,
                $match,
                $legId,
                RecordVisitDTO::fromArray($validated),
            )
        );
    }

    public function undoVisit(int $playoffGameId, int $legId): JsonResponse
    {
        [$context, $match] = $this->matchScoringService->resolvePlayoffGame($playoffGameId);

        return response()->json(
            $this->matchScoringService->undoLastVisit($context, $match, $legId)
        );
    }

    public function closeLeg(Request $request, int $playoffGameId, int $legId): JsonResponse
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

        [$context, $match] = $this->matchScoringService->resolvePlayoffGame($playoffGameId);
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
    }
}
