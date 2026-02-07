<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameExternalRef extends Model
{
    protected $fillable = [
        'game_id',
        'source',
        'external_id',
        'external_url',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
