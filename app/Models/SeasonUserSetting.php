<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeasonUserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'user_id',
        'exclude_from_leaderboard',
        'fee_exempt',
    ];

    protected $casts = [
        'exclude_from_leaderboard' => 'boolean',
        'fee_exempt' => 'boolean',
    ];
}

