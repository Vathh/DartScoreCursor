<?php

namespace App\Support\Match;

use App\Enums\MatchKind;
use App\Models\Game\Game;
use App\Models\PlayoffGame\PlayoffGame;
use App\Models\QuickGame\QuickGame;
use DomainException;

readonly class MatchContext
{
    public function __construct(
        public MatchKind $kind,
        public int $matchId,
        public int $player1Id,
        public int $player2Id,
        public ?int $tournamentId,
        public int $legsToWin,
        public int $startingScore = 501,
    ) {
    }

    public static function fromGroupGame(Game $game): self
    {
        return new self(
            kind: MatchKind::GROUP,
            matchId: $game->id,
            player1Id: (int) $game->player1_id,
            player2Id: (int) $game->player2_id,
            tournamentId: (int) $game->tournament_id,
            legsToWin: 2,
        );
    }

    public static function fromPlayoffGame(PlayoffGame $game): self
    {
        if (! $game->player1_id || ! $game->player2_id) {
            throw new DomainException('Mecz playoff nie ma przypisanych graczy.');
        }

        return new self(
            kind: MatchKind::PLAYOFF,
            matchId: $game->id,
            player1Id: (int) $game->player1_id,
            player2Id: (int) $game->player2_id,
            tournamentId: (int) $game->tournament_id,
            legsToWin: 2,
        );
    }

    public static function fromQuickGame(QuickGame $game): self
    {
        return new self(
            kind: MatchKind::QUICK,
            matchId: $game->id,
            player1Id: (int) $game->player1_id,
            player2Id: (int) $game->player2_id,
            tournamentId: null,
            legsToWin: max(1, (int) ($game->legs_count ?? 3)),
        );
    }

    public function broadcastChannelName(): string
    {
        return match ($this->kind) {
            MatchKind::GROUP => 'group-game.'.$this->matchId,
            MatchKind::PLAYOFF => 'playoff-game.'.$this->matchId,
            MatchKind::QUICK => 'quick-game.'.$this->matchId,
        };
    }

    public function otherPlayerId(int $playerId): int
    {
        return $playerId === $this->player1Id ? $this->player2Id : $this->player1Id;
    }
}
