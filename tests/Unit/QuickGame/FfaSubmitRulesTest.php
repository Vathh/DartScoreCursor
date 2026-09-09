<?php

namespace Tests\Unit\QuickGame;

use App\Domain\QuickGame\FfaSubmitRules;
use DomainException;
use PHPUnit\Framework\TestCase;

class FfaSubmitRulesTest extends TestCase
{
    public function test_rejects_missing_lobby(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Lobby nie istnieje.');
        FfaSubmitRules::assert(false, 'each_own', 1, 1, 10, 10, [10], []);
    }

    public function test_rejects_left_target_before_device_mode(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ten gracz opuścił mecz.');
        FfaSubmitRules::assert(true, 'one_device', 1, 1, 10, 20, [10, 20], [20]);
    }

    public function test_one_device_allows_host_and_returns(): void
    {
        FfaSubmitRules::assert(true, 'one_device', 1, 1, 10, 20, [10, 20], []);
        $this->addToAssertionCount(1);
    }

    public function test_one_device_rejects_non_host(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('W trybie jednego urządzenia punkty wpisuje tylko host.');
        FfaSubmitRules::assert(true, 'one_device', 1, 2, 10, 10, [10, 20], []);
    }

    public function test_each_own_requires_player_profile(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nie znaleziono gracza.');
        FfaSubmitRules::assert(true, 'each_own', 1, 1, null, 10, [10], []);
    }

    public function test_each_own_rejects_left_submitter_on_undo(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Opuszczono ten mecz — nie możesz wpisywać rzutów.');
        FfaSubmitRules::assert(true, 'each_own', 1, 2, 20, null, [10, 20], [20]);
    }

    public function test_each_own_rejects_foreign_target(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Możesz wpisywać tylko własne rzuty.');
        FfaSubmitRules::assert(true, 'each_own', 1, 1, 10, 20, [10, 20], []);
    }

    public function test_each_own_undo_requires_participant(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nie jesteś uczestnikiem tego meczu.');
        FfaSubmitRules::assert(true, 'each_own', 1, 99, 99, null, [10, 20], []);
    }

    public function test_each_own_own_dart_ok(): void
    {
        FfaSubmitRules::assert(true, 'each_own', 1, 1, 10, 10, [10, 20], []);
        $this->addToAssertionCount(1);
    }
}
