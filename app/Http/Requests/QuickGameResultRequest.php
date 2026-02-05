<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuickGameResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Quick games mogą być bez autoryzacji (gracze tymczasowi)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // Lista wyników graczy (tylko zarejestrowanych)
            'players' => 'required|array|min:2|max:6',
            'players.*.playerId' => 'required|integer|exists:players,id',
            'players.*.score' => 'required|integer|min:0',
            'players.*.place' => 'nullable|integer|min:1',
            'players.*.average' => 'nullable|numeric|min:0|max:999.99',
            'players.*.dartsThrown' => 'nullable|integer|min:1',
            'players.*.pointsEarned' => 'nullable|integer|min:0',
            
            // Achievementy
            'achievements' => 'nullable|array',
            'achievements.*.playerId' => 'required|integer|exists:players,id',
            'achievements.*.value' => 'nullable|integer',
            'achievements.*.type' => 'required|string|in:max,one_seventy,hf,qf',
            
            // Szczegóły legów
            'legs' => 'nullable|array',
            'legs' => 'nullable|array',
            'legs.*.legNumber' => 'required|integer|min:1',
            'legs.*.playerId' => 'required|integer|exists:players,id',
            'legs.*.score' => 'required|integer|min:0',
            'legs.*.average' => 'nullable|numeric|min:0',
            'legs.*.dartsThrown' => 'nullable|integer|min:1',
            'legs.*.checkoutScore' => 'nullable|integer|min:2|max:170',
            'legs.*.startedAt' => 'nullable|date',
            'legs.*.finishedAt' => 'nullable|date',
            
            // Opcjonalne lobbyId (jeśli mecz był tworzony przez lobby)
            'lobbyId' => 'nullable|integer|exists:quick_game_lobbies,id',
        ];
    }
}
