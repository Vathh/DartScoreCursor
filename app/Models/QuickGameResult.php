<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickGameResult extends Model
{
    protected $fillable = [
        'quick_game_id',
        'player_id',
        'score',
        'place',
        'average',
        'darts_thrown',
        'points_earned',
    ];

    protected $casts = [
        'score' => 'integer',
        'place' => 'integer',
        'average' => 'decimal:2',
        'darts_thrown' => 'integer',
        'points_earned' => 'integer',
    ];

    public function quickGame(): BelongsTo
    {
        return $this->belongsTo(QuickGame::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
