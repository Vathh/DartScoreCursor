<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupStanding extends Model
{
    protected $fillable = [
        'tournament_id',
        'group_number',
        'player_id',
        'matchesPlayed',
        'matches_won',
        'matches_lost',
        'legs_won',
        'legs_lost',
        'points',
        'legs_difference'
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
