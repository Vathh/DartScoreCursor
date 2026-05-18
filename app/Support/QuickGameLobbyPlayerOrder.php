<?php

namespace App\Support;

use Illuminate\Support\Collection;

class QuickGameLobbyPlayerOrder
{
    /**
     * @param  Collection<int, object>  $players  QuickGameLobbyPlayer models
     * @param  array<int, int>|null  $orderLobbyPlayerIds
     * @return Collection<int, object>
     */
    public static function sort(Collection $players, ?array $orderLobbyPlayerIds): Collection
    {
        if (! is_array($orderLobbyPlayerIds) || count($orderLobbyPlayerIds) === 0) {
            return $players->values();
        }

        $orderPos = array_flip(array_map('intval', $orderLobbyPlayerIds));

        return $players->sortBy(function ($p) use ($orderPos) {
            $id = (int) $p->id;

            return $orderPos[$id] ?? (10000 + $id);
        })->values();
    }
}
