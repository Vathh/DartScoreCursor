<?php

namespace App\Services\Match;

use App\Enums\GameStatus;
use App\Models\Game\Game;
use App\Models\Game\GameLeg;
use App\Models\PlayoffGame\PlayoffGame;
use App\Models\QuickGame\QuickGame;
use App\Repositories\Game\GameLegPlayerStatRepository;
use App\Repositories\Game\GameLegRepository;
use App\Repositories\Game\GameVisitRepository;
use App\Support\Match\MatchContext;
use App\Support\Match\MatchStatisticsCalculator;

class MatchStateBuilder
{
    public function __construct(
        private GameLegRepository $gameLegRepository,
        private GameVisitRepository $gameVisitRepository,
        private GameLegPlayerStatRepository $gameLegPlayerStatRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(MatchContext $context, Game|PlayoffGame|QuickGame $match): array
    {
        $legs = $this->gameLegRepository->getForContext($context);
        $legIds = $legs->pluck('id')->all();
        $allVisits = $this->gameVisitRepository->getActiveForMatchLegs($legIds);
        $allLegStats = $this->gameLegPlayerStatRepository->getForLegIds($legIds);

        $openLeg = $legs->first(fn (GameLeg $leg) => $leg->isOpen());

        $player1LegsWon = (int) ($match->player1_score ?? 0);
        $player2LegsWon = (int) ($match->player2_score ?? 0);

        $players = [
            $this->buildPlayerState(
                $context->player1Id,
                $match->player1?->name ?? 'Gracz 1',
                $openLeg,
                $allVisits,
                $allLegStats,
                $context,
                $player1LegsWon,
            ),
            $this->buildPlayerState(
                $context->player2Id,
                $match->player2?->name ?? 'Gracz 2',
                $openLeg,
                $allVisits,
                $allLegStats,
                $context,
                $player2LegsWon,
            ),
        ];

        return [
            'match' => [
                'id' => $context->matchId,
                'kind' => $context->kind->value,
                'status' => $match->status instanceof GameStatus ? $match->status->value : $match->status,
                'tournamentId' => $context->tournamentId,
                'legsToWin' => $context->legsToWin,
                'player1LegsWon' => $player1LegsWon,
                'player2LegsWon' => $player2LegsWon,
                'startingScore' => $context->startingScore,
            ],
            'players' => $players,
            'currentLeg' => $openLeg ? [
                'id' => $openLeg->id,
                'legNumber' => $openLeg->leg_number,
                'open' => true,
            ] : null,
            'legs' => $legs->whereNotNull('finished_at')->map(fn (GameLeg $leg) => [
                'id' => $leg->id,
                'legNumber' => $leg->leg_number,
                'winnerId' => $leg->winner_id,
                'finishedAt' => $leg->finished_at?->toIso8601String(),
            ])->values()->all(),
            'visits' => $openLeg
                ? $allVisits->where('game_leg_id', $openLeg->id)->map(fn ($v) => [
                    'id' => $v->id,
                    'playerId' => $v->player_id,
                    'visitNumber' => $v->visit_number,
                    'score' => $v->score,
                    'remainingBefore' => $v->remaining_before,
                    'remainingAfter' => $v->remaining_after,
                    'dartsInVisit' => $v->darts_in_visit,
                    'closedLeg' => $v->closed_leg,
                    'bust' => $v->bust,
                ])->values()->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPlayerState(
        int $playerId,
        string $name,
        ?GameLeg $openLeg,
        $allVisits,
        $allLegStats,
        MatchContext $context,
        int $legsWon,
    ): array {
        $matchVisits = $allVisits->where('player_id', $playerId);
        $openLegVisits = $openLeg
            ? $matchVisits->where('game_leg_id', $openLeg->id)
            : collect();

        $remaining = $context->startingScore;
        if ($openLegVisits->isNotEmpty()) {
            $last = $openLegVisits->sortByDesc('visit_number')->sortByDesc('id')->first();
            $remaining = (int) $last->remaining_after;
        }

        return [
            'playerId' => $playerId,
            'name' => $name,
            'legsWon' => $legsWon,
            'remaining' => $remaining,
            'legAverage' => MatchStatisticsCalculator::legAverage($openLegVisits),
            'matchAverage' => MatchStatisticsCalculator::matchAverage($matchVisits),
            'firstNineAverage' => MatchStatisticsCalculator::firstNineAverage($openLegVisits),
            'doublePercent' => MatchStatisticsCalculator::matchDoublePercent($allLegStats, $playerId),
        ];
    }
}
