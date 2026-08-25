<?php

namespace App\Support\Checkout;

final class CheckoutWheelLevels
{
    public const APEX = 15;

    public const BRIGHT = 10;

    public const GOLD = 5;

    public const BRONZE = 3;

    public const IRON = 1;

    public static function fromHits(int $hits): string
    {
        return match (true) {
            $hits >= self::APEX => 'apex',
            $hits >= self::BRIGHT => 'bright',
            $hits >= self::GOLD => 'gold',
            $hits >= self::BRONZE => 'bronze',
            $hits >= self::IRON => 'iron',
            default => 'locked',
        };
    }
}
