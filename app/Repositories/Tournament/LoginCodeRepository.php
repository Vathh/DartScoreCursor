<?php

namespace App\Repositories\Tournament;

use App\Models\Tournament\LoginCode;
use Illuminate\Support\Collection;

class LoginCodeRepository
{
    public function findByCodeWithTournament(string $code): ?LoginCode
    {
        return LoginCode::query()
            ->with(['tournament.season.organization'])
            ->where('code', $code)
            ->first();
    }

    /**
     * @return Collection<int, string>
     */
    public function findCodesByTournamentId(int $tournamentId): Collection
    {
        return LoginCode::query()
            ->where('tournament_id', $tournamentId)
            ->orderBy('id')
            ->pluck('code');
    }

    public function save(Collection $codes, int $tournamentId): void
    {
        $codesToInsert = [];

        foreach ($codes as $code) {
            $codesToInsert[] = [
                'code' => $code,
                'expires_at' => null,
                'tournament_id' => $tournamentId,
            ];
        }

        LoginCode::insert($codesToInsert);
    }

    public function revokeForTournament(int $tournamentId): void
    {
        $codes = LoginCode::query()
            ->where('tournament_id', $tournamentId)
            ->get();

        foreach ($codes as $code) {
            $code->tokens()->delete();
        }

        LoginCode::query()
            ->where('tournament_id', $tournamentId)
            ->delete();
    }
}
