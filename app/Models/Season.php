<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{

    protected static function booted(): void
    {
        static::created(function ($season) {
            $season->league?->touch();
        });

        static::updated(function ($season) {
            $season->league?->touch();
        });
    }

    protected $fillable = [
      'league_id',
      'name',
      'start_date',
      'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'season_user_admin', 'season_id', 'user_id');
    }

    public function relatedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'season_user');
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }
}
