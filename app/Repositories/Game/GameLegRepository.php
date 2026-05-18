<?php

namespace App\Repositories\Game;

use App\DTO\GameLegDTO;
use App\Enums\MatchKind;
use App\Models\Game\GameLeg;
use App\Support\Match\MatchContext;

class GameLegRepository
{
    /**
     * @param GameLegDTO[] $legs
     * @param int|null $gameId
     * @param int|null $playoffGameId
     * @param int|null $quickGameId
     * @return void
     */
    public function createMany(array $legs, ?int $gameId = null, ?int $playoffGameId = null, ?int $quickGameId = null): void
    {
        $data = array_map(function (GameLegDTO $leg) use ($gameId, $playoffGameId, $quickGameId) {
            return [
                'game_id' => $gameId,
                'playoff_game_id' => $playoffGameId,
                'quick_game_id' => $quickGameId,
                'leg_number' => $leg->legNumber,
                'player1_score' => $leg->player1Score,
                'player2_score' => $leg->player2Score,
                'winner_id' => $leg->winnerId,
                'player1_average' => $leg->player1Average,
                'player2_average' => $leg->player2Average,
                'player1_darts_thrown' => $leg->player1DartsThrown,
                'player2_darts_thrown' => $leg->player2DartsThrown,
                'checkout_score' => $leg->checkoutScore,
                'started_at' => $leg->startedAt,
                'finished_at' => $leg->finishedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $legs);

        if (!empty($data)) {
            GameLeg::insert($data);
        }
    }

    /**
     * @param int $gameId
     * @return \Illuminate\Support\Collection
     */
    public function getByGameId(int $gameId): \Illuminate\Support\Collection
    {
        return GameLeg::where('game_id', $gameId)
            ->orderBy('leg_number')
            ->get();
    }

    /**
     * @param int $playoffGameId
     * @return \Illuminate\Support\Collection
     */
    public function getByPlayoffGameId(int $playoffGameId): \Illuminate\Support\Collection
    {
        return GameLeg::where('playoff_game_id', $playoffGameId)
            ->orderBy('leg_number')
            ->get();
    }

    public function getByQuickGameId(int $quickGameId): \Illuminate\Support\Collection
    {
        return GameLeg::where('quick_game_id', $quickGameId)
            ->orderBy('leg_number')
            ->get();
    }

    public function getForContext(MatchContext $context): \Illuminate\Support\Collection
    {
        return match ($context->kind) {
            MatchKind::GROUP => $this->getByGameId($context->matchId),
            MatchKind::PLAYOFF => $this->getByPlayoffGameId($context->matchId),
            MatchKind::QUICK => $this->getByQuickGameId($context->matchId),
        };
    }

    public function findOpenForContext(MatchContext $context): ?GameLeg
    {
        $query = GameLeg::query()->whereNull('finished_at');

        match ($context->kind) {
            MatchKind::GROUP => $query->where('game_id', $context->matchId),
            MatchKind::PLAYOFF => $query->where('playoff_game_id', $context->matchId),
            MatchKind::QUICK => $query->where('quick_game_id', $context->matchId),
        };

        return $query->first();
    }

    public function startLeg(MatchContext $context, int $legNumber): GameLeg
    {
        $data = [
            'leg_number' => $legNumber,
            'player1_score' => 0,
            'player2_score' => 0,
            'started_at' => now(),
            'finished_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        match ($context->kind) {
            MatchKind::GROUP => $data['game_id'] = $context->matchId,
            MatchKind::PLAYOFF => $data['playoff_game_id'] = $context->matchId,
            MatchKind::QUICK => $data['quick_game_id'] = $context->matchId,
        };

        return GameLeg::create($data);
    }

    public function finishLeg(GameLeg $leg, int $winnerId, int $player1LegPoints, int $player2LegPoints): void
    {
        $leg->update([
            'winner_id' => $winnerId,
            'player1_score' => $player1LegPoints,
            'player2_score' => $player2LegPoints,
            'finished_at' => now(),
        ]);
    }
}












