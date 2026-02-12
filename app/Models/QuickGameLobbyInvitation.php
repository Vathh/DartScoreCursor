<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickGameLobbyInvitation extends Model
{
    protected $fillable = [
        'lobby_id',
        'invited_player_id',
        'status',
    ];

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(QuickGameLobby::class, 'lobby_id');
    }

    public function invitedPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'invited_player_id');
    }
}
