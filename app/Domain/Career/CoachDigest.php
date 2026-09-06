<?php

namespace App\Domain\Career;

/**
 * Wejście przyszłego LLM (i fallbacku): fakty z kariery, bez PII i bez surowych wizyt.
 *
 * @phpstan-type Hero array{
 *     games: int,
 *     x01Average: float|null,
 *     x01AverageDelta: float|null,
 *     doublePct: float|null,
 *     doublePctDelta: float|null,
 *     doubleAttempts: int|null,
 *     doubleSuccesses: int|null,
 *     doubleLabel: string|null,
 *     hasX01: bool,
 *     hasDoubles: bool
 * }
 * @phpstan-type PerDoubleRow array{attempts: int, successes: int, pct: float|null}
 */
final class CoachDigest
{
    public const SCHEMA_VERSION = 1;

    /**
     * @param  Hero  $hero
     * @param  array<string, int>  $sources
     * @param  array<string, int>  $gameTypes
     * @param  array<string, PerDoubleRow>  $perDouble
     * @param  list<string>  $notReadyReasons
     * @param  list<string>  $focusHints
     * @param  list<array{id: string, label: string, trains: list<string>}>  $modes
     */
    public function __construct(
        public readonly string $window,
        public readonly string $generatedAt,
        public readonly bool $ready,
        public readonly array $notReadyReasons,
        public readonly array $hero,
        public readonly array $sources,
        public readonly array $gameTypes,
        public readonly array $perDouble,
        public readonly ?string $weakestDouble,
        public readonly ?float $weakestDoublePct,
        public readonly array $focusHints,
        public readonly array $modes,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'window' => $this->window,
            'generatedAt' => $this->generatedAt,
            'ready' => $this->ready,
            'notReadyReasons' => $this->notReadyReasons,
            'hero' => $this->hero,
            'sources' => $this->sources,
            'gameTypes' => $this->gameTypes,
            'bob27' => [
                'perDouble' => $this->perDouble,
                'weakest' => $this->weakestDouble,
                'weakestPct' => $this->weakestDoublePct,
            ],
            'focusHints' => $this->focusHints,
            'modes' => $this->modes,
        ];
    }
}
