<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->when($this->relationLoaded('user'), [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'game' => $this->when($this->relationLoaded('game'), function () {
                return [
                    'id' => $this->game->id,
                    'game_number' => $this->game->game_number,
                    'opponent' => [
                        'name' => $this->game->opponent->name,
                        'short_name' => $this->game->opponent->short_name,
                    ],
                    'kickoff_at' => $this->game->kickoff_at->toIso8601String(),
                    'status' => $this->game->status,
                    'is_finished' => $this->game->isFinished(),
                ];
            }),
            'eisbaeren_goals' => $this->eisbaeren_goals,
            'opponent_goals' => $this->opponent_goals,
            'joker_type' => $this->joker_type,
            'base_price' => (float) $this->base_price,
            'multiplier' => (float) $this->multiplier,
            'final_price' => (float) $this->final_price,
            'locked_at' => $this->locked_at?->toIso8601String(),
            'is_locked' => !is_null($this->locked_at),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
