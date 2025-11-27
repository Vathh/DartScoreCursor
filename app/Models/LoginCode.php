<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class LoginCode extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'code',
        'tournament_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public static function generate(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = collect(range(1,6))
            ->map(fn() => $alphabet[random_int(0, strlen($alphabet)-1)])
            ->join('');

        if(LoginCode::where('code', $code)->exists()){
            $code = self::generate();
        }

        return $code;
    }
}
