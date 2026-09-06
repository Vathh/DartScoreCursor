<?php

namespace App\Domain\Career;

use InvalidArgumentException;

/**
 * Wyjście trenera: karty sesji 30/60/90 min. Przyszły LLM musi trafić w ten schemat.
 *
 * @phpstan-type Card array{
 *     durationMin: int,
 *     modeId: string,
 *     focus: string,
 *     why: string,
 *     steps: list<string>
 * }
 */
final class CoachPlan
{
    public const SCHEMA_VERSION = 1;

    public const SOURCE_TEMPLATE = 'template';

    public const SOURCE_LLM = 'llm';

    /** @var list<int> */
    public const DURATIONS = [30, 60, 90];

    public const HEADLINE_MAX = 160;

    public const FOCUS_MAX = 80;

    public const WHY_MAX = 280;

    public const STEP_MAX = 160;

    public const STEPS_MIN = 1;

    public const STEPS_MAX = 6;

    public const CARDS_MIN = 1;

    public const CARDS_MAX = 3;

    /**
     * @param  list<Card>  $cards
     */
    public function __construct(
        public readonly string $source,
        public readonly string $headline,
        public readonly string $focus,
        public readonly array $cards,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $source = (string) ($payload['source'] ?? '');
        if (! in_array($source, [self::SOURCE_TEMPLATE, self::SOURCE_LLM], true)) {
            throw new InvalidArgumentException('Nieznane źródło planu trenera.');
        }

        $headline = self::requiredText($payload['headline'] ?? null, self::HEADLINE_MAX, 'nagłówek');
        $focus = self::requiredText($payload['focus'] ?? null, self::FOCUS_MAX, 'fokus');
        $cardsRaw = $payload['cards'] ?? null;
        if (! is_array($cardsRaw) || $cardsRaw === []) {
            throw new InvalidArgumentException('Plan trenera wymaga kart sesji.');
        }
        if (count($cardsRaw) < self::CARDS_MIN || count($cardsRaw) > self::CARDS_MAX) {
            throw new InvalidArgumentException('Plan trenera ma mieć 1–3 karty.');
        }

        $cards = [];
        $seenDurations = [];
        foreach ($cardsRaw as $card) {
            if (! is_array($card)) {
                throw new InvalidArgumentException('Karta sesji musi być obiektem.');
            }
            $parsed = self::parseCard($card);
            $duration = $parsed['durationMin'];
            if (isset($seenDurations[$duration])) {
                throw new InvalidArgumentException('Każda karta musi mieć inną długość sesji.');
            }
            $seenDurations[$duration] = true;
            $cards[] = $parsed;
        }

        return new self($source, $headline, $focus, $cards);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'source' => $this->source,
            'headline' => $this->headline,
            'focus' => $this->focus,
            'cards' => $this->cards,
        ];
    }

    /**
     * @param  array<string, mixed>  $card
     * @return Card
     */
    private static function parseCard(array $card): array
    {
        $duration = (int) ($card['durationMin'] ?? 0);
        if (! in_array($duration, self::DURATIONS, true)) {
            throw new InvalidArgumentException('Długość sesji musi być 30, 60 albo 90 minut.');
        }

        $modeId = (string) ($card['modeId'] ?? '');
        if (! CoachModeCatalog::has($modeId)) {
            throw new InvalidArgumentException('Nieznany tryb w karcie trenera.');
        }

        $stepsRaw = $card['steps'] ?? null;
        if (! is_array($stepsRaw) || $stepsRaw === []) {
            throw new InvalidArgumentException('Karta sesji wymaga kroków.');
        }
        if (count($stepsRaw) < self::STEPS_MIN || count($stepsRaw) > self::STEPS_MAX) {
            throw new InvalidArgumentException('Karta sesji ma mieć 1–6 kroków.');
        }

        $steps = [];
        foreach ($stepsRaw as $step) {
            $steps[] = self::requiredText($step, self::STEP_MAX, 'krok');
        }

        return [
            'durationMin' => $duration,
            'modeId' => $modeId,
            'focus' => self::requiredText($card['focus'] ?? null, self::FOCUS_MAX, 'fokus karty'),
            'why' => self::requiredText($card['why'] ?? null, self::WHY_MAX, 'uzasadnienie'),
            'steps' => $steps,
        ];
    }

    private static function requiredText(mixed $value, int $max, string $label): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('Brak tekstu: '.$label.'.');
        }
        $text = trim($value);
        if ($text === '') {
            throw new InvalidArgumentException('Pusty tekst: '.$label.'.');
        }
        if (mb_strlen($text) > $max) {
            throw new InvalidArgumentException('Za długi tekst: '.$label.'.');
        }

        return $text;
    }
}
