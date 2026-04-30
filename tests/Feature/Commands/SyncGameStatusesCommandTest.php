<?php

namespace Tests\Feature\Commands;

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncGameStatusesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_scheduled_past_game_to_live(): void
    {
        $season = Season::create([
            'name' => 'Saison 25/26',
            'is_active' => true,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => null,
        ]);

        $team = Team::create([
            'name' => 'Opponent',
            'short_name' => 'OPP',
            'logo_url' => null,
        ]);

        $game = Game::create([
            'game_number' => 1,
            'opponent_id' => $team->id,
            'season_id' => $season->id,
            'is_home' => true,
            'kickoff_at' => now()->subDay(),
            'status' => 'scheduled',
            'is_derby' => false,
            'is_playoff' => false,
            'difficulty_rating' => 1,
            'email_reminder_sent' => false,
            'sms_reminder_sent' => false,
        ]);

        $this->artisan('games:sync-status')
            ->expectsOutputToContain("g#{$game->id}: scheduled -> live")
            ->assertExitCode(0);

        $this->assertSame('live', $game->fresh()->status);
    }
}
