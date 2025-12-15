<?php

namespace App\DTO;

class GameResultDTO
{

    public function __construct(
        public int $gameId,
        public int $player1Id,
        public int $player2Id,
        public int $player1Score,
        public int $player2Score,
        public int $winnerId,
        public int $tournamentId,
        public int $groupNumber
    )
    {
    }

    public static function fromArray(array $data): GameResultDTO
    {
        return new self(
            gameId: $data['id'],
            player1Id: $data['player1Id'],
            player2Id: $data['player2Id'],
            player1Score: $data['player1Score'],
            player2Score: $data['player2Score'],
            winnerId: $data['winnerId'],
            tournamentId: $data['tournamentId'],
            groupNumber: $data['groupNumber']
        );
    }
}
