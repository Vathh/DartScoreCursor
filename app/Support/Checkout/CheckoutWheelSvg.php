<?php

namespace App\Support\Checkout;

final class CheckoutWheelSvg
{
    public static function inline(): string
    {
        $svg = file_get_contents(public_path('images/checkout_wheel.svg')) ?: '';

        return preg_replace('/^<\?xml.*?\?>\s*/', '', $svg) ?? $svg;
    }
}
