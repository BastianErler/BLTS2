<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\SeasonParticipation;
use App\Models\SeasonUserSetting;
use App\Models\User;
use App\Support\LeaderboardCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserAdminController extends Controller
{
    public function withdrawalRequests()
    {
        return response()->json(['data' => DB::table('season_participations as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->join('seasons as s', 's.id', '=', 'p.season_id')
            ->whereNotNull('p.withdrawal_requested_at')->whereNull('p.withdrawn_at')
            ->select('p.id', 'p.withdrawal_requested_at', 'u.name', 's.name as season_name')
            ->orderBy('p.withdrawal_requested_at')->get()]);
    }

    public function resolveWithdrawal(Request $request, SeasonParticipation $participation)
    {
        $data = $request->validate(['approve' => ['required', 'boolean']]);
        DB::transaction(function () use ($participation, $request, $data) {
            $p = SeasonParticipation::whereKey($participation->id)->lockForUpdate()->firstOrFail();
            abort_if(! $p->withdrawal_requested_at || $p->withdrawn_at, 409, 'No pending request');
            if ($data['approve']) {
                $games = DB::table('games')->where('season_id', $p->season_id)->where('status', 'finished');
                $gameCount = (clone $games)->count();
                $bets = DB::table('bets')->join('games', 'games.id', '=', 'bets.game_id')
                    ->where('bets.user_id', $p->user_id)->where('games.season_id', $p->season_id)
                    ->where('games.status', 'finished');
                $betCount = (clone $bets)->count();
                $cost = (float) (clone $bets)->sum('bets.final_price') + max(0, $gameCount - $betCount);
                $p->withdrawn_at = now();
                $p->withdrawn_by = $request->user()->id;
                $p->frozen_cost = round($cost, 2);
            }
            $p->withdrawal_requested_at = null;
            $p->save();
            LeaderboardCache::forgetSeason($p->season_id);
            cache()->forget("leaderboard:v1:userstats:season:{$p->season_id}:user:{$p->user_id}");
        });

        return response()->json(['ok' => true]);
    }

    public function index(Request $request)
    {
        $seasonId = $request->integer('season_id');

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_admin']);

        $settings = SeasonUserSetting::query()
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->get()
            ->keyBy(fn ($row) => $row->season_id.':'.$row->user_id);

        return response()->json([
            'data' => $users->map(function (User $user) use ($settings, $seasonId) {
                $setting = $seasonId ? $settings->get($seasonId.':'.$user->id) : null;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => (bool) $user->is_admin,
                    'season_setting' => [
                        'exclude_from_leaderboard' => (bool) ($setting->exclude_from_leaderboard ?? false),
                        'fee_exempt' => (bool) ($setting->fee_exempt ?? false),
                    ],
                ];
            }),
        ]);
    }

    public function updateSeasonSetting(Request $request, User $user, Season $season)
    {
        $data = $request->validate([
            'exclude_from_leaderboard' => ['required', 'boolean'],
            'fee_exempt' => ['required', 'boolean'],
        ]);

        $setting = SeasonUserSetting::query()->updateOrCreate(
            ['season_id' => $season->id, 'user_id' => $user->id],
            $data,
        );
        LeaderboardCache::forgetSeason($season->id);

        return response()->json([
            'message' => 'Season user setting updated.',
            'data' => $setting,
        ]);
    }
}
