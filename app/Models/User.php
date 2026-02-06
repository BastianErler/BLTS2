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
        'notification_settings',
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
        'notification_settings' => 'array',
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
        return (float) $this->bets()
            ->whereHas('game', fn($q) => $q->where('season_id', $season->id))
            ->sum('final_price');
    }

    /**
     * Get leaderboard position for a season
     *
     * NOTE: This is expensive (loads all users). Prefer LeaderboardService->rankForUser().
     */
    public function getLeaderboardPosition(Season $season): int
    {
        $allUsers = User::withSum(['bets as total_cost' => function ($query) use ($season) {
            $query->whereHas('game', fn($q) => $q->where('season_id', $season->id));
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
    public function addTransaction(
        string $type,
        float $amount,
        ?string $description = null,
        ?Bet $bet = null
    ): Transaction {
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

    /**
     * Notification settings schema (defaults)
     */
    public static function defaultNotificationSettings(): array
    {
        return [
            'v' => 1,

            'push_enabled' => false,

            'remind_before_deadline' => true,
            'remind_before_deadline_minutes' => 120, // allowed: 30/60/120

            'remind_on_game_start_if_no_bet' => true,

            'notify_on_bet_result' => true,

            'notify_on_rank_change' => false,
            'rank_change_threshold' => 3, // 1..50

            // snapshot for rank-change comparisons
            'rank_last_position' => null,
        ];
    }

    /**
     * Merges stored JSON with defaults + normalizes.
     */
    public function mergedNotificationSettings(): array
    {
        $stored = $this->notification_settings;

        if (!is_array($stored)) {
            $stored = [];
        }

        // stored überschreibt defaults
        $merged = array_replace(self::defaultNotificationSettings(), $stored);

        // keep unknown keys user already has (future proof)
        foreach ($stored as $k => $v) {
            if (!array_key_exists($k, $merged)) {
                $merged[$k] = $v;
            }
        }

        // normalize
        $merged['v'] = (int) ($merged['v'] ?? 1);

        $merged['push_enabled'] = (bool) ($merged['push_enabled'] ?? false);
        $merged['remind_before_deadline'] = (bool) ($merged['remind_before_deadline'] ?? true);
        $merged['remind_on_game_start_if_no_bet'] = (bool) ($merged['remind_on_game_start_if_no_bet'] ?? true);
        $merged['notify_on_bet_result'] = (bool) ($merged['notify_on_bet_result'] ?? true);
        $merged['notify_on_rank_change'] = (bool) ($merged['notify_on_rank_change'] ?? false);

        $mins = (int) ($merged['remind_before_deadline_minutes'] ?? 120);
        if (!in_array($mins, [30, 60, 120], true)) {
            $mins = 120;
        }
        $merged['remind_before_deadline_minutes'] = $mins;

        $thr = (int) ($merged['rank_change_threshold'] ?? 3);
        if ($thr < 1) $thr = 1;
        if ($thr > 50) $thr = 50;
        $merged['rank_change_threshold'] = $thr;

        $merged['rank_last_position'] = $merged['rank_last_position'] !== null
            ? (int) $merged['rank_last_position']
            : null;

        return $merged;
    }
}
