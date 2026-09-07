<?php

namespace App\Domain\Badge;

use App\Enums\GameKind;

/**
 * @phpstan-type Visit array{
 *     playerId: int,
 *     score: int,
 *     closedLeg: bool,
 *     bust: bool,
 *     isVoided: bool
 * }
 */
final class FinishedGame
{
    /**
     * @param  list<int>  $playerIds
     * @param  list<int>  $registeredPlayerIds
     * @param  list<Visit>  $visits
     */
    public function __construct(
        public GameKind $kind,
        public int $sourceId,
        public int $startingScore,
        public string $gameType,
        public array $playerIds,
        public array $registeredPlayerIds,
        public array $visits,
    ) {}
}
