<?php

namespace App\Support;

use App\Models\QuickGame\QuickGameLobby;

class QuickGameLobbyPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function fromLobby(QuickGameLobby $lobby): array
    {
        $lobby->loadMissing(['host.player', 'players.player', 'session']);

        $players = $lobby->players->map(function ($p) use ($lobby) {
            $isRegistered = $p->player_id !== null;
            $isHost = $p->player && (int) $p->player->user_id === (int) $lobby->host_id;

            return [
                'id' => $p->id,
                'playerId' => $p->player_id,
                'name' => $p->player?->name ?? null,
                'tempName' => $p->temp_player_name,
                'ready' => (bool) $p->is_ready,
                'isRegistered' => $isRegistered,
                'isHost' => $isHost,
            ];
        })->values()->all();

        $out = [
            'id' => $lobby->id,
            'code' => $lobby->code,
            'hostId' => $lobby->host_id,
            'status' => $lobby->status,
            'legsCount' => $lobby->legs_count ?? 3,
            'gameType' => $lobby->game_type ?? '501',
            'scoringMode' => $lobby->scoring_mode ?? 'each_own',
            'players' => $players,
        ];

        if ($lobby->status === 'started' && $lobby->session) {
            $out['sessionId'] = $lobby->session->id;
        }

        return $out;
    }
}
