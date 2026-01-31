<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class Season extends Model
{
    protected $fillable = [
        'name',
        'winner_team_id',
        'start_date',
        'end_date',
        'is_active',
        'phase_1_multiplier',
        'phase_2_multiplier',
        'phase_3_multiplier',
        'playoff_multiplier',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'phase_1_multiplier' => 'decimal:1',
        'phase_2_multiplier' => 'decimal:1',
        'phase_3_multiplier' => 'decimal:1',
        'playoff_multiplier' => 'decimal:1',
    ];

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function seasonWinnerBets(): HasMany
    {
        return $this->hasMany(SeasonWinnerBet::class);
    }

    public function winnerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    /**
     * Get the current active season
     */
    public static function current(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Determine which phase multiplier to use based on match count
     */
    public function getMultiplierForGame(Game $game): float
    {
        if ($game->is_playoff) {
            return (float) $this->playoff_multiplier;
        }

        $totalGames = $this->games()->count();
        $gamePosition = $this->games()
            ->where('kickoff_at', '<=', $game->kickoff_at)
            ->count();

        $percentage = ($gamePosition / $totalGames) * 100;

        if ($percentage <= 40) {
            return (float) $this->phase_1_multiplier;
        } elseif ($percentage <= 75) {
            return (float) $this->phase_2_multiplier;
        } else {
            return (float) $this->phase_3_multiplier;
        }
    }
}
