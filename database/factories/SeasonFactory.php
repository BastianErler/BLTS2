<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['24/25', '25/26', '23/24']),
            'winner_team_id' => null,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->addMonths(4),
            'is_active' => false,
            'phase_1_multiplier' => 1.0,
            'phase_2_multiplier' => 1.5,
            'phase_3_multiplier' => 2.0,
            'playoff_multiplier' => 3.0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }
}
