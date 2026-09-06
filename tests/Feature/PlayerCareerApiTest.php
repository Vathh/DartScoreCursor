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

    public function test_tables_follow_source_and_window_filters(): void
    {
        $this->insertSnapshot(CareerSource::Quick, 'x01', [
            'average' => 60.0,
            'darts_thrown' => 3,
            'points' => 60,
            'visit_scores' => ['180' => 1, '170' => 0, '140' => 0, '100' => 0, '80' => 0, '60' => 0],
            'checkouts' => [['score' => 120, 'darts' => 2]],
            'closed_legs' => [['darts' => 12]],
            'double_tracked' => false,
        ]);
        $this->insertSnapshot(CareerSource::Tournament, 'x01', [
            'average' => 90.0,
            'darts_thrown' => 3,
            'points' => 90,
            'visit_scores' => ['180' => 0, '170' => 1, '140' => 0, '100' => 0, '80' => 0, '60' => 0],
            'checkouts' => [['score' => 40, 'darts' => 1]],
            'closed_legs' => [['darts' => 18]],
            'double_tracked' => false,
        ]);
        $this->insertSnapshot(CareerSource::League, 'x01', [
            'average' => 75.0,
            'darts_thrown' => 3,
            'points' => 75,
            'visit_scores' => ['180' => 0, '170' => 0, '140' => 0, '100' => 0, '80' => 0, '60' => 0],
            'checkouts' => [],
            'closed_legs' => [['darts' => 15]],
            'double_tracked' => false,
        ]);
        $this->insertSnapshot(CareerSource::Training, 'x01', [
            'average' => 99.0,
            'darts_thrown' => 3,
            'points' => 99,
            'visit_scores' => ['180' => 2, '170' => 0, '140' => 0, '100' => 0, '80' => 0, '60' => 0],
            'checkouts' => [['score' => 170, 'darts' => 3]],
            'closed_legs' => [['darts' => 9]],
            'double_tracked' => false,
        ]);
        $this->insertSnapshot(CareerSource::Quick, 'x01', [
            'average' => 40.0,
            'darts_thrown' => 3,
            'points' => 40,
            'visit_scores' => ['180' => 0, '170' => 1, '140' => 0, '100' => 0, '80' => 0, '60' => 0],
            'checkouts' => [],
            'closed_legs' => [],
            'double_tracked' => false,
        ], now()->subDays(200));

        Sanctum::actingAs($this->owner);

        $all = $this->getJson('/api/players/'.$this->ownerPlayer->id.'/career?window=all&source=all')
            ->assertOk();
        $this->assertSame(5, $all->json('table.games'));
        $this->assertSame(3, $all->json('table.count_max'));
        $this->assertSame(170, $all->json('table.highest_hf'));
        $this->assertSame(9, $all->json('table.fastest_qf'));
        $this->assertSame(2, $all->json('table.count_170_plus'));
        $this->assertSame(2, $all->json('table.count_hf'));
        $this->assertSame(4, $all->json('table.count_qf'));

        $quick = $this->getJson('/api/players/'.$this->ownerPlayer->id.'/career?window=all&source=quick')
            ->assertOk();
        $this->assertSame(2, $quick->json('table.games'));
        $this->assertSame(1, $quick->json('table.count_max'));
        $this->assertSame(120, $quick->json('table.highest_hf'));

        $tournament = $this->getJson('/api/players/'.$this->ownerPlayer->id.'/career?window=all&source=tournament')
            ->assertOk();
        $this->assertSame(2, $tournament->json('table.games'));
        $this->assertSame(1, $tournament->json('table.count_170_plus'));
        $this->assertSame(15, $tournament->json('table.fastest_qf'));
        $this->assertSame(2, $tournament->json('table.count_qf'));

        $training = $this->getJson('/api/players/'.$this->ownerPlayer->id.'/career?window=all&source=training')
            ->assertOk();
        $this->assertSame(1, $training->json('table.games'));
        $this->assertSame(2, $training->json('table.count_max'));
        $this->assertSame(170, $training->json('table.highest_hf'));
        $this->assertSame(1, $training->json('hero.games'));

        $windowed = $this->getJson('/api/players/'.$this->ownerPlayer->id.'/career?window=90d&source=quick')
            ->assertOk();
        $this->assertSame(1, $windowed->json('table.games'));
        $this->assertSame(1, $windowed->json('table.count_max'));
    }

    public function test_overview_split_is_90d_without_training(): void
    {
        $this->insertSnapshot(CareerSource::Quick, 'x01', [
            'average' => 60.0,
            'darts_thrown' => 3,
            'points' => 60,
            'visit_scores' => ['180' => 1, '170' => 0, '140' => 0, '100' => 0, '80' => 0, '60' => 0],
            'checkouts' => [['score' => 120, 'darts' => 2]],
            'closed_legs' => [['darts' => 12]],
            'double_tracked' => false,
        ]);
        $this->insertSnapshot(CareerSource::Tournament, 'x01', [
            'average' => 90.0,
            'darts_thrown' => 3,
            'points' => 90,
            'visit_scores' => ['180' => 0, '170' => 1, '140' => 0, '100' => 0, '80' => 0, '60' => 0],
            'checkouts' => [],
            'closed_legs' => [['darts' => 15]],
            'double_tracked' => false,
        ]);
        $this->insertSnapshot(CareerSource::Training, 'x01', [
            'average' => 99.0,
            'darts_thrown' => 3,
            'points' => 99,
            'visit_scores' => ['180' => 2, '170' => 0, '140' => 0, '100' => 0, '80' => 0, '60' => 0],
            'checkouts' => [['score' => 170, 'darts' => 3]],
            'closed_legs' => [['darts' => 9]],
            'double_tracked' => false,
        ]);
        $this->insertSnapshot(CareerSource::Quick, 'x01', [
            'average' => 40.0,
            'darts_thrown' => 3,
            'points' => 40,
            'visit_scores' => ['180' => 0, '170' => 1, '140' => 0, '100' => 0, '80' => 0, '60' => 0],
            'checkouts' => [],
            'closed_legs' => [],
            'double_tracked' => false,
        ], now()->subDays(200));

        $split = app(\App\Services\Career\PlayerCareerStatsService::class)
            ->buildOverviewSplit($this->ownerPlayer);

        $this->assertSame('90d', $split['window']);
        $this->assertSame(1, $split['quick']['games']);
        $this->assertSame(1, $split['quick']['count_max']);
        $this->assertSame(120, $split['quick']['highest_hf']);
        $this->assertSame(1, $split['tournament']['games']);
        $this->assertSame(1, $split['tournament']['count_170_plus']);
        $this->assertSame(15, $split['tournament']['fastest_qf']);
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function insertSnapshot(
        CareerSource $source,
        string $gameType,
        array $metrics,
        ?\DateTimeInterface $occurredAt = null,
    ): void {
        PlayerGameSnapshot::create([
            'player_id' => $this->ownerPlayer->id,
            'source' => $source,
            'game_type' => $gameType,
            'occurred_at' => $occurredAt ?? now(),
            'client_uuid' => $source === CareerSource::Training ? (string) \Illuminate\Support\Str::uuid() : null,
            'sourceable_type' => null,
            'sourceable_id' => null,
            'metrics' => $metrics,
        ]);
    }
}
