<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Clone request und user_bet im GameResource deaktivieren
        $gameRequest = $request->duplicate(
            query: array_merge($request->query(), ['include_user_bet' => '0'])
        );

        return [
            'id' => $this->id,

            'user' => $this->when($this->relationLoaded('user'), [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),

            'game' => $this->when(
                $this->relationLoaded('game'),
                fn() => (new GameResource($this->game))->toArray($gameRequest)
            ),

            'eisbaeren_goals' => (int) $this->eisbaeren_goals,
            'opponent_goals' => (int) $this->opponent_goals,
            'joker_type' => $this->joker_type,
            'base_price' => (float) $this->base_price,
            'multiplier' => (float) $this->multiplier,
            'final_price' => (float) $this->final_price,
            'locked_at' => $this->locked_at?->toIso8601String(),
            'is_locked' => !is_null($this->locked_at),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
