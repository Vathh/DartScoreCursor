<?php

namespace App\Repositories;

use App\Models\QuickGameLobby;
use App\Models\QuickGameLobbyPlayer;
use Illuminate\Support\Collection;

class QuickGameLobbyRepository
{
    /**
     * Tworzy nowe lobby
     * @param int $hostId ID użytkownika tworzącego lobby
     * @return QuickGameLobby
     */
    public function create(int $hostId): QuickGameLobby
    {
        return QuickGameLobby::create([
            'host_id' => $hostId,
            'status' => 'waiting',
        ]);
    }

    /**
     * Znajduje lobby po ID
     * @param int $lobbyId
     * @return QuickGameLobby
     */
    public function find(int $lobbyId): QuickGameLobby
    {
        return QuickGameLobby::with(['host.player', 'players.player'])
            ->findOrFail($lobbyId);
    }

    /**
     * Znajduje lobby po kodzie
     * @param string $code
     * @return QuickGameLobby|null
     */
    public function findByCode(string $code): ?QuickGameLobby
    {
        return QuickGameLobby::with(['host.player', 'players.player'])
            ->where('code', $code)
            ->first();
    }

    /**
     * Dodaje gracza do lobby
     * @param int $lobbyId
     * @param int|null $playerId ID gracza (null dla tymczasowego)
     * @param string|null $tempPlayerName Nazwa gracza tymczasowego
     * @param bool $isRegistered
     * @return QuickGameLobbyPlayer
     */
    public function addPlayer(int $lobbyId, ?int $playerId, ?string $tempPlayerName = null, bool $isRegistered = true): QuickGameLobbyPlayer
    {
        // Sprawdź czy gracz już jest w lobby (tylko dla zarejestrowanych)
        if ($isRegistered && $playerId) {
            $existing = QuickGameLobbyPlayer::where('lobby_id', $lobbyId)
                ->where('player_id', $playerId)
                ->first();

            if ($existing) {
                throw new \RuntimeException('Gracz już jest w lobby');
            }
        }

        // Sprawdź limit graczy (max 6)
        $playersCount = QuickGameLobbyPlayer::where('lobby_id', $lobbyId)->count();
        if ($playersCount >= 6) {
            throw new \RuntimeException('Lobby jest pełne (max 6 graczy)');
        }

        return QuickGameLobbyPlayer::create([
            'lobby_id' => $lobbyId,
            'player_id' => $playerId,
            'temp_player_name' => $tempPlayerName,
            'is_registered' => $isRegistered,
            'is_ready' => false,
        ]);
    }

    /**
     * Usuwa gracza z lobby
     * @param int $lobbyId
     * @param int|null $playerId ID gracza (null dla tymczasowego - wtedy użyj tempPlayerName)
     * @param string|null $tempPlayerName Nazwa gracza tymczasowego
     * @return void
     */
    public function removePlayer(int $lobbyId, ?int $playerId, ?string $tempPlayerName = null): void
    {
        $query = QuickGameLobbyPlayer::where('lobby_id', $lobbyId);

        if ($playerId) {
            $query->where('player_id', $playerId);
        } elseif ($tempPlayerName) {
            $query->where('temp_player_name', $tempPlayerName)
                  ->whereNull('player_id');
        } else {
            throw new \RuntimeException('Musisz podać playerId lub tempPlayerName');
        }

        $deleted = $query->delete();

        if ($deleted === 0) {
            throw new \RuntimeException('Gracz nie został znaleziony w lobby');
        }
    }

    /**
     * Ustawia status gotowości gracza
     * @param int $lobbyId
     * @param int|null $playerId
     * @param bool $isReady
     * @return void
     */
    public function setPlayerReady(int $lobbyId, ?int $playerId, bool $isReady): void
    {
        QuickGameLobbyPlayer::where('lobby_id', $lobbyId)
            ->where('player_id', $playerId)
            ->update(['is_ready' => $isReady]);
    }

    /**
     * Rozpoczyna mecz (zmienia status lobby na 'in_progress')
     * @param int $lobbyId
     * @return QuickGameLobby
     */
    public function startGame(int $lobbyId): QuickGameLobby
    {
        $lobby = QuickGameLobby::findOrFail($lobbyId);
        $lobby->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $lobby->fresh(['host.player', 'players.player']);
    }

    /**
     * Usuwa lobby
     * @param int $lobbyId
     * @return void
     */
    public function delete(int $lobbyId): void
    {
        QuickGameLobby::destroy($lobbyId);
    }
}
