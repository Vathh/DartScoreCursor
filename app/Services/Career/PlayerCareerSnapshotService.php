<?php

namespace App\Services\Career;

use App\Domain\Career\CareerSnapshotMetrics;
use App\Domain\GameScoring\MatchFormat;
use App\Enums\CareerSource;
use App\Enums\GameKind;
use App\Models\Career\TrainingGame;
use App\Models\Game\Game;
use App\Models\League\LeagueGame;
use App\Models\PlayoffGame\PlayoffGame;
use App\Models\QuickGame\QuickGame;
use App\Models\QuickGame\QuickGameFfaSession;
use App\Repositories\Career\PlayerGameSnapshotRepository;
use App\Repositories\Game\GameLegPlayerStatRepository;
use App\Repositories\Game\GameLegRepository;
use App\Repositories\Game\GameVisitRepository;
use App\Repositories\Player\PlayerRepository;
use App\Repositories\QuickGame\QuickGameFfaVisitRepository;
use App\Repositories\QuickGame\QuickGameRepository;
use App\Support\GameScoring\GameScoringContext;
use Illuminate\Database\Eloquent\Model;

class PlayerCareerSnapshotService
{
    public function __construct(
        private PlayerGameSnapshotRepository $snapshotRepository,
        private PlayerRepository $playerRepository,
        private GameLegRepository $gameLegRepository,
        private GameVisitRepository $gameVisitRepository,
        private GameLegPlayerStatRepository $gameLegPlayerStatRepository,
        private QuickGameFfaVisitRepository $ffaVisitRepository,
        private QuickGameRepository $quickGameRepository,
    ) {
    }

    public function recordFinishedH2h(
        GameScoringContext $context,
        Game|PlayoffGame|QuickGame|LeagueGame $game,
    ): void {
        $source = match ($context->kind) {
            GameKind::GROUP, GameKind::PLAYOFF => CareerSource::Tournament,
            GameKind::LEAGUE => CareerSource::League,
            GameKind::QUICK => CareerSource::Quick,
        };

        $legs = $this->gameLegRepository->getForContext($context);
        $legIds = $legs->pluck('id')->map(fn ($id) => (int) $id)->all();
        $visits = $this->gameVisitRepository->getActiveForGameLegs($legIds);
        $stats = $this->gameLegPlayerStatRepository->getForLegIds($legIds);
        $gameType = $context->matchFormat->gameType ?: MatchFormat::DEFAULT_GAME_TYPE;
        $occurredAt = $game->updated_at ?? now();

        foreach ([$context->player1Id, $context->player2Id] as $playerId) {
            if (! $this->isRegistered($playerId)) {
                continue;
            }

            $playerVisits = $visits->where('player_id', $playerId)->values();
            $playerStats = $stats->where('player_id', $playerId);
            $trackedRows = $playerStats->filter(fn ($row) => (bool) $row->double_tracked);
            $doubleTracked = $trackedRows->isNotEmpty();
            $attempts = $doubleTracked ? (int) $trackedRows->sum('double_attempts') : null;
            $successes = $doubleTracked ? (int) $trackedRows->sum('double_successes') : null;

            $metrics = $this->isX01GameType($gameType)
                ? CareerSnapshotMetrics::fromX01Visits($playerVisits, $doubleTracked, $attempts, $successes)
                : CareerSnapshotMetrics::fromNonX01((int) $playerVisits->sum('darts_in_visit'));

            if ($metrics === null) {
                continue;
            }

            $this->snapshotRepository->upsertForSourceable(
                $playerId,
                $source,
                $this->storedGameType($gameType),
                $occurredAt,
                $game,
                $metrics,
            );
        }
    }

    /**
     * @param  array<string, mixed>|null  $gameState  dartLog (Bob27) albo darts_thrown z wyników
     */
    public function recordFinishedQuickGame(
        int $quickGameId,
        QuickGameFfaSession $session,
        ?array $gameState = null,
    ): void {
        $quickGame = $this->quickGameRepository->findModel($quickGameId);
        $gameType = (string) ($quickGame->game_type ?: $session->game_type ?: MatchFormat::DEFAULT_GAME_TYPE);
        $occurredAt = $session->finished_at ?? $quickGame->updated_at ?? now();
        $playerIds = array_map('intval', $session->player_order ?? []);
        $registeredIds = $this->playerRepository->getRegisteredIds($playerIds);

        if ($registeredIds === []) {
            return;
        }

        $resultsByPlayer = $this->quickGameRepository->getResultsForGame($quickGameId)->keyBy('player_id');

        $visits = $this->isX01GameType($gameType)
            ? $this->ffaVisitRepository->getActiveForSession($session)
            : collect();

        $dartLog = is_array($gameState['dartLog'] ?? null) ? $gameState['dartLog'] : [];

        foreach ($registeredIds as $playerId) {
            $metrics = match (true) {
                $gameType === MatchFormat::GAME_TYPE_BOB27 => CareerSnapshotMetrics::fromBob27DartLog($dartLog, $playerId),
                $this->isX01GameType($gameType) => CareerSnapshotMetrics::fromX01Visits(
                    $visits->where('player_id', $playerId)->values(),
                    false,
                    null,
                    null,
                ),
                default => CareerSnapshotMetrics::fromNonX01(
                    $resultsByPlayer->get($playerId)?->darts_thrown,
                ),
            };

            if ($metrics === null) {
                continue;
            }

            $this->snapshotRepository->upsertForSourceable(
                $playerId,
                CareerSource::Quick,
                $this->storedGameType($gameType),
                $occurredAt,
                $quickGame,
                $metrics,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    public function recordTrainingGame(TrainingGame $trainingGame, array $metrics): void
    {
        $this->snapshotRepository->upsertForSourceable(
            (int) $trainingGame->player_id,
            CareerSource::Training,
            (string) $trainingGame->game_type,
            $trainingGame->completed_at,
            $trainingGame,
            $metrics,
            (string) $trainingGame->client_uuid,
        );
    }

    public function deleteForSourceable(Model $sourceable): void
    {
        $this->snapshotRepository->deleteForSourceable($sourceable);
    }

    private function isRegistered(int $playerId): bool
    {
        return $this->playerRepository->getRegisteredIds([$playerId]) !== [];
    }

    private function isX01GameType(string $gameType): bool
    {
        return ! in_array($gameType, [
            MatchFormat::GAME_TYPE_CRICKET,
            MatchFormat::GAME_TYPE_BOB27,
            MatchFormat::GAME_TYPE_ATC,
            MatchFormat::GAME_TYPE_CATCH40,
            MatchFormat::GAME_TYPE_CRICKET56,
        ], true);
    }

    private function storedGameType(string $gameType): string
    {
        return $this->isX01GameType($gameType) ? MatchFormat::DEFAULT_GAME_TYPE : $gameType;
    }
}
