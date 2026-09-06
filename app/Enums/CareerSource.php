<?php

namespace App\Enums;

enum CareerSource: string
{
    case Training = 'training';
    case Quick = 'quick';
    case Tournament = 'tournament';
    case League = 'league';

    /**
     * Filtr UI „Turnieje” = turniej klubowy + liga.
     *
     * @return list<self>
     */
    public static function forFilter(?string $filter): array
    {
        return match ($filter) {
            'quick' => [self::Quick],
            'training' => [self::Training],
            'tournament' => [self::Tournament, self::League],
            default => [self::Quick, self::Tournament, self::League, self::Training],
        };
    }
}
