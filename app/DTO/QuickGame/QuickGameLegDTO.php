<?php

namespace App\DTO\QuickGame;

class QuickGameLegDTO
{
    public function __construct(
        public int $legNumber,
        public int $playerId,
        public int $score, // Wynik gracza w tym legu
        public ?float $average = null,
        public ?int $dartsThrown = null,
        public ?int $checkoutScore = null,
        public ?string $startedAt = null,
        public ?string $finishedAt = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            legNumber: $data['legNumber'],
            playerId: $data['playerId'],
            score: $data['score'],
            average: isset($data['average']) ? (float)$data['average'] : null,
            dartsThrown: $data['dartsThrown'] ?? null,
            checkoutScore: $data['checkoutScore'] ?? null,
            startedAt: $data['startedAt'] ?? null,
            finishedAt: $data['finishedAt'] ?? null,
        );
    }
}
