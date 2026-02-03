<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Season;
use App\Models\User;
use App\Support\LeaderboardCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    /**
     * Get leaderboard for current season
     */
    public function index(Request $request)
    {
        $seasonId = $request->integer('season_id');

        $season = $seasonId
            ? Season::query()->find($seasonId)
            : Season::current();

        if (!$season) {
            return response()->json(['message' => 'No active season'], 404);
        }

        $now = now();

        // ===== Delta-Basis: letztes finished Spiel vs davor =====
        $lastTwoFinished = Game::query()
            ->where('season_id', $season->id)
            ->where('status', 'finished')
            ->orderByDesc('kickoff_at')
            ->limit(2)
            ->get();

        $latestFinished = $lastTwoFinished->first();
        $previousFinished = $lastTwoFinished->skip(1)->first();

        $latestCutoff = $latestFinished?->kickoff_at;
        $previousCutoff = $previousFinished?->kickoff_at;

        $ttlSeconds = 60;

        // ===== aktuelles Ranking =====
        $current = LeaderboardCache::rememberRanking(
            $season->id,
            $latestCutoff?->timestamp,
            $ttlSeconds,
            fn() => $this->buildRanking($season, $latestCutoff)
        );

        // ===== vorheriges Ranking =====
        $previous = $previousCutoff
            ? LeaderboardCache::rememberRanking(
                $season->id,
                $previousCutoff->timestamp,
                $ttlSeconds,
                fn() => $this->buildRanking($season, $previousCutoff)
            )
            : $current;

        $prevRanks = collect($previous)
            ->mapWithKeys(fn($u) => [$u['id'] => $u['rank']])
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
                'latest_finished_game_id' => $latestFinished?->id,
                'previous_finished_game_id' => $previousFinished?->id,
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
    public function userStats(Request $request, User $user = null)
    {
        $user = $user ?? $request->user();
        $season = Season::current();

        if (!$season) {
            return response()->json(['message' => 'No active season'], 404);
        }

        $ttlSeconds = 60;
        $cacheKey = "leaderboard:v1:userstats:season:{$season->id}:user:{$user->id}";

        $stats = cache()->remember($cacheKey, $ttlSeconds, function () use ($user, $season) {
            $bets = $user->bets()
                ->whereHas('game', function ($q) use ($season) {
                    $q->where('season_id', $season->id)
                        ->where('status', 'finished');
                })
                ->get();

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
                'position' => $user->getLeaderboardPosition($season),
            ];
        });

        return response()->json($stats);
    }

    /**
     * Build ranking for a season (SQL aggregation)
     */
    private function buildRanking(Season $season, $cutoff = null): array
    {
        $rows = DB::table('users')
            ->leftJoin('bets', 'bets.user_id', '=', 'users.id')
            ->leftJoin('games', function ($join) use ($season, $cutoff) {
                $join->on('games.id', '=', 'bets.game_id')
                    ->where('games.season_id', '=', $season->id)
                    ->where('games.status', '=', 'finished');

                if ($cutoff) {
                    $join->where('games.kickoff_at', '<=', $cutoff);
                }
            })
            ->where('users.is_admin', false)
            ->groupBy('users.id', 'users.name', 'users.jokers_remaining')
            ->selectRaw('
                users.id,
                users.name,
                users.jokers_remaining,
                COALESCE(ROUND(SUM(bets.final_price), 2), 0) as total_cost,
                COUNT(bets.id) as bet_count,
                SUM(CASE WHEN bets.base_price = 0.00 THEN 1 ELSE 0 END) as exact_bets
            ')
            ->orderBy('total_cost')
            ->orderByDesc('exact_bets')
            ->orderBy('users.id')
            ->get();

        $rank = 1;

        return $rows->map(function ($r) use (&$rank) {
            $betCount = (int) $r->bet_count;
            $totalCost = (float) $r->total_cost;

            return [
                'id' => (int) $r->id,
                'name' => (string) $r->name,
                'total_cost' => $totalCost,
                'bet_count' => $betCount,
                'exact_bets' => (int) $r->exact_bets,
                'average_cost' => $betCount > 0 ? round($totalCost / $betCount, 2) : 0.0,
                'jokers_remaining' => (int) $r->jokers_remaining,
                'rank' => $rank++,
            ];
        })->toArray();
    }
}
