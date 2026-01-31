<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'logo_url',
    ];

    /**
     * Matches where this team is the opponent (Eisbären are always home/away)
     */
    public function matches(): HasMany
    {
        return $this->hasMany(Game::class, 'opponent_id');
    }

    /**
     * Season winner predictions for this team
     */
    public function seasonWinnerBets(): HasMany
    {
        return $this->hasMany(SeasonWinnerBet::class);
    }

    /**
     * Seasons where this team won
     */
    public function wonSeasons(): HasMany
    {
        return $this->hasMany(Season::class, 'winner_team_id');
    }

    /**
     * Get name without whitespace (for crawler)
     */
    public function getNameWithoutWhitespace(): string
    {
        return str_replace(' ', '', $this->name);
    }
}
