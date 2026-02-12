<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuickGameLobby extends Model
{
    protected $fillable = [
        'host_id',
        'code',
        'status',
        'legs_count',
        'game_type',
        'scoring_mode',
        'started_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lobby) {
            if (empty($lobby->code)) {
                $lobby->code = strtoupper(Str::random(6));
            }
        });
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(QuickGameLobbyPlayer::class, 'lobby_id')->orderBy('created_at');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(QuickGameLobbyInvitation::class, 'lobby_id');
    }

    public function session(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\QuickGameSession::class, 'lobby_id');
    }
}
