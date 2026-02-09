<?php

namespace App\Repositories;

use App\Models\QuickGameLobby;
use App\Models\QuickGameLobbyPlayer;
use Illuminate\Support\Facades\DB;

class QuickGameLobbyRepository
{
    public function create(int $hostUserId): QuickGameLobby
    {
        return QuickGameLobby::create([
            'host_id' => $hostUserId,
            'status' => 'waiting',
        ]);
    }

    public function find(int $lobbyId): QuickGameLobby
    {
        return QuickGameLobby::with(['host.player', 'players.player'])
            ->findOrFail($lobbyId);
    }

    public function findByCode(string $code): ?QuickGameLobby
    {
        return QuickGameLobby::with(['host.player', 'players.player'])
            ->where('code', $code)
            ->first();
    }

    public function addPlayer(int $lobbyId, ?int $playerId, ?string $tempPlayerName, bool $isRegistered): void
    {
        QuickGameLobbyPlayer::create([
            'lobby_id' => $lobbyId,
            'player_id' => $playerId,
            'temp_player_name' => $tempPlayerName,
            'is_registered' => $isRegistered,
            'is_ready' => false,
        ]);
    }

    public function removePlayer(int $lobbyId, ?int $playerId, ?string $tempPlayerName): void
    {
        $query = QuickGameLobbyPlayer::where('lobby_id', $lobbyId);
        if ($playerId !== null) {
            $query->where('player_id', $playerId);
        } elseif ($tempPlayerName !== null) {
            $query->where('temp_player_name', $tempPlayerName);
        } else {
            return;
        }
        $query->delete();
    }

    public function delete(int $lobbyId): void
    {
        QuickGameLobbyPlayer::where('lobby_id', $lobbyId)->delete();
        QuickGameLobby::destroy($lobbyId);
    }

    public function setPlayerReady(int $lobbyId, int $playerId, bool $isReady): void
    {
        QuickGameLobbyPlayer::where('lobby_id', $lobbyId)
            ->where('player_id', $playerId)
            ->update(['is_ready' => $isReady]);
    }

    public function updateSettings(int $lobbyId, int $hostUserId, ?int $legsCount = null, ?string $gameType = null): QuickGameLobby
    {
        $lobby = $this->find($lobbyId);
        if ($lobby->host_id !== $hostUserId) {
            throw new \RuntimeException('Tylko host może zmieniać ustawienia lobby');
        }
        if ($lobby->status !== 'waiting') {
            throw new \RuntimeException('Nie można zmieniać ustawień po rozpoczęciu meczu');
        }
        $updates = [];
        if ($legsCount !== null && $legsCount >= 1 && $legsCount <= 15) {
            $updates['legs_count'] = $legsCount;
        }
        if ($gameType !== null && in_array($gameType, ['501', 'cricket'], true)) {
            $updates['game_type'] = $gameType;
        }
        if (!empty($updates)) {
            $updates['updated_at'] = now();
            DB::table('quick_game_lobbies')->where('id', $lobbyId)->update($updates);
        }
        return $this->find($lobbyId);
    }

    public function startGame(int $lobbyId, ?int $legsCount = null, ?string $gameType = null): QuickGameLobby
    {
        $this->find($lobbyId); // ensure lobby exists
        $now = now();
        $updates = [
            'status' => 'started',
            'started_at' => $now,
            'updated_at' => $now,
        ];
        if ($legsCount !== null && $legsCount >= 1 && $legsCount <= 15) {
            $updates['legs_count'] = $legsCount;
        }
        if ($gameType !== null && in_array($gameType, ['501', 'cricket'], true)) {
            $updates['game_type'] = $gameType;
        }
        DB::table('quick_game_lobbies')
            ->where('id', $lobbyId)
            ->update($updates);
        return $this->find($lobbyId);
    }
}
