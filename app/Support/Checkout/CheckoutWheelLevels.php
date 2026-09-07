<?php

namespace App\Support\Checkout;

use App\Domain\Badge\Checkout\CheckoutLevelPolicy;

final class CheckoutWheelLevels
{
    public const APEX = CheckoutLevelPolicy::APEX;

    public const BRIGHT = CheckoutLevelPolicy::BRIGHT;

    public const GOLD = CheckoutLevelPolicy::GOLD;

    public const BRONZE = CheckoutLevelPolicy::BRONZE;

    public const IRON = CheckoutLevelPolicy::IRON;

    public static function fromHits(int $hits): string
    {
        return CheckoutLevelPolicy::nameForHits($hits);
    }
}
