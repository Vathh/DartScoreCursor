<?php

namespace App\Models;

use App\Enums\GameStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointSchemeRule extends Model
{
    protected $fillable = [
        'point_scheme_id',
        'elimination_stage',
        'place',
        'points'
    ];

    protected $casts = [
        'elimination_stage' => GameStage::class,
    ];

    public function pointScheme(): BelongsTo
    {
        return $this->belongsTo(PointScheme::class);
    }
}
