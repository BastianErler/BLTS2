<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GameResource;
use App\Models\Game;
use App\Models\Season;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Get all games for current season
     */
    public function index(Request $request)
    {
        $season = Season::current();
        
        if (!$season) {
            return response()->json(['message' => 'No active season'], 404);
        }

        $query = Game::with(['opponent', 'season'])
            ->where('season_id', $season->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter upcoming
        if ($request->boolean('upcoming')) {
            $query->upcoming();
        }

        // Filter past
        if ($request->boolean('past')) {
            $query->past();
        }

        $games = $query->orderBy('kickoff_at')->get();

        return GameResource::collection($games);
    }

    /**
     * Get upcoming games (next 5)
     */
    public function upcoming()
    {
        $games = Game::with(['opponent', 'season'])
            ->upcoming()
            ->limit(5)
            ->get();

        return GameResource::collection($games);
    }

    /**
     * Get a specific game
     */
    public function show(Game $game)
    {
        $game->load(['opponent', 'season', 'bets.user']);
        
        return new GameResource($game);
    }

    /**
     * Get user's bet for a specific game
     */
    public function userBet(Game $game, Request $request)
    {
        $bet = $game->bets()
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$bet) {
            return response()->json(['message' => 'No bet placed'], 404);
        }

        return response()->json([
            'bet' => $bet,
            'can_edit' => $game->canBet() && !$bet->locked_at,
        ]);
    }
}
