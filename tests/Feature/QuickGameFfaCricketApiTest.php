<?php

namespace Tests\Feature;

use App\Models\Player\Player;
use App\Models\QuickGame\QuickGame;
use App\Models\QuickGame\QuickGameFfaSession;
use App\Models\Users\User;
use App\Services\Player\PlayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuickGameFfaCricketApiTest extends TestCase
{
    use RefreshDatabase;

    private User $host;

    private User $friend;

    private Player $hostPlayer;

    private Player $friendPlayer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = User::factory()->create(['email' => 'cricket-host@test.com']);
        $this->friend = User::factory()->create(['email' => 'cricket-friend@test.com']);

        $playerService = app(PlayerService::class);
        $playerService->create('Host', $this->host->id);
        $playerService->create('Friend', $this->friend->id);

        $this->hostPlayer = Player::where('user_id', $this->host->id)->first();
        $this->friendPlayer = Player::where('user_id', $this->friend->id)->first();

        Sanctum::actingAs($this->host);
        $this->postJson('/api/friends/add', ['friendId' => $this->friend->id])->assertCreated();
    }

    public function test_cricket_start_and_visit_sync(): void
    {
        $lobbyId = $this->startCricketLobby('each_own');

        $session = QuickGameFfaSession::where('lobby_id', $lobbyId)->first();
        $this->assertSame('cricket', $session->game_type);
        $this->assertIsArray($session->cricket_state);

        $this->getJson("/api/quick-game/lobby/{$lobbyId}/ffa/state")
            ->assertOk()
            ->assertJsonPath('format', 'ffa_cricket')
            ->assertJsonPath('session.gameType', 'cricket')
            ->assertJsonPath('turn.dartsInVisit', 0);

        Sanctum::actingAs($this->host);
        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ffa/cricket/visits", [
            'playerId' => $this->hostPlayer->id,
            'clientVisitId' => (string) Str::uuid(),
            'darts' => [
                [
                    'kind' => 'hit',
                    'segment' => '20',
                    'multiplier' => 3,
                    'clientDartId' => (string) Str::uuid(),
                ],
                ['kind' => 'miss', 'clientDartId' => (string) Str::uuid()],
                ['kind' => 'miss', 'clientDartId' => (string) Str::uuid()],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('players.0.hits.20', 3)
            ->assertJsonPath('turn.dartsInVisit', 0)
            ->assertJsonPath('session.currentPlayerIndex', 1);

        Sanctum::actingAs($this->friend);
        $this->getJson("/api/quick-game/lobby/{$lobbyId}/ffa/state")
            ->assertOk()
            ->assertJsonPath('you.canInput', true)
            ->assertJsonPath('you.myPlayerIndex', 1)
            ->assertJsonPath('session.currentPlayerIndex', 1);

        Sanctum::actingAs($this->host);
        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ffa/cricket/visits/undo")
            ->assertOk()
            ->assertJsonPath('players.0.hits.20', 0)
            ->assertJsonPath('turn.dartsInVisit', 0)
            ->assertJsonPath('session.currentPlayerIndex', 0);
    }

    public function test_cricket_miss_visit_rotates_after_three_darts(): void
    {
        $lobbyId = $this->startCricketLobby('each_own');

        Sanctum::actingAs($this->host);
        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ffa/cricket/visits", [
            'playerId' => $this->hostPlayer->id,
            'clientVisitId' => (string) Str::uuid(),
            'darts' => [
                ['kind' => 'miss', 'clientDartId' => (string) Str::uuid()],
                ['kind' => 'miss', 'clientDartId' => (string) Str::uuid()],
                ['kind' => 'miss', 'clientDartId' => (string) Str::uuid()],
            ],
        ])->assertOk();

        $this->getJson("/api/quick-game/lobby/{$lobbyId}/ffa/state")
            ->assertOk()
            ->assertJsonPath('session.currentPlayerIndex', 1)
            ->assertJsonPath('turn.dartsInVisit', 0);
    }

    public function test_incomplete_cricket_visit_rejected(): void
    {
        $lobbyId = $this->startCricketLobby('each_own');

        Sanctum::actingAs($this->host);
        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ffa/cricket/visits", [
            'playerId' => $this->hostPlayer->id,
            'clientVisitId' => (string) Str::uuid(),
            'darts' => [
                ['kind' => 'miss', 'clientDartId' => (string) Str::uuid()],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Wizyta krykieta musi mieć 3 rzuty albo zakończyć lega.');
    }

    public function test_cricket_broadcast_omits_viewer_you(): void
    {
        \Illuminate\Support\Facades\Event::fake([\App\Events\QuickGameFfaStateUpdated::class]);

        $lobbyId = $this->startCricketLobby('each_own');

        Sanctum::actingAs($this->host);
        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ffa/cricket/visits", [
            'playerId' => $this->hostPlayer->id,
            'clientVisitId' => (string) Str::uuid(),
            'darts' => [
                ['kind' => 'miss', 'clientDartId' => (string) Str::uuid()],
                ['kind' => 'miss', 'clientDartId' => (string) Str::uuid()],
                ['kind' => 'miss', 'clientDartId' => (string) Str::uuid()],
            ],
        ])->assertOk();

        \Illuminate\Support\Facades\Event::assertDispatched(
            \App\Events\QuickGameFfaStateUpdated::class,
            fn ($event) => ! array_key_exists('you', $event->state)
                && ($event->state['session']['currentPlayerIndex'] ?? null) === 1,
        );
    }

    public function test_cricket_x01_visits_rejected(): void
    {
        $lobbyId = $this->startCricketLobby('one_device');

        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ffa/visits", [
            'playerId' => $this->hostPlayer->id,
            'score' => 60,
            'remainingBefore' => 501,
            'remainingAfter' => 441,
            'dartsInVisit' => 3,
            'closedLeg' => false,
            'bust' => false,
            'clientVisitId' => (string) Str::uuid(),
        ])->assertStatus(422);
    }

    public function test_cricket_finish_creates_quick_game(): void
    {
        $lobbyId = $this->startCricketLobby('one_device', legsToWin: 1);

        $session = QuickGameFfaSession::where('lobby_id', $lobbyId)->firstOrFail();
        $closed = \App\Domain\QuickGame\CricketRules::emptyHits();
        foreach (array_keys($closed) as $key) {
            $closed[$key] = 3;
        }
        $session->cricket_state = [
            'boards' => [
                (string) $this->hostPlayer->id => ['hits' => $closed, 'points' => 20],
                (string) $this->friendPlayer->id => [
                    'hits' => \App\Domain\QuickGame\CricketRules::emptyHits(),
                    'points' => 0,
                ],
            ],
            'dartsInVisit' => 0,
            'dartLog' => [],
        ];
        $session->current_player_index = 0;
        $session->save();

        // Rzut po stanie wygrywającym — serwer wykrywa koniec lega/meczu.
        $this->postJson("/api/quick-game/lobby/{$lobbyId}/ffa/cricket/visits", [
            'playerId' => $this->hostPlayer->id,
            'clientVisitId' => (string) Str::uuid(),
            'darts' => [
                [
                    'kind' => 'hit',
                    'segment' => '20',
                    'multiplier' => 1,
                    'clientDartId' => (string) Str::uuid(),
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('game.status', 'finished')
            ->assertJsonPath('session.status', 'finished');

        $session->refresh();
        $this->assertNotNull($session->quick_game_id);
        $this->assertDatabaseHas('quick_games', [
            'id' => $session->quick_game_id,
            'game_type' => 'cricket',
        ]);
        $this->assertDatabaseHas('quick_game_results', [
            'quick_game_id' => $session->quick_game_id,
            'player_id' => $this->hostPlayer->id,
            'score' => 1,
            'place' => 1,
        ]);
        $this->assertNotNull(QuickGame::find($session->quick_game_id));
    }

    private function startCricketLobby(string $scoringMode, int $legsToWin = 2): int
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
            'matchFormat' => [
                'legsToWinSet' => $legsToWin,
                'setsToWinMatch' => 1,
                'startingScore' => 501,
                'gameType' => 'cricket',
            ],
            'gameType' => 'cricket',
            'scoringMode' => $scoringMode,
        ])->assertOk();

        return $lobbyId;
    }
}
