<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        return [
            'game_number' => $this->faker->numberBetween(1, 100),
            'opponent_id' => Team::factory(),
            'season_id' => Season::factory(),
            'is_home' => $this->faker->boolean(),
            'kickoff_at' => \Carbon\Carbon::now()->addDays(rand(1, 30)),
            'eisbaeren_goals' => null,
            'opponent_goals' => null,
            'status' => 'scheduled',
            'is_derby' => false,
            'is_playoff' => false,
            'difficulty_rating' => $this->faker->numberBetween(2, 4),
            'email_reminder_sent' => false,
            'sms_reminder_sent' => false,
        ];
    }

    public function finished(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'finished',
            'eisbaeren_goals' => $this->faker->numberBetween(0, 7),
            'opponent_goals' => $this->faker->numberBetween(0, 7),
            'kickoff_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'scheduled',
            'kickoff_at' => $this->faker->dateTimeBetween('+2 days', '+30 days'),
        ]);
    }

    public function derby(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_derby' => true,
        ]);
    }

    public function playoff(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_playoff' => true,
        ]);
    }
}
