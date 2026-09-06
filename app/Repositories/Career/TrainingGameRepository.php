<?php

namespace App\Repositories\Career;

use App\Models\Career\TrainingGame;
use Carbon\CarbonInterface;

class TrainingGameRepository
{
    /**
     * @param  array<string, mixed>  $format
     * @param  array<string, mixed>  $metrics
     */
    public function upsertByClientUuid(
        int $playerId,
        string $clientUuid,
        string $gameType,
        CarbonInterface $completedAt,
        ?array $format,
        array $metrics,
    ): TrainingGame {
        $existing = TrainingGame::query()
            ->where('player_id', $playerId)
            ->where('client_uuid', $clientUuid)
            ->first();

        $attrs = [
            'player_id' => $playerId,
            'client_uuid' => $clientUuid,
            'game_type' => $gameType,
            'completed_at' => $completedAt,
            'format' => $format,
            'metrics' => $metrics,
        ];

        if ($existing === null) {
            return TrainingGame::create($attrs);
        }

        $existing->fill($attrs);
        $existing->save();

        return $existing->fresh();
    }
}
