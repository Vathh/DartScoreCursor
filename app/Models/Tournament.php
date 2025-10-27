<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tournament extends Model
{
    protected $fillable = [
      'name',
      'season_id',
      'date'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
