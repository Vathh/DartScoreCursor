<?php

namespace App\Services\Career;

use App\Domain\Career\CareerWindow;
use App\Domain\Career\CoachDigestBuilder;
use App\Domain\Career\CoachFallbackPlanner;
use App\Enums\CareerSource;
use App\Models\Career\PlayerGameSnapshot;
use App\Models\Player\Player;
use App\Repositories\Career\PlayerGameSnapshotRepository;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Collection;

/**
 * Trener osobisty — kontrakt digest + plan.
 *
 * Celowo bez trasy HTTP, bez UI i bez klienta LLM. Żeby podpiąć później:
 * własny profil → ten serwis → (LLM albo fallback) → CoachPlan::fromArray.
 */
class PlayerCoachService
{
    public function __construct(
        private PlayerGameSnapshotRepository $snapshotRepository,
    ) {}

    /**
     * Zawsze pełne źródła łącznie z treningiem — tylko właściciel kariery.
     *
     * @return array{digest: array<string, mixed>, plan: array<string, mixed>}
     */
    public function buildForOwner(Player $player, ?string $windowKey = null): array
    {
        if (! $player->user_id) {
            throw new DomainException('Trener jest tylko dla zarejestrowanego gracza.');
        }

        $window = CareerWindow::fromQuery($windowKey ?: CareerWindow::DEFAULT_KEY);
        $sources = CareerSource::forFilter('all');

        $current = $this->snapshotRepository->listForPlayer(
            (int) $player->id,
            $sources,
            $window->startUtc(),
            $window->endExclusiveUtc(),
        );
        $previous = [];
        if ($window->days !== null) {
            $previous = $this->toRows($this->snapshotRepository->listForPlayer(
                (int) $player->id,
                $sources,
                $window->previousStartUtc(),
                $window->previousEndExclusiveUtc(),
            ));
        }

        $digest = CoachDigestBuilder::fromSnapshotRows(
            $window->key,
            $this->toRows($current),
            $previous,
            CarbonImmutable::now(),
        );

        return [
            'digest' => $digest->toArray(),
            'plan' => CoachFallbackPlanner::plan($digest)->toArray(),
        ];
    }

    /**
     * @param  Collection<int, PlayerGameSnapshot>  $snapshots
     * @return list<array{source: string, game_type: string, metrics: array<string, mixed>}>
     */
    private function toRows(Collection $snapshots): array
    {
        return $snapshots
            ->map(function (PlayerGameSnapshot $snapshot) {
                $source = $snapshot->source;

                return [
                    'source' => $source instanceof CareerSource ? $source->value : (string) $source,
                    'game_type' => (string) $snapshot->game_type,
                    'metrics' => is_array($snapshot->metrics) ? $snapshot->metrics : [],
                ];
            })
            ->values()
            ->all();
    }
}
