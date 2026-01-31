<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class Bet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_id',
        'eisbaeren_goals',
        'opponent_goals',
        'joker_type',
        'joker_data',
        'base_price',
        'multiplier',
        'final_price',
    ];

    protected $casts = [
        'eisbaeren_goals' => 'integer',
        'opponent_goals' => 'integer',
        'joker_data' => 'array',
        'base_price' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'final_price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Calculate price based on BLTS1 logic
     */
    public function calculatePrice(): float
    {
        if (!$this->game->isFinished()) {
            return 0.00;
        }

        // Invalid bet (draw)
        if ($this->tipInvalid()) {
            return 1.00;
        }

        // Exact prediction
        if ($this->tipCorrect()) {
            return 0.00;
        }

        // Correct tendency (goal difference + winner)
        if ($this->tendency()) {
            return 0.30;
        }

        // Correct winner only
        if ($this->correctWinner()) {
            return 0.60;
        }

        // Completely wrong
        return 1.00;
    }

    /**
     * Calculate final price with multipliers
     */
    public function calculateFinalPrice(): float
    {
        $basePrice = $this->calculatePrice();

        // Get season phase multiplier
        $seasonMultiplier = $this->game->season->getMultiplierForGame($this->game);

        // Get derby multiplier
        $derbyMultiplier = $this->game->getDerbyMultiplier();

        // Get joker multiplier
        $jokerMultiplier = $this->getJokerMultiplier();

        // Total multiplier
        $totalMultiplier = $seasonMultiplier * $derbyMultiplier * $jokerMultiplier;

        $finalPrice = $basePrice * $totalMultiplier;

        // Apply joker bonus/penalty
        $finalPrice = $this->applyJokerBonus($finalPrice, $basePrice);

        return round($finalPrice, 2);
    }

    /**
     * Check if tip is exact
     */
    private function tipCorrect(): bool
    {
        return $this->eisbaeren_goals === $this->game->eisbaeren_goals &&
            $this->opponent_goals === $this->game->opponent_goals;
    }

    /**
     * Check if tendency is correct (goal diff + winner)
     */
    private function tendency(): bool
    {
        return $this->goalDifference === $this->game->goalDifference &&
            $this->correctWinner();
    }

    /**
     * Check if winner is correct
     */
    private function correctWinner(): bool
    {
        return $this->winner === $this->game->winner;
    }

    /**
     * Check if bet is invalid (draw)
     */
    private function tipInvalid(): bool
    {
        return $this->eisbaeren_goals === $this->opponent_goals;
    }

    /**
     * Get winner from bet
     */
    public function getWinnerAttribute(): string
    {
        if ($this->eisbaeren_goals > $this->opponent_goals) {
            return 'eisbaeren';
        }

        if ($this->opponent_goals > $this->eisbaeren_goals) {
            return 'opponent';
        }

        return 'draw';
    }

    /**
     * Get goal difference from bet
     */
    public function getGoalDifferenceAttribute(): int
    {
        return abs($this->eisbaeren_goals - $this->opponent_goals);
    }

    /**
     * Get joker multiplier based on type
     */
    private function getJokerMultiplier(): float
    {
        return match ($this->joker_type) {
            'double_down' => 2.0,
            'safety' => 0.5,
            'bankier' => 1.0, // Will be handled separately
            default => 1.0,
        };
    }

    /**
     * Apply joker-specific bonuses
     */
    private function applyJokerBonus(float $currentPrice, float $basePrice): float
    {
        if ($this->joker_type === 'double_down' && $this->tipCorrect()) {
            // Exact tip with double down = get 1€ back
            return -1.00;
        }

        if ($this->joker_type === 'underdog' && $this->winner === 'opponent') {
            // Correctly predicted Eisbären loss = bonus
            return $currentPrice - 0.50;
        }

        if ($this->joker_type === 'bankier') {
            // All or nothing: 0€ or 1€
            return $basePrice === 0.00 ? 0.00 : 1.00;
        }

        return $currentPrice;
    }

    /**
     * Update calculated prices
     */
    public function updatePrices(): void
    {
        $this->base_price = $this->calculatePrice();
        $this->final_price = $this->calculateFinalPrice();
        $this->save();
    }
}
