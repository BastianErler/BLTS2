<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',
        'wants_email_reminder',
        'wants_sms_reminder',
        'is_admin',
        'balance',
        'jokers_remaining',
        'jokers_used',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'wants_email_reminder' => 'boolean',
        'wants_sms_reminder' => 'boolean',
        'is_admin' => 'boolean',
        'balance' => 'decimal:2',
        'jokers_used' => 'array',
    ];

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function seasonWinnerBets(): HasMany
    {
        return $this->hasMany(SeasonWinnerBet::class);
    }

    /**
     * Get total cost for a season
     */
    public function getTotalCostForSeason(Season $season): float
    {
        return $this->bets()
            ->whereHas('game', fn($q) => fn($q) => $q->where('season_id', $season->id))
            ->sum('final_price');
    }

    /**
     * Get leaderboard position for a season
     */
    public function getLeaderboardPosition(Season $season): int
    {
        $allUsers = User::withSum(['bets as total_cost' => function ($query) use ($season) {
            $query->whereHas('game', fn($q) => fn($q) => $q->where('season_id', $season->id));
        }], 'final_price')
            ->orderBy('total_cost')
            ->get();

        return $allUsers->search(fn($user) => $user->id === $this->id) + 1;
    }

    /**
     * Use a joker
     */
    public function useJoker(string $jokerType, Bet $bet): bool
    {
        if ($this->jokers_remaining <= 0) {
            return false;
        }

        $jokersUsed = $this->jokers_used ?? [];
        $jokersUsed[] = [
            'type' => $jokerType,
            'bet_id' => $bet->id,
            'used_at' => now()->toIso8601String(),
        ];

        $this->jokers_used = $jokersUsed;
        $this->jokers_remaining--;
        $this->save();

        return true;
    }

    /**
     * Reset jokers for new season
     */
    public function resetJokers(int $amount = 3): void
    {
        $this->jokers_remaining = $amount;
        $this->jokers_used = [];
        $this->save();
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Add transaction
     */
    public function addTransaction(string $type, float $amount, ?string $description = null, ?Bet $bet = null): Transaction
    {
        return Transaction::create([
            'user_id' => $this->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'bet_id' => $bet?->id,
        ]);
    }

    /**
     * Update balance
     */
    public function updateBalance(): void
    {
        $this->balance = $this->transactions()->sum('amount');
        $this->save();
    }
}
