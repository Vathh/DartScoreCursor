<?php

namespace App\Models\QuickGame;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickGameSession extends Model
{
    protected $fillable = [
        'lobby_id',
        'host_user_id',
        'scoring_mode',
        'state',
    ];

    protected $casts = [
        'state' => 'array',
    ];

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(QuickGameLobby::class, 'lobby_id');
    }
}


