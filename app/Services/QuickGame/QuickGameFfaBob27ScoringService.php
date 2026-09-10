<?php

namespace App\Services\QuickGame;

use App\DTO\QuickGame\PlayerResultDTO;
use App\Domain\GameScoring\MatchFormat;
use App\Domain\QuickGame\Bob27Rules;
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
 * Scoring Bob's 27 FFA — osobny kontrakt od wizyt X01 i cricket.
 */
class QuickGameFfaBob27ScoringService
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
        $this->assertBob27Session($session);

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
            $this->assertBob27Session($session);

            if (! $session->isInProgress()) {
                throw new DomainException('Mecz jest już zakończony.');
            }

            $hits = max(0, min(3, $hits));
            $playerIds = array_map('intval', $session->player_order ?? []);
            $leftIds = $this->presenceRepository->getLeftPlayerIds($session);
            $state = $this->normalizeState($session, $playerIds);
            $skipIds = $this->skipIds($playerIds, $leftIds, $state);

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

            $state['dartLog'][] = [
                'playerId' => $playerId,
                'kind' => 'visit',
                'hits' => $hits,
                'dartsInVisitBefore' => (int) $state['dartsInVisit'],
                'hitsInVisitBefore' => (int) $state['hitsInVisit'],
                'clientDartId' => $clientVisitId,
                'clientVisitId' => $clientVisitId,
                'legNumber' => (int) $session->current_leg_number,
                'legOpenerIndex' => (int) $session->leg_opener_index,
                'currentPlayerIndex' => (int) $session->current_player_index,
                'currentTargetIndex' => (int) $state['currentTargetIndex'],
                'thrownThisTarget' => $state['thrownThisTarget'],
                'boardsSnapshot' => $state['boards'],
                'legsWonSnapshot' => $session->legs_won_in_set,
            ];

            $state['hitsInVisit'] = $hits;
            $this->applyCompletedVisit($session, $state, $playerIds, $leftIds, $playerIndex);

            $session->bob27_state = $state;
            $this->sessionRepository->incrementVersion($session);
            $this->sessionRepository->save($session);

            return $this->broadcastState($session->fresh(), $userId);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function undoLastDart(int $lobbyId, int $userId): array
    {
        return DB::transaction(function () use ($lobbyId, $userId) {
            $session = $this->sessionRepository->findOrFailForLobby($lobbyId);
            $session->loadMissing('lobby');
            $this->assertBob27Session($session);

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
            $state['dartsInVisit'] = (int) ($last['dartsInVisitBefore'] ?? 0);
            $state['hitsInVisit'] = (int) ($last['hitsInVisitBefore'] ?? 0);
            $state['currentTargetIndex'] = (int) ($last['currentTargetIndex'] ?? 0);
            $state['thrownThisTarget'] = is_array($last['thrownThisTarget'] ?? null)
                ? $last['thrownThisTarget']
                : [];
            $session->legs_won_in_set = $last['legsWonSnapshot'] ?? $session->legs_won_in_set;
            $session->current_leg_number = (int) ($last['legNumber'] ?? $session->current_leg_number);
            $session->leg_opener_index = (int) ($last['legOpenerIndex'] ?? $session->leg_opener_index);
            $session->current_player_index = (int) ($last['currentPlayerIndex'] ?? $session->current_player_index);
            $session->bob27_state = $state;

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
    private function applyCompletedVisit(
        QuickGameFfaSession $session,
        array &$state,
        array $playerIds,
        array $leftIds,
        int $playerIndex,
    ): void {
        $pidKey = (string) $playerIds[$playerIndex];
        $board = $state['boards'][$pidKey] ?? Bob27Rules::emptyBoard();
        $scoreAfter = Bob27Rules::applyVisit(
            (int) ($board['score'] ?? Bob27Rules::STARTING_SCORE),
            (int) $state['hitsInVisit'],
            (int) $state['currentTargetIndex'],
            $this->includeBull($state),
        );
        $eliminated = Bob27Rules::shouldEliminate($scoreAfter, (string) $state['mode']);
        $state['boards'][$pidKey] = [
            'score' => $scoreAfter,
            'eliminated' => $eliminated,
        ];

        $thrown = $state['thrownThisTarget'];
        $thrown[$playerIndex] = true;
        $state['thrownThisTarget'] = $thrown;

        $boardsList = $this->boardsList($state, $playerIds);
        $leftIndices = $this->leftIndices($playerIds, $leftIds);
        $outcome = Bob27Rules::resolveAfterCompletedVisit(
            $boardsList,
            (string) $state['mode'],
            (int) $state['currentTargetIndex'],
            $thrown,
            $leftIndices,
            $this->includeBull($state),
        );

        if ($outcome['kind'] === Bob27Rules::KIND_WIN) {
            $this->closeLeg($session, $state, $playerIds, $leftIds, (int) $outcome['winnerIndex']);

            return;
        }

        if ($outcome['kind'] === Bob27Rules::KIND_BUST) {
            $this->finishMatch($session, $session->legs_won_in_set ?? [], MatchFormat::fromRecord($session), $state);
            $state['dartsInVisit'] = 0;
            $state['hitsInVisit'] = 0;
            FfaMatchLog::archive($state);

            return;
        }

        if ($outcome['kind'] === Bob27Rules::KIND_TIE_RESET) {
            $this->resetBoard($state, $playerIds);
            $state['dartsInVisit'] = 0;
            $state['hitsInVisit'] = 0;
            $session->current_player_index = FfaTurnRotationDomain::normalizeIndexAt(
                (int) $session->leg_opener_index,
                $playerIds,
                $this->skipIds($playerIds, $leftIds, $state),
            );

            return;
        }

        $allThrown = Bob27Rules::allActiveHaveThrown($boardsList, $thrown, $leftIndices);
        if ($allThrown) {
            $state['currentTargetIndex'] = (int) $state['currentTargetIndex'] + 1;
            $state['thrownThisTarget'] = [];
        }

        $state['dartsInVisit'] = 0;
        $state['hitsInVisit'] = 0;
        $skipIds = $this->skipIds($playerIds, $leftIds, $state);
        $session->current_player_index = FfaTurnRotationDomain::nextIndexAfter(
            (int) $session->current_player_index,
            $playerIds,
            $skipIds,
        );
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
            [
                'gameType' => MatchFormat::GAME_TYPE_BOB27,
                'bob27Mode' => $state['mode'],
                'bob27Bull' => $this->bob27BullValue($state),
            ],
        ));
        if ((int) $legsWon[$winnerId] >= $format->legsToWinSet) {
            $this->finishMatch($session, $legsWon, $format, $state);
            $state['dartsInVisit'] = 0;
            $state['hitsInVisit'] = 0;
            FfaMatchLog::archive($state);

            return;
        }

        $this->resetBoard($state, $playerIds);
        FfaMatchLog::archive($state);
        $skipIds = $this->skipIds($playerIds, $leftIds, $state);
        FfaLegCycle::startNextLeg($session, $playerIds, $leftIds, $skipIds);
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<int>  $playerIds
     */
    private function resetBoard(array &$state, array $playerIds): void
    {
        $fresh = Bob27Rules::initialState(
            $playerIds,
            (string) $state['mode'],
            $this->includeBull($state),
        );
        $state['boards'] = $fresh['boards'];
        $state['currentTargetIndex'] = 0;
        $state['dartsInVisit'] = 0;
        $state['hitsInVisit'] = 0;
        $state['thrownThisTarget'] = [];
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
            $dartCounts[$pid] += ($entry['kind'] ?? '') === 'visit' ? 3 : 1;
        }

        $results = [];
        foreach ($ranked as $i => $row) {
            $pid = $row['playerId'];
            $pts = (int) ($state['boards'][(string) $pid]['score'] ?? 0);
            $results[] = new PlayerResultDTO(
                playerId: $pid,
                score: $row['score'],
                place: $i + 1,
                average: null,
                dartsThrown: $dartCounts[$pid] ?: null,
                pointsEarned: max(0, $pts),
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
        $bob = $this->normalizeState($session, $playerIds);
        $players = $this->playerRepository->findManyByIds($playerIds)->keyBy('id');
        $format = MatchFormat::fromArray(array_merge(
            MatchFormat::fromRecord($session)->toArray(),
            [
                'gameType' => MatchFormat::GAME_TYPE_BOB27,
                'bob27Mode' => $bob['mode'],
                'bob27Bull' => $this->bob27BullValue($bob),
                'setsToWinMatch' => 1,
            ],
        ));
        $legsWon = $session->legs_won_in_set ?? [];
        $targetIndex = (int) $bob['currentTargetIndex'];

        $playerStates = [];
        foreach ($playerIds as $orderIndex => $playerId) {
            $board = $bob['boards'][(string) $playerId] ?? Bob27Rules::emptyBoard();
            $player = $players->get($playerId);
            $playerStates[] = [
                'playerId' => (int) $playerId,
                'name' => $player?->name ?? 'Gracz',
                'orderIndex' => $orderIndex,
                'legsWon' => (int) ($legsWon[$playerId] ?? 0),
                'score' => (int) ($board['score'] ?? Bob27Rules::STARTING_SCORE),
                'eliminated' => (bool) ($board['eliminated'] ?? false),
            ];
        }

        $view = $this->submitGuard->viewerInput($session, $userId);
        $myPlayerIndex = $view['myPlayerIndex'];
        $canInput = $view['canInput'];

        return [
            'format' => 'ffa_bob27',
            'meta' => [
                'kind' => 'quick_ffa_bob27',
                'lobbyId' => (int) $session->lobby_id,
            ],
            'session' => [
                'id' => $session->id,
                'lobbyId' => $session->lobby_id,
                'status' => $session->status,
                'legsToWinSet' => $format->legsToWinSet,
                'setsToWinMatch' => 1,
                'matchFormat' => $format->toArray(),
                'gameType' => MatchFormat::GAME_TYPE_BOB27,
                'bob27Mode' => $bob['mode'],
                'bob27Bull' => $this->bob27BullValue($bob),
                'includeBull' => $this->includeBull($bob),
                'scoringMode' => $session->scoring_mode,
                'currentLegNumber' => (int) $session->current_leg_number,
                'legOpenerIndex' => (int) $session->leg_opener_index,
                'currentPlayerIndex' => (int) $session->current_player_index,
                'currentTargetIndex' => $targetIndex,
                'currentTargetLabel' => Bob27Rules::targetLabel($targetIndex, $this->includeBull($bob)),
                'currentTargetValue' => Bob27Rules::targetValue($targetIndex, $this->includeBull($bob)),
                'dartsInVisit' => (int) ($bob['dartsInVisit'] ?? 0),
                'hitsInVisit' => (int) ($bob['hitsInVisit'] ?? 0),
                'stateVersion' => (int) $session->state_version,
                'quickGameId' => $session->quick_game_id,
            ],
            'players' => $playerStates,
            'turn' => [
                'currentPlayerIndex' => (int) $session->current_player_index,
                'legOpenerIndex' => (int) $session->leg_opener_index,
                'dartsInVisit' => (int) ($bob['dartsInVisit'] ?? 0),
                'hitsInVisit' => (int) ($bob['hitsInVisit'] ?? 0),
                'currentTargetIndex' => $targetIndex,
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

    private function assertBob27Session(QuickGameFfaSession $session): void
    {
        if (strtolower((string) $session->game_type) !== MatchFormat::GAME_TYPE_BOB27) {
            throw new DomainException('To nie jest sesja Bob\'s 27.');
        }
    }

    /**
     * @param  list<int>  $playerIds
     * @return array<string, mixed>
     */
    private function normalizeState(QuickGameFfaSession $session, array $playerIds): array
    {
        $raw = $session->bob27_state;
        $mode = is_array($raw) ? Bob27Rules::normalizeMode((string) ($raw['mode'] ?? Bob27Rules::MODE_HARD)) : Bob27Rules::MODE_HARD;
        $includeBull = is_array($raw) ? (bool) ($raw['includeBull'] ?? true) : true;
        if (! is_array($raw) || ! isset($raw['boards'])) {
            return Bob27Rules::initialState($playerIds, $mode, $includeBull);
        }

        $boards = $raw['boards'];
        foreach ($playerIds as $pid) {
            $key = (string) $pid;
            if (! isset($boards[$key])) {
                $boards[$key] = Bob27Rules::emptyBoard();
            }
        }

        return [
            'mode' => $mode,
            'includeBull' => $includeBull,
            'currentTargetIndex' => (int) ($raw['currentTargetIndex'] ?? 0),
            'dartsInVisit' => (int) ($raw['dartsInVisit'] ?? 0),
            'hitsInVisit' => (int) ($raw['hitsInVisit'] ?? 0),
            'thrownThisTarget' => is_array($raw['thrownThisTarget'] ?? null) ? $raw['thrownThisTarget'] : [],
            'boards' => $boards,
            'dartLog' => is_array($raw['dartLog'] ?? null) ? $raw['dartLog'] : [],
            'matchLog' => is_array($raw['matchLog'] ?? null) ? $raw['matchLog'] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function includeBull(array $state): bool
    {
        return (bool) ($state['includeBull'] ?? true);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function bob27BullValue(array $state): string
    {
        return $this->includeBull($state)
            ? MatchFormat::BOB27_BULL_WITH
            : MatchFormat::BOB27_BULL_WITHOUT;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<int>  $playerIds
     * @return list<array{score: int, eliminated: bool}>
     */
    private function boardsList(array $state, array $playerIds): array
    {
        $list = [];
        foreach ($playerIds as $pid) {
            $b = $state['boards'][(string) $pid] ?? Bob27Rules::emptyBoard();
            $list[] = [
                'score' => (int) ($b['score'] ?? Bob27Rules::STARTING_SCORE),
                'eliminated' => (bool) ($b['eliminated'] ?? false),
            ];
        }

        return $list;
    }

    /**
     * @param  list<int>  $playerIds
     * @param  list<int>  $leftIds
     * @return list<int>
     */
    private function leftIndices(array $playerIds, array $leftIds): array
    {
        $out = [];
        foreach ($playerIds as $i => $pid) {
            if (in_array((int) $pid, $leftIds, true)) {
                $out[] = (int) $i;
            }
        }

        return $out;
    }

    /**
     * @param  list<int>  $playerIds
     * @param  list<int>  $leftIds
     * @param  array<string, mixed>  $state
     * @return list<int>
     */
    private function skipIds(array $playerIds, array $leftIds, array $state): array
    {
        $skip = $leftIds;
        foreach ($playerIds as $pid) {
            $board = $state['boards'][(string) $pid] ?? null;
            if (! empty($board['eliminated'])) {
                $skip[] = (int) $pid;
            }
        }

        return array_values(array_unique($skip));
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
