<?php

namespace App\Services\Career;

use App\Domain\Career\CareerSnapshotMetrics;
use App\Domain\Career\CareerWindow;
use App\Enums\CareerSource;
use App\Models\Career\PlayerGameSnapshot;
use App\Models\Player\Player;
use App\Models\Users\User;
use App\Repositories\Career\PlayerGameSnapshotRepository;
use Illuminate\Support\Collection;

class PlayerCareerStatsService
{
    public function __construct(
        private PlayerGameSnapshotRepository $snapshotRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(
        Player $player,
        ?User $viewer,
        ?string $windowKey,
        ?string $sourceFilter,
    ): array {
        if (! $player->user_id) {
            abort(404, 'Profil dostępny tylko dla graczy zarejestrowanych.');
        }

        $window = CareerWindow::fromQuery($windowKey);
        $isSelf = $viewer?->player !== null && (int) $viewer->player->id === (int) $player->id;
        $filter = $sourceFilter ?: 'all';
        if ($filter === 'training' && ! $isSelf) {
            abort(403, 'Treningi są prywatne.');
        }

        $sources = CareerSource::forFilter($filter);
        if (! $isSelf) {
            $sources = array_values(array_filter(
                $sources,
                fn (CareerSource $s) => $s !== CareerSource::Training,
            ));
        }

        $current = $this->snapshotRepository->listForPlayer(
            (int) $player->id,
            $sources,
            $window->startUtc(),
            $window->endExclusiveUtc(),
        );
        $previous = null;
        if ($window->days !== null) {
            $previous = $this->snapshotRepository->listForPlayer(
                (int) $player->id,
                $sources,
                $window->previousStartUtc(),
                $window->previousEndExclusiveUtc(),
            );
        }

        $hero = $this->aggregate($current);
        $prevHero = $previous !== null ? $this->aggregate($previous) : null;

        return [
            'window' => $window->key,
            'source' => $filter,
            'isSelf' => $isSelf,
            'hero' => [
                'games' => $hero['games'],
                'x01Average' => $hero['x01Average'],
                'x01AverageDelta' => $this->delta($hero['x01Average'], $prevHero['x01Average'] ?? null),
                'doublePct' => $hero['doublePct'],
                'doublePctDelta' => $this->delta($hero['doublePct'], $prevHero['doublePct'] ?? null),
                'doubleAttempts' => $hero['doubleAttempts'],
                'doubleSuccesses' => $hero['doubleSuccesses'],
                'doubleLabel' => $hero['doubleLabel'],
                'hasX01' => $hero['hasX01'],
                'hasDoubles' => $hero['hasDoubles'],
            ],
            'series' => [
                'x01_average' => $this->seriesX01($current),
                'double_pct' => $this->seriesDoubles($current),
            ],
        ];
    }

    /**
     * @param  Collection<int, PlayerGameSnapshot>  $snapshots
     * @return array<string, mixed>
     */
    private function aggregate(Collection $snapshots): array
    {
        $games = $snapshots->count();
        $darts = 0;
        $points = 0;
        $attempts = 0;
        $successes = 0;
        $hasX01 = false;
        $hasDoubles = false;

        foreach ($snapshots as $snapshot) {
            $m = $snapshot->metrics ?? [];
            $gameDarts = (int) ($m['darts_thrown'] ?? 0);
            if (CareerSnapshotMetrics::isX01((string) $snapshot->game_type) && $gameDarts > 0) {
                $hasX01 = true;
                $darts += $gameDarts;
                $points += (int) ($m['points'] ?? 0);
            }
            if (! empty($m['double_tracked'])) {
                $hasDoubles = true;
                $attempts += (int) ($m['double_attempts'] ?? 0);
                $successes += (int) ($m['double_successes'] ?? 0);
            }
        }

        $x01Average = $hasX01 && $darts > 0 ? round(($points / $darts) * 3, 2) : null;
        $doublePct = $hasDoubles && $attempts > 0 ? round(($successes / $attempts) * 100, 1) : null;

        return [
            'games' => $games,
            'x01Average' => $x01Average,
            'doublePct' => $doublePct,
            'doubleAttempts' => $hasDoubles ? $attempts : null,
            'doubleSuccesses' => $hasDoubles ? $successes : null,
            'doubleLabel' => $hasDoubles && $attempts > 0 ? $successes.'/'.$attempts : null,
            'hasX01' => $hasX01,
            'hasDoubles' => $hasDoubles,
        ];
    }

    /**
     * @param  Collection<int, PlayerGameSnapshot>  $snapshots
     * @return list<array{t: string, value: float|null, games: int}>
     */
    private function seriesX01(Collection $snapshots): array
    {
        $cumDarts = 0;
        $cumPoints = 0;
        $points = [];

        foreach ($snapshots as $snapshot) {
            $m = $snapshot->metrics ?? [];
            $gameDarts = (int) ($m['darts_thrown'] ?? 0);
            if (! CareerSnapshotMetrics::isX01((string) $snapshot->game_type) || $gameDarts <= 0) {
                continue;
            }
            $cumDarts += $gameDarts;
            $cumPoints += (int) ($m['points'] ?? 0);
            $points[] = [
                't' => $snapshot->occurred_at?->timezone(CareerWindow::TIMEZONE)->toDateString() ?? '',
                'value' => round(($cumPoints / $cumDarts) * 3, 2),
                'games' => count($points) + 1,
            ];
        }

        return $points;
    }

    /**
     * @param  Collection<int, PlayerGameSnapshot>  $snapshots
     * @return list<array{t: string, value: float|null, label: string|null, games: int}>
     */
    private function seriesDoubles(Collection $snapshots): array
    {
        $attempts = 0;
        $successes = 0;
        $points = [];

        foreach ($snapshots as $snapshot) {
            $m = $snapshot->metrics ?? [];
            if (empty($m['double_tracked'])) {
                continue;
            }
            $attempts += (int) ($m['double_attempts'] ?? 0);
            $successes += (int) ($m['double_successes'] ?? 0);
            if ($attempts <= 0) {
                continue;
            }
            $points[] = [
                't' => $snapshot->occurred_at?->timezone(CareerWindow::TIMEZONE)->toDateString() ?? '',
                'value' => round(($successes / $attempts) * 100, 1),
                'label' => $successes.'/'.$attempts,
                'games' => count($points) + 1,
            ];
        }

        return $points;
    }

    private function delta(?float $current, ?float $previous): ?float
    {
        if ($current === null || $previous === null) {
            return null;
        }

        return round($current - $previous, 2);
    }

}
