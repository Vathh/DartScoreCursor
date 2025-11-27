<?php

namespace App\Services;

use App\Models\LoginCode;
use Illuminate\Support\Collection;

class LoginCodeService
{

    public function __construct()
    {
    }

    public function generateCodes(int $amount, int $tournamentId): Collection
    {
        $result = collect();

        for ($i = 0; $i < $amount; $i++) {
            $result->push(LoginCode::generate());
        }


    }
}
