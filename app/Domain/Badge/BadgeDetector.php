<?php

namespace App\Domain\Badge;

interface BadgeDetector
{
    /**
     * @return list<array{playerId: int, category: string, badgeKey: string, amount: int}>
     */
    public function detect(FinishedGame $game): array;
}
