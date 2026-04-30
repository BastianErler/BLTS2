<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\SeasonUserSetting;
use App\Models\User;
use Illuminate\Http\Request;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        $seasonId = $request->integer('season_id');

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_admin']);

        $settings = SeasonUserSetting::query()
            ->when($seasonId, fn ($q) => $q->where('season_id', $seasonId))
            ->get()
            ->keyBy(fn ($row) => $row->season_id . ':' . $row->user_id);

        return response()->json([
            'data' => $users->map(function (User $user) use ($settings, $seasonId) {
                $setting = $seasonId ? $settings->get($seasonId . ':' . $user->id) : null;

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

        return response()->json([
            'message' => 'Season user setting updated.',
            'data' => $setting,
        ]);
    }
}
