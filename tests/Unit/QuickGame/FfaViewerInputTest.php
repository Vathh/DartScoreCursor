<?php

namespace Tests\Unit\QuickGame;

use App\Domain\QuickGame\FfaViewerInput;
use PHPUnit\Framework\TestCase;

class FfaViewerInputTest extends TestCase
{
    public function test_one_device_host_can_input_current_turn(): void
    {
        $view = FfaViewerInput::resolve('one_device', true, 9, 9, 10, [10, 20], 1);

        $this->assertTrue($view['canInput']);
        $this->assertSame(1, $view['myPlayerIndex']);
    }

    public function test_one_device_guest_cannot_input(): void
    {
        $view = FfaViewerInput::resolve('one_device', true, 9, 8, 20, [10, 20], 0);

        $this->assertFalse($view['canInput']);
        $this->assertNull($view['myPlayerIndex']);
    }

    public function test_each_own_only_on_own_turn(): void
    {
        $own = FfaViewerInput::resolve('each_own', true, 9, 1, 20, [10, 20], 1);
        $wait = FfaViewerInput::resolve('each_own', true, 9, 1, 20, [10, 20], 0);

        $this->assertTrue($own['canInput']);
        $this->assertSame(1, $own['myPlayerIndex']);
        $this->assertFalse($wait['canInput']);
        $this->assertSame(1, $wait['myPlayerIndex']);
    }

    public function test_finished_session_cannot_input(): void
    {
        $view = FfaViewerInput::resolve('each_own', false, 9, 1, 10, [10, 20], 0);

        $this->assertFalse($view['canInput']);
        $this->assertSame(0, $view['myPlayerIndex']);
    }
}
