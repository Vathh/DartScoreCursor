<?php

namespace App\Services\QuickGame;

use App\Domain\GameScoring\MatchFormat;
use App\Domain\QuickGame\CricketRules;
use App\Domain\QuickGame\FfaLegCycle;
use App\Domain\QuickGame\FfaMatchLog;
use App\Domain\QuickGame\FfaTurnRotationDomain;
use App\DTO\QuickGame\PlayerResultDTO;
use App\Models\QuickGame\QuickGameFfaSession;
use App\Repositories\Player\PlayerRepository;
use App\Repositories\QuickGame\QuickGameFfaPresenceRepository;
use App\Repositories\QuickGame\QuickGameFfaSessionRepository;
use App\Support\QuickGameFfa\FfaStateBroadcaster;
use App\Support\QuickGameFfa\FfaTurnNormalize;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Scoring cricket FFA — osobny kontrakt od wizyt X01.
 */
class QuickGameFfaCricketScoringService
{
    public function __construct(
        private QuickGameFfaSessionRepository $sessionRepository,
        private QuickGameFfaPresenceRepository $presenceRepository,
        private PlayerRepository $playerRepository,
        private FfaMatchFinishService $matchFinishService,
        private FfaSubmitGuard $submitGuard,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getState(int $lobbyId, ?int $userId = null): array
    {
        $session = $this->sessionRepository->findOrFailForLobby($lobbyId);
        $session->loadMissing('lobby');
        $this->assertCricketSession($session);

        return $this->buildState($session, $userId);
    }

    /**
     * Wizyta krykieta: 3 lotki albo wcześniejsze zamknięcie lega. Jeden zapis, jeden event WS.
     *
     * @param  list<array{kind: string, segment?: string|null, multiplier?: int, clientDartId?: string|null}>  $darts
     * @return array<string, mixed>
     */
    public function recordVisit(
        int $lobbyId,
        int $userId,
        int $playerId,
        array $darts,
        string $clientVisitId,
    ): array {
        return DB::transaction(function () use ($lobbyId, $userId, $playerId, $darts, $clientVisitId) {
            [$session, $playerIds, $leftIds, $state, $playerIndex] = $this->beginCricketWrite(
                $lobbyId,
                $userId,
                $playerId,
            );

            foreach ($state['dartLog'] as $entry) {
                if (($entry['clientVisitId'] ?? null) === $clientVisitId) {
                    return $this->broadcastState($session->fresh(), $userId);
                }
            }

            if ($darts === [] || count($darts) > 3) {
                throw new DomainException('Wizyta krykieta musi mieć od 1 do 3 rzutów.');
            }

            foreach ($darts as $dart) {
                if (! $session->isInProgress()) {
                    break;
                }
                $kind = (string) ($dart['kind'] ?? '');
                $this->applyDartToState(
                    $session,
                    $state,
                    $playerIds,
                    $leftIds,
                    $playerId,
                    $playerIndex,
                    $kind,
                    $dart['segment'] ?? null,
                    (int) ($dart['multiplier'] ?? 1),
                    (string) ($dart['clientDartId'] ?? $clientVisitId),
                    $clientVisitId,
                );
                if ((int) ($state['dartsInVisit'] ?? 0) === 0) {
                    break;
                }
            }

            if ($session->isInProgress() && (int) ($state['dartsInVisit'] ?? 0) !== 0) {
                throw new DomainException('Wizyta krykieta musi mieć 3 rzuty albo zakończyć lega.');
            }

            $session->cricket_state = $state;
            $this->sessionRepository->incrementVersion($session);
            $this->sessionRepository->save($session);

            return $this->broadcastState($session->fresh(), $userId);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function recordDart(
        int $lobbyId,
        int $userId,
        int $playerId,
        string $kind,
        ?string $segment,
        int $multiplier,
        string $clientDartId,
    ): array {
        return DB::transaction(function () use ($lobbyId, $userId, $playerId, $kind, $segment, $multiplier, $clientDartId) {
            [$session, $playerIds, $leftIds, $state, $playerIndex] = $this->beginCricketWrite(
                $lobbyId,
                $userId,
                $playerId,
            );

            foreach ($state['dartLog'] as $entry) {
                if (($entry['clientDartId'] ?? null) === $clientDartId) {
                    return $this->broadcastState($session->fresh(), $userId);
                }
            }

            $this->applyDartToState(
                $session,
                $state,
                $playerIds,
                $leftIds,
                $playerId,
                $playerIndex,
                $kind,
                $segment,
                $multiplier,
                $clientDartId,
                $clientDartId,
            );

            $session->cricket_state = $state;
            $this->sessionRepository->incrementVersion($session);
            $this->sessionRepository->save($session);

            return $this->broadcastState($session->fresh(), $userId);
        });
    }

    /**
     * Cofa ostatnią wizytę (wszystkie lotki z tym samym clientVisitId) albo pojedynczy rzut legacy.
     *
     * @return array<string, mixed>
     */
    public function undoLastDart(int $lobbyId, int $userId): array
    {
        return DB::transaction(function () use ($lobbyId, $userId) {
            $session = $this->sessionRepository->findOrFailForLobby($lobbyId);
            $session->loadMissing('lobby');
            $this->assertCricketSession($session);

            if (! $session->isInProgress()) {
                throw new DomainException('Mecz jest już zakończony.');
            }

            $this->submitGuard->assert($session, $userId, null);

            $playerIds = array_map('intval', $session->player_order ?? []);
            $state = $this->normalizeCricketState($session, $playerIds);
            if ($state['dartLog'] === []) {
                throw new DomainException('Brak rzutu do cofnięcia.');
            }

            $last = $state['dartLog'][count($state['dartLog']) - 1];
            $visitId = $last['clientVisitId'] ?? null;
            $restore = $last;
            if (is_string($visitId) && $visitId !== '') {
                while ($state['dartLog'] !== []) {
                    $peek = $state['dartLog'][count($state['dartLog']) - 1];
                    if (($peek['clientVisitId'] ?? null) !== $visitId) {
                        break;
                    }
                    $restore = array_pop($state['dartLog']);
                }
            } else {
                $restore = array_pop($state['dartLog']);
            }

            $state['boards'] = $restore['boardsSnapshot'] ?? $state['boards'];
            $state['dartsInVisit'] = (int) ($restore['dartsInVisitBefore'] ?? 0);
            $session->legs_won_in_set = $restore['legsWonSnapshot'] ?? $session->legs_won_in_set;
            $session->current_leg_number = (int) ($restore['legNumber'] ?? $session->current_leg_number);
            $session->leg_opener_index = (int) ($restore['legOpenerIndex'] ?? $session->leg_opener_index);
            $session->current_player_index = (int) ($restore['currentPlayerIndex'] ?? $session->current_player_index);
            $session->cricket_state = $state;

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
    private function advanceAfterDart(
        QuickGameFfaSession $session,
        array &$state,
        array $playerIds,
        array $leftIds,
        ?int $legWinnerIndex,
    ): void {
        if ($legWinnerIndex !== null) {
            $winnerId = (int) $playerIds[$legWinnerIndex];
            $legsWon = $session->legs_won_in_set ?? [];
            foreach ($playerIds as $pid) {
                $legsWon[$pid] ??= 0;
            }
            $legsWon[$winnerId] = (int) ($legsWon[$winnerId] ?? 0) + 1;
            $session->legs_won_in_set = $legsWon;

            $format = MatchFormat::fromRecord($session);
            if ((int) $legsWon[$winnerId] >= $format->legsToWinSet) {
                $this->finishMatch($session, $legsWon, $format, $state);
                $state['dartsInVisit'] = 0;
                FfaMatchLog::archive($state);

                return;
            }

            // Nowy leg — reset tablic, rotacja openera
            $state['boards'] = CricketRules::initialState($playerIds)['boards'];
            $state['dartsInVisit'] = 0;
            FfaMatchLog::archive($state);
            FfaLegCycle::startNextLeg($session, $playerIds, $leftIds);

            return;
        }

        $nextDarts = (int) $state['dartsInVisit'] + 1;
        if ($nextDarts >= 3) {
            $state['dartsInVisit'] = 0;
            $session->current_player_index = FfaTurnRotationDomain::nextIndexAfter(
                (int) $session->current_player_index,
                $playerIds,
                $leftIds,
            );
        } else {
            $state['dartsInVisit'] = $nextDarts;
        }
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
        $pointsTotals = [];
        foreach ($playerIds as $pid) {
            $dartCounts[$pid] = 0;
            $pointsTotals[$pid] = 0;
        }
        foreach ($state['dartLog'] ?? [] as $entry) {
            $pid = (int) ($entry['playerId'] ?? 0);
            if (! isset($dartCounts[$pid])) {
                continue;
            }
            $dartCounts[$pid]++;
            $pointsTotals[$pid] += (int) ($entry['pointsScored'] ?? 0);
        }
        // + punkty z bieżącej tablicy (ostatni leg)
        foreach ($playerIds as $pid) {
            $pointsTotals[$pid] = max(
                $pointsTotals[$pid],
                (int) ($state['boards'][(string) $pid]['points'] ?? 0),
            );
        }

        $results = [];
        foreach ($ranked as $i => $row) {
            $pid = $row['playerId'];
            $darts = $dartCounts[$pid] ?: null;
            $pts = $pointsTotals[$pid] ?: null;
            $results[] = new PlayerResultDTO(
                playerId: $pid,
                score: $row['score'],
                place: $i + 1,
                average: null,
                dartsThrown: $darts,
                pointsEarned: $pts,
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
        $cricket = $this->normalizeCricketState($session, $playerIds);
        $players = $this->playerRepository->findManyByIds($playerIds)->keyBy('id');
        $format = MatchFormat::fromRecord($session);
        $legsWon = $session->legs_won_in_set ?? [];

        $playerStates = [];
        foreach ($playerIds as $orderIndex => $playerId) {
            $board = $cricket['boards'][(string) $playerId] ?? [
                'hits' => CricketRules::emptyHits(),
                'points' => 0,
            ];
            $player = $players->get($playerId);
            $playerStates[] = [
                'playerId' => (int) $playerId,
                'name' => $player?->name ?? 'Gracz',
                'orderIndex' => $orderIndex,
                'legsWon' => (int) ($legsWon[$playerId] ?? 0),
                'hits' => $board['hits'],
                'points' => (int) ($board['points'] ?? 0),
            ];
        }

        $view = $this->submitGuard->viewerInput($session, $userId);
        $myPlayerIndex = $view['myPlayerIndex'];
        $canInput = $view['canInput'];

        return [
            'format' => 'ffa_cricket',
            'meta' => [
                'kind' => 'quick_ffa_cricket',
                'lobbyId' => (int) $session->lobby_id,
            ],
            'session' => [
                'id' => $session->id,
                'lobbyId' => $session->lobby_id,
                'status' => $session->status,
                'legsToWinSet' => $format->legsToWinSet,
                'setsToWinMatch' => 1,
                'matchFormat' => array_merge($format->toArray(), [
                    'gameType' => 'cricket',
                    'setsToWinMatch' => 1,
                ]),
                'gameType' => 'cricket',
                'scoringMode' => $session->scoring_mode,
                'currentLegNumber' => (int) $session->current_leg_number,
                'legOpenerIndex' => (int) $session->leg_opener_index,
                'currentPlayerIndex' => (int) $session->current_player_index,
                'dartsInVisit' => (int) ($cricket['dartsInVisit'] ?? 0),
                'stateVersion' => (int) $session->state_version,
                'quickGameId' => $session->quick_game_id,
            ],
            'players' => $playerStates,
            'turn' => [
                'currentPlayerIndex' => (int) $session->current_player_index,
                'legOpenerIndex' => (int) $session->leg_opener_index,
                'dartsInVisit' => (int) ($cricket['dartsInVisit'] ?? 0),
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

    /**
     * @return array{0: QuickGameFfaSession, 1: list<int>, 2: list<int>, 3: array<string, mixed>, 4: int}
     */
    private function beginCricketWrite(int $lobbyId, int $userId, int $playerId): array
    {
        $session = $this->sessionRepository->findOrFailForLobby($lobbyId);
        $session->loadMissing('lobby');
        $this->assertCricketSession($session);

        if (! $session->isInProgress()) {
            throw new DomainException('Mecz jest już zakończony.');
        }

        $playerIds = array_map('intval', $session->player_order ?? []);
        $leftIds = $this->presenceRepository->getLeftPlayerIds($session);

        if (! in_array($playerId, $playerIds, true)) {
            throw new DomainException('Gracz nie należy do tego meczu.');
        }
        $this->submitGuard->assert($session, $userId, $playerId);
        $this->normalizeTurnIndices($session, $playerIds, $leftIds);

        $currentPlayerId = (int) $playerIds[(int) $session->current_player_index];
        if ($playerId !== $currentPlayerId) {
            throw new DomainException('Teraz rzuca inny gracz.');
        }

        $playerIndex = array_search($playerId, $playerIds, true);
        if ($playerIndex === false) {
            throw new DomainException('Nieprawidłowy gracz.');
        }

        return [
            $session,
            $playerIds,
            $leftIds,
            $this->normalizeCricketState($session, $playerIds),
            (int) $playerIndex,
        ];
    }

    /**
     * @param  list<int>  $playerIds
     * @param  list<int>  $leftIds
     * @param  array<string, mixed>  $state
     */
    private function applyDartToState(
        QuickGameFfaSession $session,
        array &$state,
        array $playerIds,
        array $leftIds,
        int $playerId,
        int $playerIndex,
        string $kind,
        mixed $segment,
        int $multiplier,
        string $clientDartId,
        string $clientVisitId,
    ): void {
        if ($kind === 'miss') {
            $state['dartLog'][] = [
                'playerId' => $playerId,
                'kind' => 'miss',
                'dartsInVisitBefore' => (int) $state['dartsInVisit'],
                'clientDartId' => $clientDartId,
                'clientVisitId' => $clientVisitId,
                'legNumber' => (int) $session->current_leg_number,
                'legOpenerIndex' => (int) $session->leg_opener_index,
                'currentPlayerIndex' => (int) $session->current_player_index,
                'boardsSnapshot' => $state['boards'],
                'legsWonSnapshot' => $session->legs_won_in_set,
            ];
            $this->advanceAfterDart($session, $state, $playerIds, $leftIds, null);

            return;
        }

        if ($segment === null || ! CricketRules::isValidSegment($segment)) {
            throw new DomainException('Nieprawidłowy segment.');
        }
        $mult = max(1, min(3, $multiplier));
        if (CricketRules::segmentKey($segment) === 'bull' && $mult > 2) {
            throw new DomainException('Bull nie ma triple.');
        }

        $hitsList = [];
        foreach ($playerIds as $i => $pid) {
            $hitsList[$i] = $state['boards'][(string) $pid]['hits'] ?? CricketRules::emptyHits();
        }

        $applied = CricketRules::applyDart($hitsList, $playerIndex, $segment, $mult);
        $pidKey = (string) $playerId;
        $pointsBefore = (int) ($state['boards'][$pidKey]['points'] ?? 0);

        $state['dartLog'][] = [
            'playerId' => $playerId,
            'kind' => 'hit',
            'segment' => CricketRules::segmentKey($segment),
            'multiplier' => $mult,
            'pointsScored' => $applied['pointsScored'],
            'hitsBefore' => $state['boards'][$pidKey]['hits'],
            'pointsBefore' => $pointsBefore,
            'dartsInVisitBefore' => (int) $state['dartsInVisit'],
            'clientDartId' => $clientDartId,
            'clientVisitId' => $clientVisitId,
            'legNumber' => (int) $session->current_leg_number,
            'legOpenerIndex' => (int) $session->leg_opener_index,
            'currentPlayerIndex' => (int) $session->current_player_index,
            'boardsSnapshot' => $state['boards'],
            'legsWonSnapshot' => $session->legs_won_in_set,
        ];

        $state['boards'][$pidKey] = [
            'hits' => $applied['hits'],
            'points' => $pointsBefore + $applied['pointsScored'],
        ];

        $boardsForWin = $this->boardsList($state, $playerIds);
        $winnerIdx = CricketRules::findLegWinnerIndex($boardsForWin);
        $this->advanceAfterDart($session, $state, $playerIds, $leftIds, $winnerIdx);
    }

    private function assertCricketSession(QuickGameFfaSession $session): void
    {
        if (strtolower((string) $session->game_type) !== 'cricket') {
            throw new DomainException('To nie jest sesja cricket.');
        }
    }

    /**
     * @param  list<int>  $playerIds
     * @return array<string, mixed>
     */
    private function normalizeCricketState(QuickGameFfaSession $session, array $playerIds): array
    {
        $raw = $session->cricket_state;
        if (! is_array($raw) || ! isset($raw['boards'])) {
            return CricketRules::initialState($playerIds);
        }

        $boards = $raw['boards'];
        foreach ($playerIds as $pid) {
            $key = (string) $pid;
            if (! isset($boards[$key])) {
                $boards[$key] = [
                    'hits' => CricketRules::emptyHits(),
                    'points' => 0,
                ];
            }
        }

        return [
            'boards' => $boards,
            'dartsInVisit' => (int) ($raw['dartsInVisit'] ?? 0),
            'dartLog' => is_array($raw['dartLog'] ?? null) ? $raw['dartLog'] : [],
            'matchLog' => is_array($raw['matchLog'] ?? null) ? $raw['matchLog'] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<int>  $playerIds
     * @return list<array{hits: array<string, int>, points: int}>
     */
    private function boardsList(array $state, array $playerIds): array
    {
        $list = [];
        foreach ($playerIds as $pid) {
            $b = $state['boards'][(string) $pid] ?? null;
            $list[] = [
                'hits' => $b['hits'] ?? CricketRules::emptyHits(),
                'points' => (int) ($b['points'] ?? 0),
            ];
        }

        return $list;
    }

    /**
     * @param  list<int>  $playerIds
     * @param  list<int>  $leftIds
     */
    private function normalizeTurnIndices(QuickGameFfaSession $session, array $playerIds, array $leftIds): void
    {
        FfaTurnNormalize::apply($session, $playerIds, $leftIds);
    }
}
