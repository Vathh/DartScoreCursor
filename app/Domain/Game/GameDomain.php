<?php

namespace App\Domain\Game;

use App\Domain\PlayerDomain;
use App\Enums\GameStatus;

/**
 * Abstrakcyjna klasa bazowa dla wszystkich typów gier
 * Zawiera wspólne pola i metody dla GroupGame, PlayoffGame, QuickGame
 */
abstract class GameDomain
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?PlayerDomain $player1,
        public readonly ?PlayerDomain $player2,
        public readonly ?int $player1Score,
        public readonly ?int $player2Score,
        public readonly ?PlayerDomain $winner,
        public readonly GameStatus $status
    ) {}

    /**
     * @return array<int>
     */
    public function playerIds(): array
    {
        return [
            $this->player1?->id ?? 0,
            $this->player2?->id ?? 0,
        ];
    }

    public function isFinished(): bool
    {
        return $this->status === GameStatus::FINISHED;
    }

    /**
     * Sprawdza czy gracze w meczu są poprawne
     *
     * @throws \DomainException
     */
    protected function validatePlayers(int $player1Id, int $player2Id): void
    {
        if ($this->player1?->id !== $player1Id || $this->player2?->id !== $player2Id) {
            throw new \DomainException('Nieprawidłowe id graczy.');
        }
    }

    /**
     * Sprawdza czy zwycięzca jest poprawny
     *
     * @throws \DomainException
     */
    protected function validateWinner(int $winnerId): void
    {
        $playerIds = $this->playerIds();
        if (! in_array($winnerId, $playerIds)) {
            throw new \DomainException('Id zwycięzcy nieprawidłowe');
        }
    }

    /**
     * Sprawdza czy mecz nie został już ukończony
     *
     * @throws \DomainException
     */
    protected function validateNotFinished(): void
    {
        if ($this->status === GameStatus::FINISHED) {
            throw new \DomainException('Mecz został już ukończony.');
        }
    }
}
