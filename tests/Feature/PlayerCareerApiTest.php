<?php

namespace Tests\Feature;

use App\Enums\CareerSource;
use App\Models\Career\PlayerGameSnapshot;
use App\Models\Player\Player;
use App\Models\Users\User;
use App\Services\Player\PlayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlayerCareerApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $viewer;

    private Player $ownerPlayer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\PointSchemeSeeder']);

        $playerService = app(PlayerService::class);

        $this->owner = User::factory()->create();
        $playerService->create('Tom', $this->owner->id);
        $this->ownerPlayer = Player::where('user_id', $this->owner->id)->firstOrFail();

        $this->viewer = User::factory()->create();
        $playerService->create('Viewer', $this->viewer->id);
    }

    public function test_career_hides_training_from_others(): void
    {
        $this->insertSnapshot(CareerSource::Quick, 'x01', [
            'average' => 60.0,
            'darts_thrown' => 3,
            'points' => 60,
            'double_tracked' => false,
            'double_attempts' => null,
            'double_successes' => null,
        ]);
        $this->insertSnapshot(CareerSource::Training, 'x01', [
            'average' => 90.0,
            'darts_thrown' => 3,
            'points' => 90,
            'double_tracked' => false,
            'double_attempts' => null,
            'double_successes' => null,
        ]);

        Sanctum::actingAs($this->viewer);
        $other = $this->getJson('/api/players/'.$this->ownerPlayer->id.'/career?window=all&source=all')
            ->assertOk();
        $this->assertSame(1, $other->json('hero.games'));
        $this->assertEquals(60.0, $other->json('hero.x01Average'));

        Sanctum::actingAs($this->owner);
        $own = $this->getJson('/api/players/'.$this->ownerPlayer->id.'/career?window=all&source=all')
            ->assertOk();
        $this->assertSame(2, $own->json('hero.games'));
        $this->assertEquals(75.0, $own->json('hero.x01Average'));
    }

    public function test_training_post_is_idempotent_by_client_uuid(): void
    {
        Sanctum::actingAs($this->owner);
        $uuid = '11111111-1111-1111-1111-111111111111';
        $payload = [
            'clientUuid' => $uuid,
            'gameType' => 'x01',
            'completedAt' => now()->toIso8601String(),
            'metrics' => [
                'darts_thrown' => 9,
                'points' => 180,
                'double_tracked' => true,
                'double_attempts' => 2,
                'double_successes' => 1,
            ],
        ];

        $this->postJson('/api/training/games', $payload)->assertCreated();
        $this->postJson('/api/training/games', $payload)->assertCreated();

        $this->assertDatabaseCount('training_games', 1);
        $this->assertDatabaseCount('player_game_snapshots', 1);
        $this->assertDatabaseHas('player_game_snapshots', [
            'player_id' => $this->ownerPlayer->id,
            'source' => 'training',
            'client_uuid' => $uuid,
        ]);
    }

    public function test_empty_double_series_is_not_zero(): void
    {
        $this->insertSnapshot(CareerSource::Quick, 'x01', [
            'average' => 45.0,
            'darts_thrown' => 3,
            'points' => 45,
            'double_tracked' => false,
            'double_attempts' => null,
            'double_successes' => null,
        ]);

        Sanctum::actingAs($this->owner);
        $this->getJson('/api/players/'.$this->ownerPlayer->id.'/career?window=all&source=quick')
            ->assertOk()
            ->assertJsonPath('hero.hasDoubles', false)
            ->assertJsonPath('hero.doublePct', null)
            ->assertJsonPath('series.double_pct', []);
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function insertSnapshot(CareerSource $source, string $gameType, array $metrics): void
    {
        PlayerGameSnapshot::create([
            'player_id' => $this->ownerPlayer->id,
            'source' => $source,
            'game_type' => $gameType,
            'occurred_at' => now(),
            'client_uuid' => $source === CareerSource::Training ? (string) \Illuminate\Support\Str::uuid() : null,
            'sourceable_type' => null,
            'sourceable_id' => null,
            'metrics' => $metrics,
        ]);
    }
}
