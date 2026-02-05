<?php

namespace App\Services;

use App\DTO\UpdateGameDTO;
use App\DTO\QuickGame\QuickGameResultDTO;
use App\Domain\Game\QuickGameDomain;
use App\Enums\GameType;
use App\Repositories\QuickGameRepository;
use App\Repositories\PlayerRepository;
use App\Services\AchievementsService;
use App\Services\GameLegService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class QuickGameService
{
    public function __construct(
        private QuickGameRepository $quickGameRepository,
        private PlayerRepository $playerRepository,
        private AchievementsService $achievementsService,
        private GameLegService $gameLegService,
        private PlayerStatsService $playerStatsService
    )
    {
    }

    /**
     * Tworzy szybki mecz między dwoma zarejestrowanymi graczami
     * @param int $player1Id ID gracza 1 (z Player, nie User)
     * @param int $player2Id ID gracza 2 (z Player, nie User)
     * @param int $requestingUserId ID użytkownika który tworzy mecz
     * @return int ID utworzonego meczu
     * @throws \RuntimeException
     */
    public function createQuickGame(int $player1Id, int $player2Id, int $requestingUserId): int
    {
        // Sprawdź czy gracze są zarejestrowani (mają user_id)
        $player1 = $this->playerRepository->findById($player1Id);
        $player2 = $this->playerRepository->findById($player2Id);

        if (!$player1 || !$player1->userId) {
            throw new \RuntimeException('Gracz 1 musi być zarejestrowanym użytkownikiem');
        }

        if (!$player2 || !$player2->userId) {
            throw new \RuntimeException('Gracz 2 musi być zarejestrowanym użytkownikiem');
        }

        // Sprawdź czy użytkownik tworzący mecz jest jednym z graczy
        if ($requestingUserId !== $player1->userId && $requestingUserId !== $player2->userId) {
            throw new \RuntimeException('Możesz tworzyć mecze tylko z własnym udziałem');
        }

        return $this->quickGameRepository->create($player1Id, $player2Id);
    }

    /**
     * Zapisuje wynik szybkiego meczu
     * @param UpdateGameDTO $dto
     * @return bool
     */
    public function updateQuickGame(UpdateGameDTO $dto): bool
    {
        try {
            DB::transaction(function () use ($dto) {
                // Sprawdź czy to szybki mecz
                if ($dto->gameResultDTO->type !== GameType::QUICK_MATCH) {
                    throw new \RuntimeException('To nie jest szybki mecz');
                }

                // Pobierz mecz i sprawdź poprawność danych
                $quickGame = $this->quickGameRepository->find($dto->gameResultDTO->gameId);
                $quickGame->checkUpdateDataAccuracy(
                    $dto->gameResultDTO->player1Id,
                    $dto->gameResultDTO->player2Id,
                    $dto->gameResultDTO->winnerId
                );

                // Zakończ mecz
                $this->quickGameRepository->finish($dto->gameResultDTO);

                // Zapisz achievementy
                $this->achievementsService->createMany($dto->achievementsDTOs);

                // Zapisz szczegóły legów jeśli są dostępne
                if (!empty($dto->legsDTOs)) {
                    $this->gameLegService->createMany(
                        $dto->legsDTOs,
                        gameId: null,
                        playoffGameId: null,
                        quickGameId: $dto->gameResultDTO->gameId
                    );
                }
            });

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Pobiera aktywne szybkie mecze użytkownika
     * @param int $userId
     * @return Collection<int, QuickGameDomain>
     */
    public function getActiveForUser(int $userId): Collection
    {
        return $this->quickGameRepository->getActiveForUser($userId);
    }

    /**
     * Ustawia status szybkiego meczu na "w trakcie"
     * @param int $gameId
     * @return void
     */
    public function setStatusInProgress(int $gameId): void
    {
        $this->quickGameRepository->setStatusInProgress($gameId);
    }

    /**
     * Zapisuje wyniki szybkiego meczu (nowy format - lista graczy)
     * @param QuickGameResultDTO $dto
     * @param int|null $lobbyId
     * @return bool
     */
    public function updateResults(QuickGameResultDTO $dto, ?int $lobbyId = null): bool
    {
        try {
            // Walidacja: musi być co najmniej 2 zarejestrowanych graczy
            if (count($dto->players) < 2) {
                throw new \RuntimeException('Musi być co najmniej 2 zarejestrowanych graczy');
            }

            DB::transaction(function () use ($dto, $lobbyId) {
                // Pobierz ID graczy
                $playerIds = array_map(fn($player) => $player->playerId, $dto->players);

                // Utwórz QuickGame
                $quickGameId = $this->quickGameRepository->createWithResults($playerIds, $lobbyId);

                // Zapisz wyniki graczy
                $this->quickGameRepository->saveResults($quickGameId, $dto->players);

                // Zapisz achievementy (tylko dla zarejestrowanych graczy)
                if (!empty($dto->achievements)) {
                    $this->achievementsService->createMany($dto->achievements);
                }

                // TODO: Zapisz szczegóły legów jeśli są dostępne
                // Na razie GameLegDTO jest dla 2 graczy, więc trzeba będzie rozszerzyć
                // if (!empty($dto->legs)) {
                //     $this->gameLegService->createMany(...);
                // }

                // Aktualizuj cache statystyk dla zarejestrowanych graczy
                $playerIds = array_unique(array_map(fn($p) => $p->playerId, $dto->players));
                foreach ($playerIds as $playerId) {
                    $player = $this->playerRepository->findById($playerId);
                    if ($player !== null && $player->userId !== null) {
                        $this->playerStatsService->recalculateAndSave($player->id);
                    }
                }
            });

            return true;
        } catch (Throwable $e) {
            \Log::error('Quick game results update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
