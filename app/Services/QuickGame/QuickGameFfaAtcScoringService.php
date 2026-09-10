<?php

namespace App\Services\QuickGame;

use App\DTO\QuickGame\PlayerResultDTO;
use App\Domain\GameScoring\MatchFormat;
use App\Domain\QuickGame\AroundTheClockRules;
use App\Domain\QuickGame\FfaLegCycle;
use App\Domain\QuickGame\FfaMatchLog;
use App\Domain\QuickGame\FfaTurnRotationDomain;
use App\Models\QuickGame\QuickGameFfaSession;
use App\Repositories\Player\PlayerRepository;
use App\Repositories\QuickGame\QuickGameFfaPresenceRepository;
use App\Repositories\QuickGame\QuickGameFfaSessionRepository;
use App\Support\QuickGameFfa\FfaStateBroadcaster;
use App\Support\QuickGameFfa\FfaTurnNormalize;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Scoring Around the Clock FFA — osobny kontrakt od X01, cricket i Bob's 27.
 */
class QuickGameFfaAtcScoringService
{
    public function __construct(
        private QuickGameFfaSessionRepository $sessionRepository,
        private QuickGameFfaPresenceRepository $presenceRepository,
        private PlayerRepository $playerRepository,
        private FfaMatchFinishService $matchFinishService,
        private FfaSubmitGuard $submitGuard,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getState(int $lobbyId, ?int $userId = null): array
    {
        $session = $this->sessionRepository->findOrFailForLobby($lobbyId);
        $session->loadMissing('lobby');
        $this->assertAtcSession($session);

        return $this->buildState($session, $userId);
    }

    /**
     * @return array<string, mixed>
     */
    public function recordVisit(
        int $lobbyId,
        int $userId,
        int $playerId,
        int $hits,
        string $clientVisitId,
    ): array {
        return DB::transaction(function () use ($lobbyId, $userId, $playerId, $hits, $clientVisitId) {
            $session = $this->sessionRepository->findOrFailForLobby($lobbyId);
            $session->loadMissing('lobby');
            $this->assertAtcSession($session);

            if (! $session->isInProgress()) {
                throw new DomainException('Mecz jest już zakończony.');
            }

            $playerIds = array_map('intval', $session->player_order ?? []);
            $leftIds = $this->presenceRepository->getLeftPlayerIds($session);
            $state = $this->normalizeState($session, $playerIds);
            $skipIds = $leftIds;

            if (! in_array($playerId, $playerIds, true)) {
                throw new DomainException('Gracz nie należy do tego meczu.');
            }
            $this->submitGuard->assert($session, $userId, $playerId);
            if (in_array($playerId, $skipIds, true)) {
                throw new DomainException('Ten gracz nie rzuca w tej turze.');
            }

            $this->normalizeTurnIndices($session, $playerIds, $skipIds);

            $currentPlayerId = (int) $playerIds[(int) $session->current_player_index];
            if ($playerId !== $currentPlayerId) {
                throw new DomainException('Teraz rzuca inny gracz.');
            }

            foreach ($state['dartLog'] as $entry) {
                if (($entry['clientDartId'] ?? $entry['clientVisitId'] ?? null) === $clientVisitId) {
                    return $this->broadcastState($session->fresh(), $userId);
                }
            }

            $playerIndex = (int) array_search($playerId, $playerIds, true);
            $pidKey = (string) $playerId;
            $board = $state['boards'][$pidKey] ?? AroundTheClockRules::emptyBoard();
            $targetBefore = (int) ($board['targetIndex'] ?? 0);
            $hits = AroundTheClockRules::clampHits($hits, $targetBefore);

            $state['dartLog'][] = [
                'playerId' => $playerId,
                'kind' => 'visit',
                'hits' => $hits,
                'clientDartId' => $clientVisitId,
                'clientVisitId' => $clientVisitId,
                'legNumber' => (int) $session->current_leg_number,
                'legOpenerIndex' => (int) $session->leg_opener_index,
                'currentPlayerIndex' => (int) $session->current_player_index,
                'boardsSnapshot' => $state['boards'],
                'legsWonSnapshot' => $session->legs_won_in_set,
            ];

            $applied = AroundTheClockRules::applyVisit($targetBefore, $hits);
            $state['boards'][$pidKey] = $applied;
            $state['dartLog'][array_key_last($state['dartLog'])]['finished'] = $applied['finished'];

            if ($applied['finished']) {
                $this->closeLeg($session, $state, $playerIds, $leftIds, $playerIndex);
            } else {
                $session->current_player_index = FfaTurnRotationDomain::nextIndexAfter(
                    (int) $session->current_player_index,
                    $playerIds,
                    $skipIds,
                );
            }

            $session->atc_state = $state;
            $this->sessionRepository->incrementVersion($session);
            $this->sessionRepository->save($session);

            return $this->broadcastState($session->fresh(), $userId);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function undoLastVisit(int $lobbyId, int $userId): array
    {
        return DB::transaction(function () use ($lobbyId, $userId) {
            $session = $this->sessionRepository->findOrFailForLobby($lobbyId);
            $session->loadMissing('lobby');
            $this->assertAtcSession($session);

            if (! $session->isInProgress()) {
                throw new DomainException('Mecz jest już zakończony.');
            }

            $this->submitGuard->assert($session, $userId, null);

            $playerIds = array_map('intval', $session->player_order ?? []);
            $state = $this->normalizeState($session, $playerIds);
            if ($state['dartLog'] === []) {
                throw new DomainException('Brak wizyty do cofnięcia.');
            }

            $last = array_pop($state['dartLog']);
            $state['boards'] = $last['boardsSnapshot'] ?? $state['boards'];
            $session->legs_won_in_set = $last['legsWonSnapshot'] ?? $session->legs_won_in_set;
            $session->current_leg_number = (int) ($last['legNumber'] ?? $session->current_leg_number);
            $session->leg_opener_index = (int) ($last['legOpenerIndex'] ?? $session->leg_opener_index);
            $session->current_player_index = (int) ($last['currentPlayerIndex'] ?? $session->current_player_index);
            $session->atc_state = $state;

            $this->sessionRepository->incrementVersion($session);
            $this->sessionRepository->save($session);

            return $this->broadcastState($session->fresh(), $userId);
        });
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<int>  $playerIds
     * @param  list<int>  $leftIds
     */
    private function closeLeg(
        QuickGameFfaSession $session,
        array &$state,
        array $playerIds,
        array $leftIds,
        int $winnerIndex,
    ): void {
        $winnerId = (int) $playerIds[$winnerIndex];
        $legsWon = $session->legs_won_in_set ?? [];
        foreach ($playerIds as $pid) {
            $legsWon[$pid] ??= 0;
        }
        $legsWon[$winnerId] = (int) ($legsWon[$winnerId] ?? 0) + 1;
        $session->legs_won_in_set = $legsWon;

        $format = MatchFormat::fromArray(array_merge(
            MatchFormat::fromRecord($session)->toArray(),
            ['gameType' => MatchFormat::GAME_TYPE_ATC, 'setsToWinMatch' => 1],
        ));
        if ((int) $legsWon[$winnerId] >= $format->legsToWinSet) {
            $this->finishMatch($session, $legsWon, $format, $state);
            FfaMatchLog::archive($state);

            return;
        }

        $this->resetBoard($state, $playerIds);
        FfaMatchLog::archive($state);
        FfaLegCycle::startNextLeg($session, $playerIds, $leftIds);
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<int>  $playerIds
     */
    private function resetBoard(array &$state, array $playerIds): void
    {
        $fresh = AroundTheClockRules::initialState($playerIds);
        $state['boards'] = $fresh['boards'];
    }

    /**
     * @param  array<int, int>  $legsWon
     * @param  array<string, mixed>  $state
     */
    private function finishMatch(
        QuickGameFfaSession $session,
        array $legsWon,
        MatchFormat $format,
        array $state,
    ): void {
        $playerIds = array_map('intval', $session->player_order ?? []);
        $ranked = $this->matchFinishService->rankedByLegsWon($playerIds, $legsWon);

        $dartCounts = [];
        foreach ($playerIds as $pid) {
            $dartCounts[$pid] = 0;
        }
        foreach ($state['dartLog'] ?? [] as $entry) {
            $pid = (int) ($entry['playerId'] ?? 0);
            if (! isset($dartCounts[$pid])) {
                continue;
            }
            $finished = ! empty($entry['finished']);
            $hits = (int) ($entry['hits'] ?? 0);
            $dartCounts[$pid] += $finished ? max(1, $hits) : 3;
        }

        $results = [];
        foreach ($ranked as $i => $row) {
            $pid = $row['playerId'];
            $progress = (int) ($state['boards'][(string) $pid]['targetIndex'] ?? 0);
            $results[] = new PlayerResultDTO(
                playerId: $pid,
                score: $row['score'],
                place: $i + 1,
                average: null,
                dartsThrown: $dartCounts[$pid] ?: null,
                pointsEarned: max(0, $progress),
            );
        }

        $this->matchFinishService->persist($session, $format, $results, $legsWon, $state);
    }

    /**
     * @return array<string, mixed>
     */
    private function broadcastState(QuickGameFfaSession $session, ?int $userId): array
    {
        return FfaStateBroadcaster::emit((int) $session->lobby_id, $this->buildState($session, $userId));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildState(QuickGameFfaSession $session, ?int $userId): array
    {
        $playerIds = array_map('intval', $session->player_order ?? []);
        $atc = $this->normalizeState($session, $playerIds);
        $players = $this->playerRepository->findManyByIds($playerIds)->keyBy('id');
        $format = MatchFormat::fromArray(array_merge(
            MatchFormat::fromRecord($session)->toArray(),
            [
                'gameType' => MatchFormat::GAME_TYPE_ATC,
                'setsToWinMatch' => 1,
            ],
        ));
        $legsWon = $session->legs_won_in_set ?? [];
        $currentIdx = (int) $session->current_player_index;
        $currentPid = (string) ($playerIds[$currentIdx] ?? '');
        $currentBoard = $atc['boards'][$currentPid] ?? AroundTheClockRules::emptyBoard();
        $throwerTarget = (int) ($currentBoard['targetIndex'] ?? 0);

        $playerStates = [];
        foreach ($playerIds as $orderIndex => $playerId) {
            $board = $atc['boards'][(string) $playerId] ?? AroundTheClockRules::emptyBoard();
            $targetIndex = (int) ($board['targetIndex'] ?? 0);
            $player = $players->get($playerId);
            $playerStates[] = [
                'playerId' => (int) $playerId,
                'name' => $player?->name ?? 'Gracz',
                'orderIndex' => $orderIndex,
                'legsWon' => (int) ($legsWon[$playerId] ?? 0),
                'targetIndex' => $targetIndex,
                'targetLabel' => AroundTheClockRules::targetLabel($targetIndex),
                'finished' => (bool) ($board['finished'] ?? false),
            ];
        }

        $view = $this->submitGuard->viewerInput($session, $userId);
        $myPlayerIndex = $view['myPlayerIndex'];
        $canInput = $view['canInput'];

        return [
            'format' => 'ffa_atc',
            'meta' => [
                'kind' => 'quick_ffa_atc',
                'lobbyId' => (int) $session->lobby_id,
            ],
            'session' => [
                'id' => $session->id,
                'lobbyId' => $session->lobby_id,
                'status' => $session->status,
                'legsToWinSet' => $format->legsToWinSet,
                'setsToWinMatch' => 1,
                'matchFormat' => $format->toArray(),
                'gameType' => MatchFormat::GAME_TYPE_ATC,
                'scoringMode' => $session->scoring_mode,
                'currentLegNumber' => (int) $session->current_leg_number,
                'legOpenerIndex' => (int) $session->leg_opener_index,
                'currentPlayerIndex' => (int) $session->current_player_index,
                'currentTargetIndex' => $throwerTarget,
                'currentTargetLabel' => AroundTheClockRules::targetLabel($throwerTarget),
                'stateVersion' => (int) $session->state_version,
                'quickGameId' => $session->quick_game_id,
            ],
            'players' => $playerStates,
            'turn' => [
                'currentPlayerIndex' => (int) $session->current_player_index,
                'legOpenerIndex' => (int) $session->leg_opener_index,
                'currentTargetIndex' => $throwerTarget,
                'currentTargetLabel' => AroundTheClockRules::targetLabel($throwerTarget),
                'maxHits' => AroundTheClockRules::maxHits($throwerTarget),
            ],
            'you' => [
                'canInput' => $canInput && $session->isInProgress(),
                'myPlayerIndex' => $myPlayerIndex,
            ],
            'game' => [
                'status' => $session->status === QuickGameFfaSession::STATUS_FINISHED
                    ? 'finished'
                    : 'in_progress',
            ],
        ];
    }

    private function assertAtcSession(QuickGameFfaSession $session): void
    {
        if (strtolower((string) $session->game_type) !== MatchFormat::GAME_TYPE_ATC) {
            throw new DomainException('To nie jest sesja Around the Clock.');
        }
    }

    /**
     * @param  list<int>  $playerIds
     * @return array<string, mixed>
     */
    private function normalizeState(QuickGameFfaSession $session, array $playerIds): array
    {
        $raw = $session->atc_state;
        if (! is_array($raw) || ! isset($raw['boards'])) {
            return AroundTheClockRules::initialState($playerIds);
        }

        $boards = $raw['boards'];
        foreach ($playerIds as $pid) {
            $key = (string) $pid;
            if (! isset($boards[$key])) {
                $boards[$key] = AroundTheClockRules::emptyBoard();
            }
        }

        return [
            'boards' => $boards,
            'dartLog' => is_array($raw['dartLog'] ?? null) ? $raw['dartLog'] : [],
            'matchLog' => is_array($raw['matchLog'] ?? null) ? $raw['matchLog'] : [],
        ];
    }

    /**
     * @param  list<int>  $playerIds
     * @param  list<int>  $skipIds
     */
    private function normalizeTurnIndices(QuickGameFfaSession $session, array $playerIds, array $skipIds): void
    {
        FfaTurnNormalize::apply($session, $playerIds, $skipIds);
    }
}
