<?php

namespace App\Rules;

use App\Models\Player;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UniquePlayerInSeasonAndLeague implements ValidationRule
{
    private int $seasonId;
    private int $leagueId;

    /**
     * @param int $seasonId
     * @param int $leagueId
     */
    public function __construct(int $seasonId, int $leagueId)
    {
        $this->seasonId = $seasonId;
        $this->leagueId = $leagueId;
    }


    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Player::where('name', $value)
            ->where(function($query) {
                $query->where('season_id', $this->seasonId)
                    ->orWhere('league_id', $this->leagueId);
            })
            ->exists();

        if ($exists) {
            $fail('Gracz o tej nazwie już istnieje.');
        }
    }
}
