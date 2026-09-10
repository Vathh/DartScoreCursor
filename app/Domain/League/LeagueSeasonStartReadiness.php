<?php

namespace App\Domain\League;

use DomainException;

/**
 * Start sezonu: wszystkie szczeble oprócz ostatniego muszą być zapełnione.
 * Ostatni może mieć wolne miejsca (łatanie dziur z dołu bez nowego zgłoszenia).
 */
final readonly class LeagueSeasonStartReadiness
{
    /**
     * @param  list<string>  $unfilledLabels
     */
    private function __construct(
        public bool $canStart,
        public ?string $reason,
        public array $unfilledLabels,
    ) {}

    /**
     * @param  list<array{name: string, capacity: int, memberCount: int}>  $divisions
     *                                                                                 Od najwyższego szczebla (position 0) do najniższego.
     */
    public static function inspect(array $divisions): self
    {
        if ($divisions === []) {
            return new self(false, 'Liga nie ma szczebli.', []);
        }

        $lastIndex = count($divisions) - 1;
        $unfilledLabels = [];
        foreach ($divisions as $index => $division) {
            if ($index === $lastIndex) {
                continue;
            }
            $capacity = (int) $division['capacity'];
            $memberCount = (int) $division['memberCount'];
            if ($memberCount < $capacity) {
                $unfilledLabels[] = sprintf(
                    '%s (%d/%d)',
                    $division['name'],
                    $memberCount,
                    $capacity,
                );
            }
        }

        if ($unfilledLabels === []) {
            return new self(true, null, []);
        }

        return new self(
            false,
            'Nie można wystartować sezonu, dopóki wszystkie szczeble oprócz ostatniego nie są zapełnione. Brakuje zawodników: '
                .implode(', ', $unfilledLabels).'.',
            $unfilledLabels,
        );
    }

    public function assertCanStart(): void
    {
        if (! $this->canStart) {
            throw new DomainException($this->reason ?? 'Nie można wystartować sezonu ligowego.');
        }
    }
}
