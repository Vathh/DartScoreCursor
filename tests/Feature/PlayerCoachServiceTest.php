<?php

namespace Tests\Feature;

use App\Enums\CareerSource;
use App\Models\Career\PlayerGameSnapshot;
use App\Models\Player\Player;
use App\Models\Users\User;
use App\Services\Career\PlayerCoachService;
use App\Services\Player\PlayerService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerCoachServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Player $ownerPlayer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\PointSchemeSeeder']);

        $this->owner = User::factory()->create();
        app(PlayerService::class)->create('Tom', $this->owner->id);
        $this->ownerPlayer = Player::where('user_id', $this->owner->id)->firstOrFail();
    }

    public function test_guest_player_cannot_have_a_coach(): void
    {
        $guest = Player::create(['name' => 'Gość']);

        $this->expectException(DomainException::class);
        app(PlayerCoachService::class)->buildForOwner($guest);
    }

    public function test_owner_digest_includes_training_and_template_plan(): void
    {
        $this->insertSnapshot(CareerSource::Quick, 'x01', [
            'average' => 50.0,
            'darts_thrown' => 9,
            'points' => 150,
            'double_tracked' => true,
            'double_attempts' => 4,
            'double_successes' => 1,
        ]);
        $this->insertSnapshot(CareerSource::Training, 'bob27', [
            'average' => null,
            'darts_thrown' => 12,
            'points' => 0,
            'double_tracked' => true,
            'double_attempts' => 12,
            'double_successes' => 3,
            'per_double' => [
                'D16' => ['attempts' => 9, 'successes' => 1],
                'D20' => ['attempts' => 3, 'successes' => 2],
            ],
        ]);
        $this->insertSnapshot(CareerSource::Tournament, 'x01', [
            'average' => 48.0,
            'darts_thrown' => 9,
            'points' => 144,
            'double_tracked' => true,
            'double_attempts' => 3,
            'double_successes' => 1,
        ]);

        $payload = app(PlayerCoachService::class)->buildForOwner($this->ownerPlayer);

        $this->assertSame(1, $payload['digest']['schemaVersion']);
        $this->assertSame('90d', $payload['digest']['window']);
        $this->assertTrue($payload['digest']['ready']);
        $this->assertSame(3, $payload['digest']['hero']['games']);
        $this->assertSame(1, $payload['digest']['sources']['training']);
        $this->assertSame('D16', $payload['digest']['bob27']['weakest']);
        $this->assertArrayNotHasKey('playerId', $payload['digest']);
        $this->assertSame('template', $payload['plan']['source']);
        $this->assertCount(3, $payload['plan']['cards']);
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
