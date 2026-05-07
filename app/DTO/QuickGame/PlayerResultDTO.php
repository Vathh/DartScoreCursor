<?php

namespace App\DTO\QuickGame;

class PlayerResultDTO
{
    public function __construct(
        public int $playerId,
        public int $score, // Liczba wygranych legów
        public ?int $place = null, // Miejsce w meczu (1 = zwycięzca)
        public ?float $average = null, // Średnia punktowa w meczu
        public ?int $dartsThrown = null, // Łączna liczba rzuconych lotek
        public ?int $pointsEarned = null, // Łączna liczba zdobytych punktów
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            playerId: $data['playerId'],
            score: $data['score'],
            place: $data['place'] ?? null,
            average: isset($data['average']) ? (float)$data['average'] : null,
            dartsThrown: $data['dartsThrown'] ?? null,
            pointsEarned: $data['pointsEarned'] ?? null,
        );
    }
}

