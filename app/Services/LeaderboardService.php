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

    public function buildRanking(Season $season, ?Carbon $cutoff = null): array
    {
        // Jeder abgeschlossene Spieltag zählt – auch wenn ein Spieler keinen Tipp abgegeben hat.
        $finishedGameCount = Game::query()
            ->where('season_id', $season->id)
            ->where('status', 'finished')
            ->when(
                $cutoff,
                fn($query) =>
                $query->where('kickoff_at', '<=', $cutoff)
            )
            ->count();

        if ($finishedGameCount === 0) {
            return [];
        }

        $completedBets = DB::table('bets')
            ->join('games', 'games.id', '=', 'bets.game_id')
            ->where('games.season_id', $season->id)
            ->where('games.status', 'finished')
            ->when($cutoff, fn ($q) => $q->where('games.kickoff_at', '<=', $cutoff))
            ->groupBy('bets.user_id')
            ->selectRaw('bets.user_id, SUM(bets.final_price) as submitted_cost, COUNT(*) as bet_count, SUM(CASE WHEN bets.base_price = 0 THEN 1 ELSE 0 END) as exact_bets');

        $rows = DB::table('users')
            ->join('season_participations as participation', function ($join) use ($season) {
                $join->on('participation.user_id', '=', 'users.id')
                    ->where('participation.season_id', '=', $season->id)
                    ->whereNull('participation.withdrawn_at');
            })
            ->leftJoinSub($completedBets, 'completed', 'completed.user_id', '=', 'users.id')
            ->leftJoin('season_user_settings as sus', function ($join) use ($season) {
                $join->on('sus.user_id', '=', 'users.id')
                    ->where('sus.season_id', '=', $season->id);
            })
            ->where(function ($query) {
                $query->whereNull('sus.exclude_from_leaderboard')
                    ->orWhere('sus.exclude_from_leaderboard', false);
            })
            ->selectRaw('
                users.id,
                users.name,
                users.jokers_remaining,
                COALESCE(completed.submitted_cost, 0) as submitted_cost,
                COALESCE(completed.bet_count, 0) as bet_count,
                COALESCE(completed.exact_bets, 0) as exact_bets
            ')
            ->get();

        return $rows
            ->map(function ($row) use ($finishedGameCount) {
                $betCount = (int) $row->bet_count;
                $submittedCost = (float) $row->submitted_cost;
                $missingTips = max(0, $finishedGameCount - $betCount);
                $totalCost = round($submittedCost + $missingTips, 2);

                return [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'total_cost' => $totalCost,
                    'bet_count' => $betCount,
                    'missing_bets' => $missingTips,
                    'exact_bets' => (int) $row->exact_bets,
                    'average_cost' => $finishedGameCount > 0
                        ? round($totalCost / $finishedGameCount, 2)
                        : 0.0,
                    'jokers_remaining' => (int) $row->jokers_remaining,
                ];
            })
            ->sort(function ($a, $b) {
                return [$a['total_cost'], -$a['exact_bets'], $a['id']]
                    <=> [$b['total_cost'], -$b['exact_bets'], $b['id']];
            })
            ->values()
            ->map(function (array $row, int $index) {
                $row['rank'] = $index + 1;
                return $row;
            })
            ->toArray();
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
