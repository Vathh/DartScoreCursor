<?php

namespace App\Models;

use App\Enums\GameStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuickGame extends Model
{
    protected $fillable = [
        'lobby_id', // Powiązanie z lobby (opcjonalne)
        'player1_id', // Zachowane dla kompatybilności wstecznej
        'player2_id', // Zachowane dla kompatybilności wstecznej
        'player1_score', // Zachowane dla kompatybilności wstecznej
        'player2_score', // Zachowane dla kompatybilności wstecznej
        'winner_id',
        'status'
    ];

    protected $casts = [
        'status' => GameStatus::class
    ];

    // Stare relacje - zachowane dla kompatybilności wstecznej
    public function player1(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }

    // Relacja do wyników zarejestrowanych graczy
    public function results(): HasMany
    {
        return $this->hasMany(QuickGameResult::class);
    }

    // Relacja do lobby (jeśli było tworzone przez lobby)
    public function lobby(): BelongsTo
    {
        return $this->belongsTo(QuickGameLobby::class);
    }
}
