<?php

namespace App\Http\Requests;

use App\DTO\GameResultDTO;
use App\DTO\UpdateGameDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GameResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $gameType = $this->input('game.type');

        $rules = [
            'game.id' => 'required|integer',
            'game.type' => 'required|string|in:group,playoff', // Quick games są w osobnym endpoincie
            'game.player1Id' => 'required|integer|exists:players,id',
            'game.player2Id' => 'required|integer|exists:players,id',
            'game.player1Score' => 'required|integer',
            'game.player2Score' => 'required|integer',
            'game.winnerId' => 'required|integer|exists:players,id',
            'game.tournamentId' => 'required|integer|exists:tournaments,id',
            'game.groupNumber' => 'required|integer',
        ];

        $additionalRules = [
            'achievements' => 'array',
            'achievements.*.playerId' => 'required|integer|exists:players,id',
            'achievements.*.tournamentId' => 'required|integer|exists:tournaments,id',
            'achievements.*.value' => 'nullable|integer',
            'achievements.*.type' => 'required|string',
            'legs' => 'nullable|array',
            'legs.*.legNumber' => 'required|integer|min:1',
            'legs.*.player1Score' => 'required|integer|min:0',
            'legs.*.player2Score' => 'required|integer|min:0',
            'legs.*.winnerId' => 'required|integer|exists:players,id',
            'legs.*.player1Average' => 'nullable|integer|min:0',
            'legs.*.player2Average' => 'nullable|integer|min:0',
            'legs.*.player1DartsThrown' => 'nullable|integer|min:1',
            'legs.*.player2DartsThrown' => 'nullable|integer|min:1',
            'legs.*.checkoutScore' => 'nullable|integer|min:2|max:170',
            'legs.*.startedAt' => 'nullable|date',
            'legs.*.finishedAt' => 'nullable|date',
        ];

        return array_merge($rules, $additionalRules);
    }

    public function toDTO(): UpdateGameDTO
    {
        return UpdateGameDTO::fromArray($this->validated());
    }
}
