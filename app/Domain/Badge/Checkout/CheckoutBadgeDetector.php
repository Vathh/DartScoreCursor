<?php

namespace App\Domain\Badge\Checkout;

use App\Domain\Badge\BadgeCategory;
use App\Domain\Badge\BadgeDetector;
use App\Domain\Badge\FinishedGame;
use App\Domain\Badge\GameEligibility;

final class CheckoutBadgeDetector implements BadgeDetector
{
    public function detect(FinishedGame $game): array
    {
        if (! GameEligibility::allowsCheckoutBadges($game)) {
            return [];
        }

        $counts = [];
        foreach ($game->visits as $visit) {
            if ($visit['isVoided'] || $visit['bust'] || ! $visit['closedLeg']) {
                continue;
            }
            if (! GameEligibility::isRegistered($game, $visit['playerId'])) {
                continue;
            }
            if (! CheckoutCatalog::contains($visit['score'])) {
                continue;
            }

            $key = $visit['playerId'].':'.$visit['score'];
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $events = [];
        foreach ($counts as $key => $amount) {
            [$playerId, $badgeKey] = explode(':', $key, 2);
            $events[] = [
                'playerId' => (int) $playerId,
                'category' => BadgeCategory::Checkout->value,
                'badgeKey' => $badgeKey,
                'amount' => $amount,
            ];
        }

        return $events;
    }
}
