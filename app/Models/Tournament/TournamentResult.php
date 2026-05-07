<?php

namespace App\Models\Tournament;

use App\Enums\GameStage;
use App\Models\Player\Player;
use App\Models\Season\Season;
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


