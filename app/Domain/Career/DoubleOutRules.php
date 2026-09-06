<?php

namespace App\Domain\Career;

/**
 * Próba na double-out: remaining przed lotką to 2–40 parzyste albo 50 (bull).
 */
final class DoubleOutRules
{
    public static function isCheckoutRemaining(int $remaining): bool
    {
        if ($remaining === 50) {
            return true;
        }

        return $remaining >= 2 && $remaining <= 40 && $remaining % 2 === 0;
    }

    public static function isDoubleFinishLabel(?string $label): bool
    {
        if ($label === null || $label === '') {
            return false;
        }

        if ($label === 'Bull' || $label === 'DB') {
            return true;
        }

        return str_starts_with($label, 'D');
    }

    /**
     * @param  list<array{remainingBefore: int, label?: string|null, points?: int, bust?: bool}>  $darts
     * @return array{attempts: int, successes: int}
     */
    public static function countFromDarts(array $darts): array
    {
        $attempts = 0;
        $successes = 0;

        foreach ($darts as $dart) {
            $remaining = (int) ($dart['remainingBefore'] ?? 0);
            if (! self::isCheckoutRemaining($remaining)) {
                continue;
            }

            $attempts++;
            $bust = (bool) ($dart['bust'] ?? false);
            $points = (int) ($dart['points'] ?? 0);
            $label = isset($dart['label']) ? (string) $dart['label'] : null;
            if (! $bust && $points === $remaining && self::isDoubleFinishLabel($label)) {
                $successes++;
            }
        }

        return [
            'attempts' => $attempts,
            'successes' => $successes,
        ];
    }
}
