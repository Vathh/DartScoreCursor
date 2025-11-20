<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class LoginCode extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'code',
    ];
}
