<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceBet
{
    public function __invoke(User $user, array $data): Bet
    {
        return DB::transaction(function () use ($user, $data) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $game = Game::query()->lockForUpdate()->findOrFail($data['game_id']);

            if (! $game->canBet()) {
                throw ValidationException::withMessages(['game_id' => 'Betting is closed for this game']);
            }
            if (Bet::where('user_id', $user->id)->where('game_id', $game->id)->exists()) {
                throw ValidationException::withMessages(['game_id' => 'You already placed a bet on this game']);
            }
            $joker = $data['joker_type'] ?? null;
            if ($joker && $user->jokers_remaining <= 0) {
                throw ValidationException::withMessages(['joker_type' => 'No jokers remaining']);
            }

            $bet = Bet::create([
                'user_id' => $user->id,
                'game_id' => $game->id,
                'eisbaeren_goals' => $data['eisbaeren_goals'],
                'opponent_goals' => $data['opponent_goals'],
                'joker_type' => $joker,
            ]);

            if ($joker && ! $user->useJoker($joker, $bet)) {
                throw ValidationException::withMessages(['joker_type' => 'No jokers remaining']);
            }

            return $bet;
        });
    }
}
