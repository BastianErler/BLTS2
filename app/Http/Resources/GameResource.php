<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $includeUserBet = $request->boolean('include_user_bet', true);

        return [
            'id' => $this->id,

            // FIX: match_number (nicht game_number)
            'match_number' => $this->match_number,

            'opponent' => [
                'id' => $this->opponent->id,
                'name' => $this->opponent->name,
                'short_name' => $this->opponent->short_name,
                'logo_url' => $this->opponent->logo_url,
            ],

            'season' => [
                'id' => $this->season->id,
                'name' => $this->season->name,
            ],

            'is_home' => (bool) $this->is_home,

            'kickoff_at' => $this->kickoff_at?->toIso8601String(),
            'kickoff_at_human' => $this->kickoff_at?->diffForHumans(),

            'eisbaeren_goals' => $this->eisbaeren_goals,
            'opponent_goals' => $this->opponent_goals,

            'status' => $this->status,

            'is_derby' => (bool) $this->is_derby,
            'is_playoff' => (bool) $this->is_playoff,

            'can_bet' => $this->canBet(),

            'bet_deadline_at' => $this->betDeadline()?->toIso8601String(),

            'is_finished' => $this->isFinished(),
            'winner' => $this->winner,
            'goal_difference' => $this->goalDifference,

            'user_bet' => $this->when(
                $includeUserBet && $request->user(),
                fn() => $this->bets()
                    ->where('user_id', $request->user()->id)
                    ->first()
            ),
        ];
    }
}
