<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_number',
        'opponent_id',
        'season_id',
        'is_home',
        'kickoff_at',
        'eisbaeren_goals',
        'opponent_goals',
        'status',
        'is_derby',
        'is_playoff',
        'difficulty_rating',
        'email_reminder_sent',
        'sms_reminder_sent',
    ];

    protected $casts = [
        'kickoff_at' => 'datetime',
        'eisbaeren_goals' => 'integer',
        'opponent_goals' => 'integer',

        // NOTE: Falls diese Spalten nicht in `games` existieren, kannst du sie rausnehmen.
        // Laravel crasht dadurch nicht, aber es ist verwirrend.
        'joker_data' => 'array',
        'base_price' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'final_price' => 'decimal:2',
        'locked_at' => 'datetime',
    ];

    // Optional: wenn du bet_deadline_at im JSON/TS verfügbar haben willst
    protected $appends = [
        'bet_deadline_at',
    ];

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'opponent_id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    /**
     * Check if match is finished
     */
    public function isFinished(): bool
    {
        return $this->status === 'finished'
            && $this->eisbaeren_goals !== null
            && $this->opponent_goals !== null;
    }

    /**
     * Check if match is in the past
     */
    public function isPast(): bool
    {
        return $this->kickoff_at?->isPast() ?? false;
    }

    /**
     * Bet deadline is 6 hours before kickoff.
     */
    public function betDeadline(): ?Carbon
    {
        if (!$this->kickoff_at) return null;
        return $this->kickoff_at->copy()->subHours(6);
    }

    /**
     * Accessor: bet_deadline_at for JSON / TS.
     */
    public function getBetDeadlineAtAttribute(): ?string
    {
        $d = $this->betDeadline();
        return $d ? $d->toIso8601String() : null;
    }

    /**
     * Check if bets can still be placed (until kickoff - 6h).
     */
    public function canBet(): bool
    {
        $deadline = $this->betDeadline();

        return $this->status === 'scheduled'
            && $deadline !== null
            && now()->lt($deadline);
    }

    /**
     * Get winner (eisbaeren or opponent or draw)
     */
    public function getWinnerAttribute(): ?string
    {
        if (!$this->isFinished()) {
            return null;
        }

        if ($this->eisbaeren_goals > $this->opponent_goals) {
            return 'eisbaeren';
        }

        if ($this->opponent_goals > $this->eisbaeren_goals) {
            return 'opponent';
        }

        return 'draw';
    }

    /**
     * Get goal difference
     */
    public function getGoalDifferenceAttribute(): ?int
    {
        if (!$this->isFinished()) {
            return null;
        }

        return abs($this->eisbaeren_goals - $this->opponent_goals);
    }

    /**
     * Get Derby multiplier
     */
    public function getDerbyMultiplier(): float
    {
        return $this->is_derby ? 1.5 : 1.0;
    }

    /**
     * Scope for upcoming matches
     */
    public function scopeUpcoming($query)
    {
        return $query->where('kickoff_at', '>', now())
            ->where('status', 'scheduled')
            ->orderBy('kickoff_at');
    }

    /**
     * Scope for past matches
     */
    public function scopePast($query)
    {
        return $query->where('kickoff_at', '<', now())
            ->orderBy('kickoff_at', 'desc');
    }

    /**
     * Scope for specific season
     */
    public function scopeSeason($query, $seasonId)
    {
        return $query->where('season_id', $seasonId);
    }

    public function finishGame(int $eisbaerenGoals, int $opponentGoals): void
    {
        $this->update([
            'eisbaeren_goals' => $eisbaerenGoals,
            'opponent_goals' => $opponentGoals,
            'status' => 'finished',
        ]);

        // Calculate all bet prices
        foreach ($this->bets as $bet) {
            $bet->updatePrices();
            $bet->locked_at = now();
            $bet->save();
        }
    }
}
