<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Game;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonParticipationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_bets_are_backfilled_once_per_user_and_season(): void
    {
        $migration = require database_path('migrations/2026_11_11_000001_create_season_participations_table.php');
        $migration->down();
        $season = Season::factory()->create();
        $user = User::factory()->create();
        foreach (range(1, 2) as $number) {
            $game = Game::factory()->create(['season_id' => $season->id, 'game_number' => $number]);
            Bet::factory()->create(['user_id' => $user->id, 'game_id' => $game->id]);
        }
        $migration->up();
        $this->assertDatabaseCount('season_participations', 1);
        $this->assertDatabaseHas('season_participations', ['season_id' => $season->id, 'user_id' => $user->id]);
    }
}
