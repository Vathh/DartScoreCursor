<?php

namespace Tests\Feature;

use App\Models\Friends\FriendshipInvitation;
use App\Models\Users\User;
use App\Services\Friends\FriendshipService;
use App\Services\Player\PlayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FriendsPanelWebTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $friend;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $playerService = app(PlayerService::class);
        $this->user = User::factory()->create(['email' => 'panel-owner@test.com']);
        $this->friend = User::factory()->create(['email' => 'lazy-panel-friend@test.com']);
        $playerService->create('Panel Owner', $this->user->id);
        $playerService->create('LazyPanelFriend', $this->friend->id);
        app(FriendshipService::class)->addFriend($this->user->id, $this->friend->id);
    }

    public function test_guest_is_redirected_from_friends_panel(): void
    {
        $this->get(route('friends.panel'))
            ->assertRedirect(route('pages.loginPanel'));
    }

    public function test_home_page_shows_count_but_not_friend_list(): void
    {
        $this->actingAs($this->user)
            ->get(route('pages.home'))
            ->assertOk()
            ->assertSee('Znajomi (1)', false)
            ->assertSee(route('friends.panel'), false)
            ->assertDontSee('LazyPanelFriend')
            ->assertDontSee('Brak znajomych');
    }

    public function test_home_page_does_not_query_friendship_invitations(): void
    {
        $sql = [];
        DB::listen(function ($query) use (&$sql) {
            $sql[] = $query->sql;
        });

        $this->actingAs($this->user)->get(route('pages.home'))->assertOk();

        $joined = implode("\n", $sql);
        $this->assertStringNotContainsString('friendship_invitations', $joined);
    }

    public function test_panel_fragment_lists_friends_and_invitations(): void
    {
        $sender = User::factory()->create(['email' => 'panel-inviter@test.com']);
        app(PlayerService::class)->create('PanelInviter', $sender->id);
        FriendshipInvitation::create([
            'sender_id' => $sender->id,
            'receiver_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)
            ->get(route('friends.panel'))
            ->assertOk()
            ->assertSee('LazyPanelFriend')
            ->assertSee('PanelInviter')
            ->assertSee('Akceptuj')
            ->assertDontSee('<html', false);
    }
}
