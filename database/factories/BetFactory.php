<?php

namespace Database\Factories;

use App\Models\Bet;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BetFactory extends Factory
{
    protected $model = Bet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'game_id' => Game::factory(),
            'eisbaeren_goals' => $this->faker->numberBetween(0, 7),
            'opponent_goals' => $this->faker->numberBetween(0, 7),
            'joker_type' => null,
            'joker_data' => null,
            'base_price' => 0,
            'multiplier' => 1.0,
            'final_price' => 0,
            'locked_at' => null,
        ];
    }

    public function withJoker(string $type = 'safety'): static
    {
        return $this->state(fn(array $attributes) => [
            'joker_type' => $type,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn(array $attributes) => [
            'locked_at' => now(),
        ]);
    }
}
