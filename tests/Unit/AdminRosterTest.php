<?php

namespace Tests\Unit;

use App\Domain\AdminRoster;
use DomainException;
use PHPUnit\Framework\TestCase;

class AdminRosterTest extends TestCase
{
    public function test_allows_remove_when_more_than_one_admin(): void
    {
        AdminRoster::assertCanRemove(2, 'Organizacja');
        $this->addToAssertionCount(1);
    }

    public function test_rejects_remove_when_last_admin(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Organizacja musi mieć co najmniej jednego administratora.');
        AdminRoster::assertCanRemove(1, 'Organizacja');
    }

    public function test_rejects_remove_when_zero_admins(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Sezon musi mieć co najmniej jednego administratora.');
        AdminRoster::assertCanRemove(0, 'Sezon');
    }
}
