<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\SeasonParticipation;
use App\Models\User;
use App\Services\PushSender;
use App\Support\LeaderboardCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SeasonParticipationController extends Controller
{
    public function show(Request $request)
    {
        $season = Season::current();
        if (! $season) {
            return response()->json(['season' => null, 'participation' => null]);
        }

        $participation = SeasonParticipation::where('season_id', $season->id)->where('user_id', $request->user()->id)->first();
        $finished = $season->games()->where('status', 'finished')->count();

        return response()->json(['season' => $season->only('id', 'name', 'start_date'), 'participation' => $participation, 'finished_games' => $finished]);
    }

    public function join(Request $request)
    {
        $season = Season::current();
        abort_unless($season, 404, 'No active season');
        $existing = SeasonParticipation::where('season_id', $season->id)->where('user_id', $request->user()->id)->first();
        if ($existing?->withdrawn_at) {
            throw ValidationException::withMessages(['season' => 'Please contact an administrator to rejoin.']);
        }
        if ($season->games()->where('status', 'finished')->exists()) {
            $request->validate(['accept_previous_games' => ['accepted']]);
        }
        $participation = SeasonParticipation::firstOrCreate(
            ['season_id' => $season->id, 'user_id' => $request->user()->id],
            ['joined_at' => now()]
        );
        LeaderboardCache::forgetSeason($season->id);

        return response()->json($participation);
    }

    public function requestWithdrawal(Request $request)
    {
        $season = Season::current();
        abort_unless($season, 404, 'No active season');
        $participation = SeasonParticipation::where('season_id', $season->id)->where('user_id', $request->user()->id)->whereNull('withdrawn_at')->firstOrFail();
        $created = SeasonParticipation::whereKey($participation->id)
            ->whereNull('withdrawn_at')->whereNull('withdrawal_requested_at')
            ->update(['withdrawal_requested_at' => now()]);
        if ($created) {
            foreach (User::where('is_admin', true)->get() as $admin) {
                try {
                    app(PushSender::class)->sendToUser($admin, [
                        'title' => 'Abmeldeanfrage',
                        'body' => $request->user()->name.' möchte sich von '.$season->name.' abmelden.',
                        'url' => '/admin/users/seasons',
                        'tag' => 'season-withdrawal-'.$participation->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Could not deliver withdrawal push', ['participation_id' => $participation->id, 'error' => $e->getMessage()]);
                }
            }
        }

        return response()->json($participation->fresh());
    }
}
