<?php

namespace App\Domain;

use DomainException;

/**
 * Invariant: organizacja / sezon / turniej nie może zostać bez administratora.
 */
final class AdminRoster
{
    public static function assertCanRemove(int $adminsCount, string $entityLabel): void
    {
        if ($adminsCount <= 1) {
            throw new DomainException(
                $entityLabel.' musi mieć co najmniej jednego administratora.',
            );
        }
    }
}
