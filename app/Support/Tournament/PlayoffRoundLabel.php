<?php

namespace App\Support\Tournament;

use App\Enums\GameStage;

/**
 * Etykiety rund playoff (SE używa wartości GameStage; DE: W0, L1, GF, …).
 */
final class PlayoffRoundLabel
{
    public static function label(string $round): string
    {
        $stage = GameStage::tryFrom($round);
        if ($stage !== null) {
            return $stage->label();
        }

        if ($round === 'GF' || $round === 'GF1') {
            return 'Grand Final';
        }
        if ($round === 'GF2') {
            return 'Grand Final (reset)';
        }
        if (preg_match('/^W(\d+)$/', $round, $m)) {
            return 'WB R'.((int) $m[1] + 1);
        }
        if (preg_match('/^L(\d+)$/', $round, $m)) {
            return 'LB R'.((int) $m[1] + 1);
        }

        return $round;
    }

    /** Etykieta dla widza (overlay / transmisja) — bez skrótów WB/LB. */
    public static function broadcastLabel(string $round): string
    {
        $stage = GameStage::tryFrom($round);
        if ($stage !== null) {
            return $stage->label();
        }

        if ($round === 'GF' || $round === 'GF1') {
            return 'Wielki finał';
        }
        if ($round === 'GF2') {
            return 'Wielki finał — reset';
        }
        if (preg_match('/^W(\d+)$/', $round, $m)) {
            return 'Runda '.((int) $m[1] + 1).' drabinki zwycięzców';
        }
        if (preg_match('/^L(\d+)$/', $round, $m)) {
            return 'Runda '.((int) $m[1] + 1).' drabinki przegranych';
        }

        return $round;
    }
}
