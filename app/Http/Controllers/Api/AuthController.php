<?php

namespace App\Http\Controllers\Api;

use App\Models\LoginCode;
use Illuminate\Http\Request;

class AuthController
{

    public function __construct()
    {
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
           'code' => ['required|string'],
        ]);

        $loginCode = LoginCode::where('code', $validated['code'])->first();

        $token = $loginCode->createToken('counter')->plainTextToken;

        return response()->json([
            'token' => $token,
            'tournamentId' => $loginCode->tournament_id,
        ]);
    }
}
