<?php

namespace App\Domain\Game;

use App\Domain\PlayerDomain;
use App\Domain\Tournament\TournamentDomain;
use App\Enums\GameStatus;
use App\Models\Game;
use DomainException;

class GameDomain
{

    /**
     * @param int $id
     * @param TournamentDomain|null $tournament
     * @param PlayerDomain|null $player1
     * @param PlayerDomain|null $player2
     * @param int $player1Score
     * @param int $player2Score
     * @param PlayerDomain|null $winner
     * @param int $groupNumber
     * @param GameStatus $status
     */
    public function __construct(
        public readonly int $id,
        public readonly ?TournamentDomain $tournament,
        public readonly ?PlayerDomain $player1,
        public readonly ?PlayerDomain $player2,
        public readonly int $player1Score,
        public readonly int $player2Score,
        public readonly ?PlayerDomain $winner,
        public readonly int $groupNumber,
        public readonly GameStatus $status
    )
    {
    }

    /**
     * @param Game $game
     * @param array $with
     * @return GameDomain
     */
    public static function fromEloquent(Game $game, array $with = []): GameDomain
    {
        $game->loadMissing(array_intersect($with, ['tournament', 'player1', 'player2', 'winner']));

        return new self(
            id: $game->id,
            tournament: in_array('tournament', $with)
                ? TournamentDomain::fromEloquent($game->tournament)
                : null,
            player1: in_array('player1', $with)
                ? PlayerDomain::fromEloquent($game->player1)
                : null,
            player2: in_array('player2', $with)
                ? PlayerDomain::fromEloquent($game->player2)
                : null,
            player1Score: $game->player1_score,
            player2Score: $game->player2_score,
            winner: in_array('winner', $with) && $game->winner
                ? PlayerDomain::fromEloquent($game->winner)
                : null,
            groupNumber: $game->group_number,
            status: $game->status
        );
    }

    /**
     * @return array
     */
    public function playerIds(): array
    {
        return [$this->player1->id, $this->player2->id];
    }

    public function isFinished(): bool
    {
        return $this->status === GameStatus::FINISHED;
    }

    public function checkUpdateDataAccuracy(int $player1Id,
                                            int $player2Id,
                                            int $winnerId): void
    {
        if( $player1Id !== $this->player1->id ||
            $player2Id !== $this->player2->id)
        {
            throw new DomainException('Nieprawidłowe id graczy.');
        }

        if( !in_array($winnerId, [$this->player1->id, $this->player2->id]) )
        {
            throw new DomainException('Id zwycięzcy nieprawidłowe');
        }

        if($this->status === GameStatus::FINISHED)
        {
            throw new DomainException('Mecz został już ukończony.');
        }
    }
}
