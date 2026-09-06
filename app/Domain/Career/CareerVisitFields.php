<?php

namespace App\Domain\Career;

/**
 * Odczyt pola wizyty z modelu Eloquent albo tablicy (snake / camel).
 */
final class CareerVisitFields
{
    public static function get(mixed $visit, string $snake, ?string $camel = null, mixed $default = null): mixed
    {
        $camel ??= self::toCamel($snake);
        if (is_array($visit)) {
            return $visit[$snake] ?? $visit[$camel] ?? $default;
        }

        return $visit->{$snake} ?? $visit->{$camel} ?? $default;
    }

    private static function toCamel(string $snake): string
    {
        return lcfirst(str_replace('_', '', ucwords($snake, '_')));
    }
}
