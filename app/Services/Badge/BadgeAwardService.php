<?php

namespace App\Services\Badge;

use App\Domain\Badge\BadgeDetector;
use App\Domain\Badge\Checkout\CheckoutBadgeDetector;
use App\Domain\Badge\FinishedGame;
use App\Domain\Badge\GameEligibility;
use App\Enums\GameKind;
use App\Models\Game\Game;
use App\Models\Game\GameVisit;
use App\Models\League\LeagueGame;
use App\Models\PlayoffGame\PlayoffGame;
use App\Models\QuickGame\QuickGame;
use App\Repositories\Badge\PlayerBadgeRepository;
use App\Repositories\Game\GameLegRepository;
use App\Repositories\Game\GameVisitRepository;
use App\Repositories\Player\PlayerRepository;
use App\Support\GameScoring\GameScoringContext;
use Illuminate\Support\Facades\DB;

class BadgeAwardService
{
    /** @var list<BadgeDetector> */
    private array $detectors;

    public function __construct(
        private PlayerBadgeRepository $playerBadgeRepository,
        private GameLegRepository $gameLegRepository,
        private GameVisitRepository $gameVisitRepository,
        private PlayerRepository $playerRepository,
        CheckoutBadgeDetector $checkoutDetector,
    ) {
        $this->detectors = [$checkoutDetector];
    }

    public function awardForFinishedGame(
        GameScoringContext $context,
        Game|PlayoffGame|LeagueGame|QuickGame $game,
    ): void {
        if (! in_array($context->kind, [GameKind::GROUP, GameKind::PLAYOFF, GameKind::LEAGUE], true)) {
            return;
        }

        $sourceKind = $context->kind->value;
        $sourceId = $context->gameId;

        if ($this->playerBadgeRepository->hasCommit($sourceKind, $sourceId)) {
            return;
        }

        $finished = $this->buildFinishedGame($context);
        $events = [];
        if (GameEligibility::allowsCheckoutBadges($finished)) {
            foreach ($this->detectors as $detector) {
                foreach ($detector->detect($finished) as $event) {
                    $events[] = $event;
                }
            }
        }

        $this->playerBadgeRepository->insertEvents($sourceKind, $sourceId, $events);
        foreach ($events as $event) {
            $this->playerBadgeRepository->upsertFromAward(
                $event['playerId'],
                $event['category'],
                $event['badgeKey'],
                $event['amount'],
            );
        }
        $this->playerBadgeRepository->insertCommit($sourceKind, $sourceId);
    }

    public function retractForGame(GameKind $kind, int $sourceId): void
    {
        if (! in_array($kind, [GameKind::GROUP, GameKind::PLAYOFF, GameKind::LEAGUE], true)) {
            return;
        }

        $sourceKind = $kind->value;

        DB::transaction(function () use ($sourceKind, $sourceId) {
            $deleted = $this->playerBadgeRepository->deleteEventsForSource($sourceKind, $sourceId);
            $this->playerBadgeRepository->deleteCommit($sourceKind, $sourceId);

            $seen = [];
            foreach ($deleted as $row) {
                $key = $row['player_id'].'|'.$row['category'].'|'.$row['badge_key'];
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $totals = $this->playerBadgeRepository->remainingTotals(
                    $row['player_id'],
                    $row['category'],
                    $row['badge_key'],
                );
                $this->playerBadgeRepository->replaceTotals(
                    $row['player_id'],
                    $row['category'],
                    $row['badge_key'],
                    $totals['amount'],
                    $totals['first'],
                    $totals['last'],
                );
            }
        });
    }

    private function buildFinishedGame(GameScoringContext $context): FinishedGame
    {
        $legs = $this->gameLegRepository->getForContext($context);
        $visits = $this->gameVisitRepository->getActiveForGameLegs(
            $legs->pluck('id')->map(fn ($id) => (int) $id)->all(),
        );
        $playerIds = [$context->player1Id, $context->player2Id];

        return new FinishedGame(
            kind: $context->kind,
            sourceId: $context->gameId,
            startingScore: $context->matchFormat->startingScore,
            gameType: $context->matchFormat->gameType,
            playerIds: $playerIds,
            registeredPlayerIds: $this->playerRepository->getRegisteredIds($playerIds),
            visits: $visits->map(fn (GameVisit $visit) => [
                'playerId' => (int) $visit->player_id,
                'score' => (int) $visit->score,
                'closedLeg' => (bool) $visit->closed_leg,
                'bust' => (bool) $visit->bust,
                'isVoided' => (bool) $visit->is_voided,
            ])->all(),
        );
    }
}
