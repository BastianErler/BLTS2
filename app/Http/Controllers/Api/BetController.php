<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBetRequest;
use App\Http\Resources\BetResource;
use App\Models\Bet;
use App\Models\Game;
use App\Services\PlaceBet;
use Illuminate\Http\Request;

class BetController extends Controller
{
    /**
     * Get all bets for authenticated user
     */
    public function index(Request $request)
    {
        $bets = Bet::with(['game.opponent', 'game.season'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return BetResource::collection($bets);
    }

    /**
     * Place a bet
     */
    public function store(StoreBetRequest $request, PlaceBet $placeBet)
    {
        $bet = $placeBet($request->user(), $request->validated());

        return new BetResource($bet->load('game.opponent'));
    }

    /**
     * Update a bet (only before game starts)
     */
    public function update(StoreBetRequest $request, Bet $bet)
    {
        // Check authorization
        if ($bet->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if bet is locked
        if ($bet->locked_at) {
            return response()->json([
                'message' => 'Bet is locked and cannot be changed'
            ], 422);
        }

        // Check if game betting is still open
        if (!$bet->game->canBet()) {
            return response()->json([
                'message' => 'Betting is closed for this game'
            ], 422);
        }

        $bet->update([
            'eisbaeren_goals' => $request->eisbaeren_goals,
            'opponent_goals' => $request->opponent_goals,
        ]);

        return new BetResource($bet->load('game.opponent'));
    }

    /**
     * Delete a bet (only before game starts)
     */
    public function destroy(Bet $bet, Request $request)
    {
        // Check authorization
        if ($bet->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if bet is locked
        if ($bet->locked_at) {
            return response()->json([
                'message' => 'Bet is locked and cannot be deleted'
            ], 422);
        }

        // Check if game betting is still open
        if (!$bet->game->canBet()) {
            return response()->json([
                'message' => 'Betting is closed for this game'
            ], 422);
        }

        $bet->delete();

        return response()->json(['message' => 'Bet deleted successfully']);
    }

    /**
     * Get bets for a specific game
     */
    public function forGame(Game $game, Request $request)
    {
        // Only show other users' bets after game started
        if ($game->canBet()) {
            return response()->json([
                'message' => 'Bets will be visible after game starts'
            ], 422);
        }

        $bets = $game->bets()
            ->with('user')
            ->get();

        return BetResource::collection($bets);
    }
}
