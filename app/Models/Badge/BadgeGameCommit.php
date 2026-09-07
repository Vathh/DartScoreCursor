<?php

namespace App\Models\Badge;

use Illuminate\Database\Eloquent\Model;

class BadgeGameCommit extends Model
{
    protected $fillable = [
        'source_kind',
        'source_id',
    ];
}
