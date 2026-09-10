<?php

namespace Tests\Feature;

use App\Models\Tournament\LoginCode;
use App\Models\Tournament\Tournament;
use App\Models\Users\User;
use App\Enums\TournamentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_login_is_throttled_after_five_attempts(): void
    {
        User::factory()->create([
            'email' => 'throttle@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/account/login', [
                'email' => 'throttle@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        $this->postJson('/api/account/login', [
            'email' => 'throttle@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_web_login_is_throttled_after_five_attempts(): void
    {
        User::factory()->create([
            'email' => 'web-throttle@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->from(route('pages.loginPanel'))
                ->post('/login', [
                    'email' => 'web-throttle@example.com',
                    'password' => 'wrong-password',
                ])
                ->assertRedirect();
        }

        $this->from(route('pages.loginPanel'))
            ->post('/login', [
                'email' => 'web-throttle@example.com',
                'password' => 'wrong-password',
            ])
            ->assertStatus(429);
    }

    public function test_tablet_login_allows_twenty_concurrent_attempts(): void
    {
        $tournament = Tournament::create([
            'name' => 'Tablet throttle',
            'season_id' => null,
            'date' => '2024-06-01',
            'status' => TournamentStatus::GROUP,
        ]);
        LoginCode::create([
            'code' => 'THROTTL1',
            'tournament_id' => $tournament->id,
        ]);

        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/login', ['code' => 'THROTTL1'])->assertOk();
        }
    }
}
