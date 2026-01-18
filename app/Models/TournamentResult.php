<?php

namespace App\Models;

use App\Enums\GameStage;
use App\EnumsGameStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentResult extends Model
{
    protected $fillable = [
        'season_id',
        'tournament_id',
        'player_id',
        'points',
        'place',
        'elimination_stage'
    ];

    protected $casts = [
        'elimination_stage' => GameStage::class,
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
