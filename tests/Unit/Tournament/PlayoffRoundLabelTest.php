<?php

namespace Tests\Unit\Tournament;

use App\Enums\GameStage;
use App\Support\Tournament\PlayoffRoundLabel;
use PHPUnit\Framework\TestCase;

class PlayoffRoundLabelTest extends TestCase
{
    public function test_short_label_keeps_wb_abbreviation(): void
    {
        $this->assertSame('WB R1', PlayoffRoundLabel::label('W0'));
        $this->assertSame('LB R2', PlayoffRoundLabel::label('L1'));
        $this->assertSame('Grand Final', PlayoffRoundLabel::label('GF'));
        $this->assertSame('Finał', PlayoffRoundLabel::label(GameStage::FINAL->value));
    }

    public function test_broadcast_label_spells_out_brackets(): void
    {
        $this->assertSame('Runda 1 drabinki zwycięzców', PlayoffRoundLabel::broadcastLabel('W0'));
        $this->assertSame('Runda 2 drabinki przegranych', PlayoffRoundLabel::broadcastLabel('L1'));
        $this->assertSame('Wielki finał', PlayoffRoundLabel::broadcastLabel('GF'));
        $this->assertSame('Wielki finał — reset', PlayoffRoundLabel::broadcastLabel('GF2'));
        $this->assertSame('1/8 finału', PlayoffRoundLabel::broadcastLabel(GameStage::EIGHT->value));
        $this->assertSame('Finał', PlayoffRoundLabel::broadcastLabel(GameStage::FINAL->value));
    }
}
