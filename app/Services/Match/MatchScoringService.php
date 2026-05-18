<?php

namespace App\Services\Match;

use App\DTO\Match\CloseLegPlayerStatsDTO;
use App\DTO\Match\RecordVisitDTO;
use App\Enums\GameStatus;
use App\Enums\MatchKind;
use App\Events\MatchScoringStateUpdated;
use App\Models\Game\Game;
use App\Models\Game\GameLeg;
use App\Models\PlayoffGame\PlayoffGame;
use App\Models\QuickGame\QuickGame;
use App\Repositories\Game\GameLegPlayerStatRepository;
use App\Repositories\Game\GameLegRepository;
use App\Repositories\Game\GameVisitRepository;
use App\Support\Match\MatchContext;
use App\Support\Match\MatchStatisticsCalculator;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MatchScoringService
{
    public function __construct(
        private GameLegRepository $gameLegRepository,
        private GameVisitRepository $gameVisitRepository,
        private GameLegPlayerStatRepository $gameLegPlayerStatRepository,
        private MatchStateBuilder $matchStateBuilder,
    ) {
    }

    public function resolveGroupGame(int $gameId): array
    {
        $game = Game::with(['player1', 'player2'])->findOrFail($gameId);
        $context = MatchContext::fromGroupGame($game);

        return [$context, $game];
    }

    public function resolvePlayoffGame(int $playoffGameId): array
    {
        $game = PlayoffGame::with(['player1', 'player2'])->findOrFail($playoffGameId);
        $context = MatchContext::fromPlayoffGame($game);

        return [$context, $game];
    }

    /**
     * @return array{0: MatchContext, 1: Model}
     */
    public function resolveQuickGame(int $quickGameId): array
    {
        $game = QuickGame::with(['player1', 'player2'])->findOrFail($quickGameId);
        $context = MatchContext::fromQuickGame($game);

        return [$context, $game];
    }

    /**
     * @return array<string, mixed>
     */
    public function getState(MatchContext $context, Game|PlayoffGame|QuickGame $match): array
    {
        return $this->matchStateBuilder->build($context, $match);
    }

    /**
     * @return array<string, mixed>
     */
    public function startLeg(
        MatchContext $context,
        Game|PlayoffGame|QuickGame $match,
        bool $player1DoubleTracked,
        bool $player2DoubleTracked,
    ): array {
        if ($this->gameLegRepository->findOpenForContext($context) !== null) {
            throw new DomainException('W tym meczu jest już otwarty leg.');
        }

        if ($match->status === GameStatus::FINISHED) {
            throw new DomainException('Mecz jest już zakończony.');
        }

        return DB::transaction(function () use ($context, $match, $player1DoubleTracked, $player2DoubleTracked) {
            $legNumber = $this->gameLegRepository->getForContext($context)->count() + 1;
            $leg = $this->gameLegRepository->startLeg($context, $legNumber);

            $this->gameLegPlayerStatRepository->createPlaceholder(
                $leg->id,
                $context->player1Id,
                $player1DoubleTracked,
            );
            $this->gameLegPlayerStatRepository->createPlaceholder(
                $leg->id,
                $context->player2Id,
                $player2DoubleTracked,
            );

            $this->setMatchInProgress($match);

            return $this->broadcastState($context, $match);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function recordVisit(
        MatchContext $context,
        Game|PlayoffGame|QuickGame $match,
        int $legId,
        RecordVisitDTO $dto,
    ): array {
        $leg = $this->resolveLegForContext($context, $legId);

        if (! $leg->isOpen()) {
            throw new DomainException('Leg jest już zamknięty.');
        }

        if (! in_array($dto->playerId, [$context->player1Id, $context->player2Id], true)) {
            throw new DomainException('Gracz nie należy do tego meczu.');
        }

        $existing = $this->gameVisitRepository->findByClientVisitId($dto->clientVisitId);
        if ($existing !== null) {
            return $this->broadcastState($context, $match);
        }

        return DB::transaction(function () use ($context, $match, $leg, $dto) {
            $visitNumber = $this->gameVisitRepository->nextVisitNumber($leg->id);
            $this->gameVisitRepository->create($leg->id, $visitNumber, $dto);

            return $this->broadcastState($context, $match);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function undoLastVisit(
        MatchContext $context,
        Game|PlayoffGame|QuickGame $match,
        int $legId,
    ): array {
        $leg = $this->resolveLegForContext($context, $legId);

        if (! $leg->isOpen()) {
            throw new DomainException('Cofanie wizyty po zamknięciu lega nie jest jeszcze obsługiwane.');
        }

        return DB::transaction(function () use ($context, $match, $leg) {
            $voided = $this->gameVisitRepository->voidLastForLeg($leg->id);
            if ($voided === null) {
                throw new DomainException('Brak wizyty do cofnięcia.');
            }

            return $this->broadcastState($context, $match);
        });
    }

    /**
     * @param  CloseLegPlayerStatsDTO[]  $playerStats
     * @return array<string, mixed>
     */
    public function closeLeg(
        MatchContext $context,
        Game|PlayoffGame|QuickGame $match,
        int $legId,
        int $winnerId,
        array $playerStats,
    ): array {
        $leg = $this->resolveLegForContext($context, $legId);

        if (! $leg->isOpen()) {
            throw new DomainException('Leg jest już zamknięty.');
        }

        if (! in_array($winnerId, [$context->player1Id, $context->player2Id], true)) {
            throw new DomainException('Zwycięzca lega musi być uczestnikiem meczu.');
        }

        return DB::transaction(function () use ($context, $match, $leg, $winnerId, $playerStats) {
            $legVisits = $this->gameVisitRepository->getActiveForLeg($leg->id);

            foreach ($playerStats as $statsDto) {
                $playerLegVisits = $legVisits->where('player_id', $statsDto->playerId);
                $merged = $this->mergeStatsWithVisits($statsDto, $playerLegVisits);
                $this->gameLegPlayerStatRepository->updateOnLegClose($leg->id, $merged);
            }

            $p1Points = (int) $legVisits->where('player_id', $context->player1Id)->where('bust', false)->sum('score');
            $p2Points = (int) $legVisits->where('player_id', $context->player2Id)->where('bust', false)->sum('score');

            $this->gameLegRepository->finishLeg($leg, $winnerId, $p1Points, $p2Points);

            if ($winnerId === $context->player1Id) {
                $match->player1_score = (int) $match->player1_score + 1;
            } else {
                $match->player2_score = (int) $match->player2_score + 1;
            }

            if ((int) $match->player1_score >= $context->legsToWin || (int) $match->player2_score >= $context->legsToWin) {
                $match->winner_id = (int) $match->player1_score >= $context->legsToWin
                    ? $context->player1Id
                    : $context->player2Id;
                $match->status = GameStatus::FINISHED;
            }

            $match->save();

            return $this->broadcastState($context, $match->fresh(['player1', 'player2']));
        });
    }

    private function mergeStatsWithVisits(CloseLegPlayerStatsDTO $dto, $playerLegVisits): CloseLegPlayerStatsDTO
    {
        return new CloseLegPlayerStatsDTO(
            playerId: $dto->playerId,
            doubleTracked: $dto->doubleTracked,
            doubleAttempts: $dto->doubleAttempts,
            doubleSuccesses: $dto->doubleSuccesses,
            legAverage: $dto->legAverage ?? MatchStatisticsCalculator::legAverage($playerLegVisits),
            firstNineAverage: $dto->firstNineAverage ?? MatchStatisticsCalculator::firstNineAverage($playerLegVisits),
            highestVisit: $dto->highestVisit ?? MatchStatisticsCalculator::highestVisit($playerLegVisits),
            highestFinish: $dto->highestFinish ?? MatchStatisticsCalculator::highestFinish($playerLegVisits),
            dartsThrown: $dto->dartsThrown ?? MatchStatisticsCalculator::dartsThrown($playerLegVisits),
            checkoutDart: $dto->checkoutDart ?? MatchStatisticsCalculator::checkoutDart($playerLegVisits),
        );
    }

    private function resolveLegForContext(MatchContext $context, int $legId): GameLeg
    {
        $leg = GameLeg::findOrFail($legId);

        $belongs = match ($context->kind) {
            MatchKind::GROUP => (int) $leg->game_id === $context->matchId,
            MatchKind::PLAYOFF => (int) $leg->playoff_game_id === $context->matchId,
            MatchKind::QUICK => (int) $leg->quick_game_id === $context->matchId,
        };

        if (! $belongs) {
            throw new DomainException('Leg nie należy do tego meczu.');
        }

        return $leg;
    }

    private function setMatchInProgress(Game|PlayoffGame|QuickGame $match): void
    {
        if ($match->status !== GameStatus::FINISHED) {
            $match->status = GameStatus::IN_PROGRESS;
            $match->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function broadcastState(MatchContext $context, Game|PlayoffGame|QuickGame $match): array
    {
        $match->loadMissing(['player1', 'player2']);
        $state = $this->matchStateBuilder->build($context, $match);
        broadcast(new MatchScoringStateUpdated($context, $state));

        return $state;
    }
}
