<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBetRequest;
use App\Http\Resources\BetResource;
use App\Models\Bet;
use App\Models\Game;
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
    public function store(StoreBetRequest $request)
    {
        $game = Game::findOrFail($request->game_id);

        // Check if betting is still allowed
        if (!$game->canBet()) {
            return response()->json([
                'message' => 'Betting is closed for this game'
            ], 422);
        }

        // Check if user already bet on this game
        $existingBet = Bet::where('user_id', $request->user()->id)
            ->where('game_id', $game->id)
            ->first();

        if ($existingBet) {
            return response()->json([
                'message' => 'You already placed a bet on this game'
            ], 422);
        }

        // Check if user has enough jokers
        if ($request->joker_type && $request->user()->jokers_remaining <= 0) {
            return response()->json([
                'message' => 'No jokers remaining'
            ], 422);
        }

        // Create bet
        $bet = Bet::create([
            'user_id' => $request->user()->id,
            'game_id' => $game->id,
            'eisbaeren_goals' => $request->eisbaeren_goals,
            'opponent_goals' => $request->opponent_goals,
            'joker_type' => $request->joker_type,
        ]);

        // Use joker if specified
        if ($request->joker_type) {
            $request->user()->useJoker($request->joker_type, $bet);
        }

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
