<?php

namespace Database\Factories;

use App\Models\SeasonWinnerBet;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonWinnerBetFactory extends Factory
{
    protected $model = SeasonWinnerBet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'season_id' => Season::factory(),
            'team_id' => Team::factory(),
        ];
    }
}
