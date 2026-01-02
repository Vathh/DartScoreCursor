<?php /** @noinspection PhpParamsInspection */

namespace App\DTO;

use App\Domain\GameDomain;
use App\Models\PlayoffGame;

class ActiveGameDTO
{

    public function __construct(
        public int $id,
        public string $type,
        public array $player1,
        public array $player2,
        public ?int $groupNumber
    )
    {
    }

    public static function fromGame(GameDomain $game)
    {
        return new self(
            id: $game->id,
            type: 'group',
            player1: [
                'id' => $game->player1->id,
                'name' => $game->player1->name,
            ],
            player2: [
                'id' => $game->player2->id,
                'name' => $game->player2->name,
            ],
            groupNumber: $game->groupNumber
        );
    }
    
    public static function fromPlayoffGame(PlayoffGame $game)
    {
        return new self(
            id: $game->id,
            type: 'playoff',
            player1: [
                'id' => $game->player1->id,
                'name' => $game->player1->name,
            ],
            player2: [
                'id' => $game->player2->id,
                'name' => $game->player2->name,
            ]
        );
    }
}
