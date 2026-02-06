<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupStanding extends Model
{
    protected $fillable = [
        'tournament_id',
        'group_number',
        'player_id',
        'matches_played',
        'matches_won',
        'matches_lost',
        'legs_won',
        'legs_lost',
        'points',
        'place',
    ];

    protected $casts = [
        'status' => GameStatus::class
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
