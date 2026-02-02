<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
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

        $seasonId = $request->integer('season_id');

        $season = $seasonId
            ? Season::query()->find($seasonId)
            : Season::current();

        if (!$season) {
            return response()->json(['message' => 'No active season'], 404);
        }

        $now = now();

        // ===== Delta Basis: letztes finished Spiel vs davor =====
        $lastTwoFinished = Game::query()
            ->where('season_id', $season->id)
            ->where('status', 'finished')
            ->orderByDesc('kickoff_at')
            ->limit(2)
            ->get();

        $latestFinished = $lastTwoFinished->first();
        $previousFinished = $lastTwoFinished->skip(1)->first();

        $latestCutoff = $latestFinished?->kickoff_at;     // "nach dem letzten Spiel"
        $previousCutoff = $previousFinished?->kickoff_at; // "nach dem Spiel davor"

        // aktuelles Ranking (bis letztes finished Spiel; falls keins vorhanden: ohne cutoff)
        $current = $this->buildRanking($season, $latestCutoff);

        // vorheriges Ranking (bis vorletztes finished Spiel; falls nicht vorhanden: delta = 0)
        $previous = $previousCutoff
            ? $this->buildRanking($season, $previousCutoff)
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
        });

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
            'entries' => $entries->values(),
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

    /**
     * Build ranking for a season (optionally up to a cutoff kickoff_at)
     */
    private function buildRanking(Season $season, $cutoff = null): array
    {
        $users = User::with(['bets' => function ($query) use ($season, $cutoff) {
            $query->whereHas('game', function ($q) use ($season, $cutoff) {
                $q->where('season_id', $season->id)
                    ->where('status', 'finished');

                if ($cutoff) {
                    $q->where('kickoff_at', '<=', $cutoff);
                }
            });
        }])
            ->where('is_admin', false)
            ->get()
            ->map(function ($user) {
                $totalCost = round($user->bets->sum('final_price'), 2);
                $betCount = $user->bets->count();
                $exactBets = $user->bets->filter(fn($b) => $b->base_price === 0.00)->count();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'total_cost' => $totalCost,
                    'bet_count' => $betCount,
                    'exact_bets' => $exactBets,
                    'average_cost' => $betCount > 0 ? round($totalCost / $betCount, 2) : 0,
                    'jokers_remaining' => $user->jokers_remaining,
                ];
            })
            ->sortBy('total_cost')
            ->values()
            ->map(function ($u, $i) {
                return [
                    ...$u,
                    'rank' => $i + 1,
                ];
            });

        return $users->toArray();
    }
}
