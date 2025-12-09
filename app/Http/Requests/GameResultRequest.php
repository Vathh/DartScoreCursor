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
        return [
            'game.id' => 'required|integer|exists:games,id',
            'game.tournamentId' => 'required|integer|exists:tournaments,id',
            'game.player1Score' => 'required|integer',
            'game.player2Score' => 'required|integer',
            'game.winnerId' => 'required|integer|exists:players,id',
            'game.groupNumber' => 'required|integer',

            'achievements' => 'array',
            'achievements.*.playerId' => 'required|integer|exists:players,id',
            'achievements.*.tournamentId' => 'required|integer|exists:tournaments,id',
            'achievements.*.type' => 'required|string'
        ];
    }

    public function toDTO(): UpdateGameDTO
    {
        return UpdateGameDTO::fromArray($this->validated());
    }
}
