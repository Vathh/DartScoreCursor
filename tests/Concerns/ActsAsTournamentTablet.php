<?php

namespace Tests\Concerns;

use App\Models\Tournament\LoginCode;
use App\Models\Tournament\Tournament;
use Laravel\Sanctum\Sanctum;

trait ActsAsTournamentTablet
{
    protected function makeTournamentTablet(Tournament $tournament, string $code): LoginCode
    {
        return LoginCode::create([
            'code' => $code,
            'tournament_id' => $tournament->id,
        ]);
    }

    protected function actAsTournamentTablet(Tournament $tournament, string $code = 'TABLET01'): LoginCode
    {
        $loginCode = LoginCode::query()
            ->where('tournament_id', $tournament->id)
            ->where('code', $code)
            ->first()
            ?? $this->makeTournamentTablet($tournament, $code);

        Sanctum::actingAs($loginCode);

        return $loginCode;
    }
}
