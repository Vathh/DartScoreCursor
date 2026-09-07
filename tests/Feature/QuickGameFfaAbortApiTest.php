<?php

namespace Tests\Feature;

use App\Models\Player\Player;
use App\Models\QuickGame\QuickGameFfaPresence;
use App\Models\QuickGame\QuickGameFfaSession;
use App\Models\Users\User;
use App\Services\Player\PlayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuickGameFfaAbortApiTest extends TestCase
{
    use RefreshDatabase;

    private User $host;

    private User $friend;

    private Player $hostPlayer;

    private Player $friendPlayer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = User::factory()->create(['email' => 'abort-host@test.com']);
        $this->friend = User::factory()->create(['email' => 'abort-friend@test.com']);

        $playerService = app(PlayerService::class);
        $playerService->create('Host', $this->host->id);
        $playerService->create('Friend', $this->friend->id);

        $this->hostPlayer = Player::where('user_id', $this->host->id)->first();
        $this->friendPlayer = Player::where('user_id', $this->friend->id)->first();

        Sanctum::actingAs($this->host);
        $this->postJson('/api/friends/add', ['friendId' => $this->friend->id])->assertCreated();
    }

    public function test_host_can_abort_one_device_game_and_open_new_lobby(): void
    {
        $lobbyId = $this->startOneDeviceLobbyWithGuest();

        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ffa/abort")
            ->assertOk()
            ->assertJsonPath('aborted', true);

        $this->assertDatabaseMissing('quick_game_lobbies', ['id' => $lobbyId]);
        $this->assertDatabaseMissing('quick_game_ffa_sessions', ['lobby_id' => $lobbyId]);
        $this->assertDatabaseMissing('quick_games', ['lobby_id' => $lobbyId]);

        $this->getJson('/api/quick-game/lobby/active-match')
            ->assertOk()
            ->assertJsonPath('match', null);

        $this->postJson('/api/quick-game/lobby/create')
            ->assertOk()
            ->assertJsonPath('status', 'waiting');
    }

    public function test_non_host_cannot_abort_started_game(): void
    {
        $lobbyId = $this->startTwoPlayerEachOwnLobby();

        Sanctum::actingAs($this->friend);
        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ffa/abort")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Tylko host może skasować grę.');

        $this->assertDatabaseHas('quick_game_lobbies', [
            'id' => $lobbyId,
            'status' => 'started',
        ]);
        $this->assertDatabaseHas('quick_game_ffa_sessions', [
            'lobby_id' => $lobbyId,
            'status' => QuickGameFfaSession::STATUS_IN_PROGRESS,
        ]);
    }

    public function test_one_device_presence_left_is_rejected_and_host_can_resume(): void
    {
        $lobbyId = $this->startOneDeviceLobbyWithGuest();

        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ffa/presence", [
            'status' => QuickGameFfaPresence::STATUS_LEFT,
        ])->assertStatus(422);

        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ffa/presence", [
            'status' => QuickGameFfaPresence::STATUS_DISCONNECTED,
        ])->assertOk();

        $this->getJson('/api/quick-game/lobby/active-match')
            ->assertOk()
            ->assertJsonPath('match.lobbyId', $lobbyId)
            ->assertJsonPath('match.scoringMode', 'one_device')
            ->assertJsonPath('match.isHost', true);
    }

    public function test_host_can_abort_each_own_game(): void
    {
        $lobbyId = $this->startTwoPlayerEachOwnLobby();

        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ffa/abort")
            ->assertOk();

        Sanctum::actingAs($this->friend);
        $this->getJson('/api/quick-game/lobby/active-match')
            ->assertOk()
            ->assertJsonPath('match', null);
    }

    private function startOneDeviceLobbyWithGuest(): int
    {
        $lobbyId = $this->postJson('/api/quick-game/lobby/create')->json('id');

        $this->postJson("/api/quick-game/lobby/{$lobbyId}/add-guest", [
            'tempPlayerName' => 'Kumpel',
        ])->assertOk();

        $this->postJson("/api/quick-game/lobby/{$lobbyId}/start", [
            'matchFormat' => ['legsToWinSet' => 2, 'setsToWinMatch' => 1, 'startingScore' => 501],
            'gameType' => '501',
            'scoringMode' => 'one_device',
        ])->assertOk();

        return $lobbyId;
    }

    private function startTwoPlayerEachOwnLobby(): int
    {
        $lobbyId = $this->postJson('/api/quick-game/lobby/create')->json('id');

        $this->postJson("/api/quick-game/lobby/{$lobbyId}/invite", [
            'playerId' => $this->friendPlayer->id,
        ])->assertOk();

        Sanctum::actingAs($this->friend);
        $this->postJson("/api/quick-game/lobby/{$lobbyId}/join")->assertOk();
        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ready")->assertOk();

        Sanctum::actingAs($this->host);
        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ready")->assertOk();
        $this->postJson("/api/quick-game/lobby/{$lobbyId}/start", [
            'matchFormat' => ['legsToWinSet' => 2, 'setsToWinMatch' => 1, 'startingScore' => 501],
            'gameType' => '501',
            'scoringMode' => 'each_own',
        ])->assertOk();

        return $lobbyId;
    }
}
