<?php

namespace App\DTO\GameScoring;

use App\Domain\GameScoring\VisitDartPayload;
use App\Domain\GameScoring\VisitRecorder;

class RecordVisitDTO
{
    /**
     * @param  list<array{sector: int, points: int, label: string|null, remainingBefore: int|null, bust: bool}>|null  $darts
     */
    public function __construct(
        public int $playerId,
        public int $score,
        public int $remainingBefore,
        public int $remainingAfter,
        public int $dartsInVisit,
        public bool $closedLeg,
        public bool $bust,
        public string $clientVisitId,
        public ?array $darts = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        return array_merge([
            'playerId' => 'required|integer|exists:players,id',
            'score' => 'required|integer|min:0|max:180',
            'remainingBefore' => 'required|integer|min:0|max:1001',
            'remainingAfter' => 'required|integer|min:0|max:1001',
            'dartsInVisit' => 'required|integer|min:1|max:3',
            'closedLeg' => 'boolean',
            'bust' => 'boolean',
            'clientVisitId' => 'required|uuid',
        ], VisitDartPayload::validationRules());
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
            darts: VisitDartPayload::normalize($data['darts'] ?? null),
        );
    }
}
