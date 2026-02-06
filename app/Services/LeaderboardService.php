<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Season;
use App\Models\User;
use App\Support\LeaderboardCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    public function currentSeasonOrFail(?int $seasonId = null): Season
    {
        $season = $seasonId
            ? Season::query()->find($seasonId)
            : Season::current();

        if (!$season) {
            throw new \RuntimeException('No active season');
        }

        return $season;
    }

    public function deltaBasis(Season $season): array
    {
        $lastTwoFinished = Game::query()
            ->where('season_id', $season->id)
            ->where('status', 'finished')
            ->orderByDesc('kickoff_at')
            ->limit(2)
            ->get();

        $latestFinished = $lastTwoFinished->first();
        $previousFinished = $lastTwoFinished->skip(1)->first();

        return [
            'latest_finished_game_id' => $latestFinished?->id,
            'previous_finished_game_id' => $previousFinished?->id,
            'latest_cutoff' => $latestFinished?->kickoff_at,
            'previous_cutoff' => $previousFinished?->kickoff_at,
        ];
    }

    public function rankingForCutoff(Season $season, ?Carbon $cutoff, int $ttlSeconds = 60): array
    {
        return LeaderboardCache::rememberRanking(
            $season->id,
            $cutoff?->timestamp,
            $ttlSeconds,
            fn() => $this->buildRanking($season, $cutoff)
        );
    }

    /**
     * Diese Methode ist exakt dein SQL – nur aus dem Controller rausgezogen.
     */
    public function buildRanking(Season $season, ?Carbon $cutoff = null): array
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

    public function rankForUser(User $user, Season $season, ?Carbon $cutoff = null): ?int
    {
        $ranking = $this->rankingForCutoff($season, $cutoff, 60);

        foreach ($ranking as $row) {
            if ((int) $row['id'] === (int) $user->id) {
                return (int) $row['rank'];
            }
        }

        return null;
    }
}
