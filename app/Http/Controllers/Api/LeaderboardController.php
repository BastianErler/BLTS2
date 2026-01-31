<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\User;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    /**
     * Get leaderboard for current season
     */
    public function index(Request $request)
    {
        $season = Season::current();
        
        if (!$season) {
            return response()->json(['message' => 'No active season'], 404);
        }

        $users = User::with(['bets' => function ($query) use ($season) {
                $query->whereHas('game', function ($q) use ($season) {
                    $q->where('season_id', $season->id)
                      ->where('status', 'finished');
                });
            }])
            ->where('is_admin', false)
            ->get()
            ->map(function ($user) use ($season) {
                $totalCost = $user->bets->sum('final_price');
                $betCount = $user->bets->count();
                $exactBets = $user->bets->filter(fn($bet) => $bet->base_price === 0.00)->count();
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'total_cost' => round($totalCost, 2),
                    'bet_count' => $betCount,
                    'exact_bets' => $exactBets,
                    'average_cost' => $betCount > 0 ? round($totalCost / $betCount, 2) : 0,
                    'jokers_remaining' => $user->jokers_remaining,
                ];
            })
            ->sortBy('total_cost')
            ->values()
            ->map(function ($user, $index) {
                $user['position'] = $index + 1;
                return $user;
            });

        return response()->json([
            'season' => [
                'id' => $season->id,
                'name' => $season->name,
            ],
            'leaderboard' => $users,
            'my_position' => $users->firstWhere('id', $request->user()->id)['position'] ?? null,
        ]);
    }

    /**
     * Get detailed stats for a user
     */
    public function userStats(Request $request, User $user = null)
    {
        $user = $user ?? $request->user();
        $season = Season::current();

        if (!$season) {
            return response()->json(['message' => 'No active season'], 404);
        }

        $bets = $user->bets()
            ->whereHas('game', function ($q) use ($season) {
                $q->where('season_id', $season->id)
                  ->where('status', 'finished');
            })
            ->with('game')
            ->get();

        $stats = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'balance' => $user->balance,
                'jokers_remaining' => $user->jokers_remaining,
            ],
            'season' => [
                'id' => $season->id,
                'name' => $season->name,
            ],
            'total_cost' => round($bets->sum('final_price'), 2),
            'bet_count' => $bets->count(),
            'exact_bets' => $bets->filter(fn($bet) => $bet->base_price === 0.00)->count(),
            'tendency_bets' => $bets->filter(fn($bet) => $bet->base_price === 0.30)->count(),
            'winner_bets' => $bets->filter(fn($bet) => $bet->base_price === 0.60)->count(),
            'wrong_bets' => $bets->filter(fn($bet) => $bet->base_price === 1.00)->count(),
            'jokers_used' => $user->jokers_used ?? [],
            'position' => $user->getLeaderboardPosition($season),
        ];

        return response()->json($stats);
    }
}
