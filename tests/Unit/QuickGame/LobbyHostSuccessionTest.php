<?php

namespace Tests\Unit\QuickGame;

use App\Domain\QuickGame\LobbyHostSuccession;
use PHPUnit\Framework\TestCase;

class LobbyHostSuccessionTest extends TestCase
{
    public function test_picks_first_remaining_registered_user(): void
    {
        $next = LobbyHostSuccession::nextHostUserId([
            ['userId' => 10, 'isRegistered' => true],
            ['userId' => 20, 'isRegistered' => true],
            ['userId' => null, 'isRegistered' => false],
        ], 10);

        $this->assertSame(20, $next);
    }

    public function test_skips_guests(): void
    {
        $next = LobbyHostSuccession::nextHostUserId([
            ['userId' => 99, 'isRegistered' => false],
            ['userId' => 20, 'isRegistered' => true],
        ], 10);

        $this->assertSame(20, $next);
    }

    public function test_returns_null_when_only_guests_remain(): void
    {
        $next = LobbyHostSuccession::nextHostUserId([
            ['userId' => null, 'isRegistered' => false],
        ], 10);

        $this->assertNull($next);
    }
}
