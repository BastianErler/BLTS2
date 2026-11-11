<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonParticipation extends Model
{
    protected $fillable = ['season_id', 'user_id', 'joined_at', 'withdrawal_requested_at', 'withdrawn_at', 'withdrawn_by', 'frozen_cost'];

    protected $casts = [
        'joined_at' => 'datetime',
        'withdrawal_requested_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'frozen_cost' => 'decimal:2',
    ];
}
