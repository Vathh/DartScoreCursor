<?php

namespace App\Repositories\Career;

use App\Enums\CareerSource;
use App\Models\Career\PlayerGameSnapshot;
use App\Models\Player\Player;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PlayerGameSnapshotRepository
{
    /**
     * @param  array<string, mixed>  $metrics
     */
    public function upsertForSourceable(
        int $playerId,
        CareerSource $source,
        string $gameType,
        CarbonInterface $occurredAt,
        Model $sourceable,
        array $metrics,
        ?string $clientUuid = null,
    ): PlayerGameSnapshot {
        $snapshot = PlayerGameSnapshot::query()
            ->where('player_id', $playerId)
            ->where('sourceable_type', $sourceable->getMorphClass())
            ->where('sourceable_id', $sourceable->getKey())
            ->first();

        if ($snapshot === null && $clientUuid !== null) {
            $snapshot = PlayerGameSnapshot::query()
                ->where('player_id', $playerId)
                ->where('client_uuid', $clientUuid)
                ->first();
        }

        $attrs = [
            'player_id' => $playerId,
            'source' => $source,
            'game_type' => $gameType,
            'occurred_at' => $occurredAt,
            'client_uuid' => $clientUuid,
            'sourceable_type' => $sourceable->getMorphClass(),
            'sourceable_id' => $sourceable->getKey(),
            'metrics' => $metrics,
        ];

        if ($snapshot === null) {
            return PlayerGameSnapshot::create($attrs);
        }

        $snapshot->fill($attrs);
        $snapshot->save();

        return $snapshot->fresh();
    }

    public function deleteForSourceable(Model $sourceable): int
    {
        return PlayerGameSnapshot::query()
            ->where('sourceable_type', $sourceable->getMorphClass())
            ->where('sourceable_id', $sourceable->getKey())
            ->delete();
    }

    /**
     * @param  list<CareerSource>  $sources
     * @return Collection<int, PlayerGameSnapshot>
     */
    public function listForPlayer(
        int $playerId,
        array $sources,
        ?CarbonInterface $fromUtc,
        CarbonInterface $toExclusiveUtc,
    ): Collection {
        $query = PlayerGameSnapshot::query()
            ->where('player_id', $playerId)
            ->whereIn('source', array_map(fn (CareerSource $s) => $s->value, $sources))
            ->where('occurred_at', '<', $toExclusiveUtc)
            ->orderBy('occurred_at');

        if ($fromUtc !== null) {
            $query->where('occurred_at', '>=', $fromUtc);
        }

        return $query->get();
    }

    /**
     * @return list<array{id: int, occurred_at: string}>
     */
    public function listTrainingHistoryStubs(int $playerId): array
    {
        return PlayerGameSnapshot::query()
            ->where('player_id', $playerId)
            ->where('source', CareerSource::Training)
            ->orderByDesc('occurred_at')
            ->get(['id', 'occurred_at'])
            ->map(fn (PlayerGameSnapshot $row) => [
                'id' => (int) $row->id,
                'occurred_at' => $row->occurred_at?->toDateTimeString() ?? '',
            ])
            ->all();
    }

    public function existsForPlayer(Player $player): bool
    {
        return PlayerGameSnapshot::query()->where('player_id', $player->id)->exists();
    }
}
