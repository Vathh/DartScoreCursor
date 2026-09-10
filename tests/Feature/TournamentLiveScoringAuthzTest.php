<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\TournamentStatus;
use App\Models\Game\Game;
use App\Models\Organization\Organization;
use App\Models\Player\Player;
use App\Models\Season\Season;
use App\Models\Tournament\Tournament;
use App\Models\Users\User;
use App\Services\Player\PlayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ActsAsTournamentTablet;
use Tests\TestCase;

class TournamentLiveScoringAuthzTest extends TestCase
{
    use ActsAsTournamentTablet;
    use RefreshDatabase;

    private User $user;

    private Tournament $tournament;

    private Player $player1;

    private Player $player2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'tablet-authz@test.com']);
        app(PlayerService::class)->create('User', $this->user->id);

        $organization = Organization::create(['name' => 'Authz Org', 'description' => 'Test']);
        $organization->admins()->attach($this->user->id);

        $season = Season::create([
            'name' => 'Authz Season',
            'organization_id' => $organization->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);
        $season->admins()->attach($this->user->id);

        $this->tournament = Tournament::create([
            'name' => 'Authz Tournament',
            'season_id' => $season->id,
            'date' => '2024-06-01',
            'status' => TournamentStatus::GROUP,
        ]);

        $this->player1 = Player::where('user_id', $this->user->id)->first();
        $this->player2 = Player::create([
            'name' => 'Opponent',
            'season_id' => $season->id,
            'organization_id' => $organization->id,
        ]);
    }

    public function test_player_account_cannot_record_group_visit(): void
    {
        Sanctum::actingAs($this->user);

        $game = $this->scheduledGroupGame();

        $this->postJson("/api/group-games/{$game->id}/legs", [
            'player1DoubleTracked' => false,
            'player2DoubleTracked' => false,
        ])->assertForbidden();
    }

    public function test_tablet_of_other_tournament_cannot_score(): void
    {
        $other = Tournament::create([
            'name' => 'Other Tournament',
            'season_id' => $this->tournament->season_id,
            'date' => '2024-07-01',
            'status' => TournamentStatus::GROUP,
        ]);
        $this->actAsTournamentTablet($other, 'OTHER001');

        $game = $this->scheduledGroupGame();

        $this->postJson("/api/group-games/{$game->id}/legs", [
            'player1DoubleTracked' => false,
            'player2DoubleTracked' => false,
        ])->assertForbidden();
    }

    public function test_tablet_of_this_tournament_can_score(): void
    {
        $this->actAsTournamentTablet($this->tournament);

        $game = $this->scheduledGroupGame();

        $start = $this->postJson("/api/group-games/{$game->id}/legs", [
            'player1DoubleTracked' => false,
            'player2DoubleTracked' => false,
        ]);
        $start->assertOk();
        $legId = $start->json('currentLeg.id');

        $this->postJson("/api/group-games/{$game->id}/legs/{$legId}/visits", [
            'playerId' => $this->player1->id,
            'score' => 60,
            'remainingBefore' => 501,
            'remainingAfter' => 441,
            'dartsInVisit' => 3,
            'closedLeg' => false,
            'bust' => false,
            'clientVisitId' => (string) Str::uuid(),
        ])->assertOk();
    }

    public function test_player_account_can_view_scoring_state(): void
    {
        $this->actAsTournamentTablet($this->tournament);
        $game = $this->scheduledGroupGame();
        $this->postJson("/api/group-games/{$game->id}/legs", [
            'player1DoubleTracked' => false,
            'player2DoubleTracked' => false,
        ])->assertOk();

        Sanctum::actingAs($this->user);
        $this->getJson("/api/group-games/{$game->id}/scoring/state")->assertOk();
    }

    public function test_finished_tournament_rejects_scoring(): void
    {
        $this->actAsTournamentTablet($this->tournament);
        $this->tournament->update(['status' => TournamentStatus::FINISHED]);

        $game = $this->scheduledGroupGame();

        $this->postJson("/api/group-games/{$game->id}/legs", [
            'player1DoubleTracked' => false,
            'player2DoubleTracked' => false,
        ])->assertForbidden();
    }

    public function test_tablet_login_issues_token_with_thirty_day_expiry(): void
    {
        $this->makeTournamentTablet($this->tournament, 'LOGINCD1');

        $response = $this->postJson('/api/login', ['code' => 'LOGINCD1']);
        $response->assertOk()->assertJsonStructure(['token', 'tournamentId']);

        $plain = $response->json('token');
        $id = (int) explode('|', $plain)[0];
        $pat = PersonalAccessToken::query()->find($id);
        $this->assertNotNull($pat);
        $this->assertNotNull($pat->expires_at);
        $this->assertTrue($pat->expires_at->greaterThan(now()->addDays(29)));
        $this->assertTrue($pat->expires_at->lessThan(now()->addDays(31)));
    }

    public function test_generated_login_code_has_null_expires_at(): void
    {
        $code = app(\App\Services\Tournament\LoginCodeService::class)
            ->generateForTournament($this->tournament->id);

        $this->assertDatabaseHas('login_codes', [
            'tournament_id' => $this->tournament->id,
            'code' => $code,
            'expires_at' => null,
        ]);
    }

    private function scheduledGroupGame(): Game
    {
        return Game::create([
            'tournament_id' => $this->tournament->id,
            'player1_id' => $this->player1->id,
            'player2_id' => $this->player2->id,
            'group_number' => 1,
            'status' => GameStatus::SCHEDULED,
        ]);
    }
}
