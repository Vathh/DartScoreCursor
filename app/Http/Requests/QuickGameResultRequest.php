<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickGameResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gameId' => 'nullable|integer|exists:quick_games,id',
            'lobbyId' => 'nullable|integer|exists:quick_game_lobbies,id',
            'players' => 'nullable|array|min:1|max:6',
            'players.*.playerId' => 'required_with:players|integer|exists:players,id',
            'players.*.score' => 'required_with:players|integer|min:0',
            'players.*.place' => 'nullable|integer|min:1',
            'players.*.average' => 'nullable|numeric',
            'players.*.dartsThrown' => 'nullable|integer|min:0',
            'players.*.pointsEarned' => 'nullable|integer|min:0',
            'achievements' => 'nullable|array',
            'achievements.*.playerId' => 'required|integer|exists:players,id',
            'achievements.*.type' => 'required|string',
            'achievements.*.value' => 'nullable|integer',
        ];
    }
}
