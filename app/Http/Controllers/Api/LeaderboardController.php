<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    /**
     * Get leaderboard for current season
     */
    public function index(Request $request, LeaderboardService $lb)
    {
        $seasonId = $request->integer('season_id');

        try {
            $season = $lb->currentSeasonOrFail($seasonId);
        } catch (\RuntimeException) {
            return response()->json(['message' => 'No active season'], 404);
        }

        $now = now();
        $ttlSeconds = 60;

        $basis = $lb->deltaBasis($season);

        $latestCutoff = $basis['latest_cutoff'];
        $previousCutoff = $basis['previous_cutoff'];

        $current = $lb->rankingForCutoff($season, $latestCutoff, $ttlSeconds);
        $previous = $previousCutoff
            ? $lb->rankingForCutoff($season, $previousCutoff, $ttlSeconds)
            : $current;

        $prevRanks = collect($previous)
            ->mapWithKeys(fn($u) => [(int) $u['id'] => (int) $u['rank']])
            ->all();

        $authId = $request->user()->id;

        $entries = collect($current)->map(function ($u) use ($prevRanks, $authId) {
            $prevRank = $prevRanks[$u['id']] ?? $u['rank'];

            return [
                ...$u,
                'delta' => $prevRank - $u['rank'], // + = hoch, - = runter
                'is_me' => $u['id'] === $authId,
            ];
        })->values();

        return response()->json([
            'season' => [
                'id' => $season->id,
                'name' => $season->name,
            ],
            'delta_basis' => [
                'latest_finished_game_id' => $basis['latest_finished_game_id'],
                'previous_finished_game_id' => $basis['previous_finished_game_id'],
                'latest_cutoff' => $latestCutoff,
                'previous_cutoff' => $previousCutoff,
            ],
            'me' => $entries->firstWhere('is_me', true),
            'top3' => $entries->take(3)->values(),
            'entries' => $entries,
            'generated_at' => $now->toIso8601String(),
        ]);
    }

    /**
     * Get detailed stats for a user
     */
    public function userStats(Request $request, User $user = null, LeaderboardService $lb)
    {
        $user = $user ?? $request->user();

        $seasonId = $request->integer('season_id');
        $season = $seasonId
            ? Season::query()->find($seasonId)
            : Season::current();

        if (!$season) {
            return response()->json(['message' => 'No active season'], 404);
        }

        $ttlSeconds = 60;
        $cacheKey = "leaderboard:v1:userstats:season:{$season->id}:user:{$user->id}";

        $stats = cache()->remember($cacheKey, $ttlSeconds, function () use ($user, $season, $lb) {
            $bets = $user->bets()
                ->whereHas('game', function ($q) use ($season) {
                    $q->where('season_id', $season->id)
                        ->where('status', 'finished');
                })
                ->get();

            // ✅ Rank aus der gleichen Ranking-Quelle (Service), damit konsistent
            $position = $lb->rankForUser($user, $season);

            return [
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
                'exact_bets' => $bets->where('base_price', 0.00)->count(),
                'tendency_bets' => $bets->where('base_price', 0.30)->count(),
                'winner_bets' => $bets->where('base_price', 0.60)->count(),
                'wrong_bets' => $bets->where('base_price', 1.00)->count(),
                'jokers_used' => $user->jokers_used ?? [],
                'position' => $position, // previously: $user->getLeaderboardPosition($season)
            ];
        });

        return response()->json($stats);
    }
}
