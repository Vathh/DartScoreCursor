<?php

namespace App\Services\Career;

use App\Repositories\Game\GameRepository;
use App\Repositories\League\LeagueGameRepository;
use App\Repositories\PlayoffGame\PlayoffGameRepository;
use App\Repositories\QuickGame\QuickGameFfaSessionRepository;
use App\Repositories\QuickGame\QuickGameRepository;
use App\Support\GameScoring\GameScoringContext;

class PlayerCareerBackfillService
{
    public function __construct(
        private PlayerCareerSnapshotService $snapshotService,
        private GameRepository $gameRepository,
        private PlayoffGameRepository $playoffGameRepository,
        private LeagueGameRepository $leagueGameRepository,
        private QuickGameRepository $quickGameRepository,
        private QuickGameFfaSessionRepository $ffaSessionRepository,
    ) {
    }

    public function backfillAll(): int
    {
        $count = 0;

        foreach ($this->gameRepository->listFinished() as $game) {
            $this->snapshotService->recordFinishedH2h(GameScoringContext::fromGroupGame($game), $game);
            $count++;
        }

        foreach ($this->playoffGameRepository->listFinished() as $game) {
            $this->snapshotService->recordFinishedH2h(GameScoringContext::fromPlayoffGame($game), $game);
            $count++;
        }

        foreach ($this->leagueGameRepository->listFinished() as $game) {
            $this->snapshotService->recordFinishedH2h(GameScoringContext::fromLeagueGame($game), $game);
            $count++;
        }

        foreach ($this->quickGameRepository->listFinished() as $game) {
            $session = $this->ffaSessionRepository->findByQuickGameId((int) $game->id);
            if ($session !== null) {
                $state = $this->stateFromSession($session);
                $this->snapshotService->recordFinishedQuickGame((int) $game->id, $session, $state);
            } else {
                $this->snapshotService->recordFinishedH2h(GameScoringContext::fromQuickGame($game), $game);
            }
            $count++;
        }

        return $count;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function stateFromSession($session): ?array
    {
        foreach (['bob27_state', 'catch40_state', 'atc_state', 'cricket_state', 'cricket56_state'] as $field) {
            if (is_array($session->{$field} ?? null)) {
                return $session->{$field};
            }
        }

        return null;
    }
}
