<?php

namespace App\DTO\QuickGameFfa;

use App\Domain\GameScoring\VisitRecorder;

class RecordFfaVisitDTO
{
    public function __construct(
        public int $playerId,
        public int $score,
        public int $remainingBefore,
        public int $remainingAfter,
        public int $dartsInVisit,
        public bool $closedLeg,
        public bool $bust,
        public string $clientVisitId,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            playerId: (int) $data['playerId'],
            score: (int) $data['score'],
            remainingBefore: (int) $data['remainingBefore'],
            remainingAfter: (int) $data['remainingAfter'],
            dartsInVisit: ! empty($data['bust'])
                ? VisitRecorder::BUST_DARTS_IN_VISIT
                : (int) ($data['dartsInVisit'] ?? 3),
            closedLeg: (bool) ($data['closedLeg'] ?? false),
            bust: (bool) ($data['bust'] ?? false),
            clientVisitId: (string) $data['clientVisitId'],
        );
    }
}
