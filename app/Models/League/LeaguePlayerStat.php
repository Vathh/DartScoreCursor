<?php

namespace App\Models\League;

use App\Models\Player\Player;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaguePlayerStat extends Model
{
    protected $table = 'league_player_stats';

    protected $fillable = [
        'league_id',
        'player_id',
        'points',
        'count_max',
        'count_170_plus',
        'count_qf',
        'count_hf',
        'best_qf',
        'best_hf',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}


