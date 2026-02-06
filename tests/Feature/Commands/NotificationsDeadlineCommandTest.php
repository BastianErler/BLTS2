<?php

namespace Tests\Feature\Commands;

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Services\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class NotificationsDeadlineCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_deadline_command_finds_game_in_window_and_outputs_dry_line(): void
    {
        // Ensure NO sending happens in dry-run
        $mock = Mockery::mock(PushSender::class);
        $mock->shouldNotReceive('sendToUser');
        $this->app->instance(PushSender::class, $mock);

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

        // Command looks for games whose betDeadline is "now + minutes ± window/2".
        // Since betDeadline = kickoff - 6h, we set kickoff accordingly:
        $minutes = 120;
        $window = 120;

        // Put it safely inside the searched window (add 6h, add minutes, plus a small offset)
        $kickoff = now()->addHours(6)->addMinutes($minutes)->addMinutes(1);

        $game = Game::create([
            'game_number' => 1,
            'opponent_id' => $team->id,
            'season_id' => $season->id,
            'is_home' => true,
            'kickoff_at' => $kickoff,
            'status' => 'scheduled',
            'is_derby' => false,
            'is_playoff' => false,
            'difficulty_rating' => 1,
            'email_reminder_sent' => false,
            'sms_reminder_sent' => false,
        ]);

        $user = User::create([
            'name' => 'Bastian',
            'email' => 'bastian@test.local',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'balance' => 0,
            'jokers_remaining' => 3,
            'wants_email_reminder' => false,
            'wants_sms_reminder' => false,
            'notification_settings' => [
                'v' => 1,
                'push_enabled' => true,
                'remind_before_deadline' => true,
                'remind_before_deadline_minutes' => $minutes,
            ],
        ]);

        $this->artisan('notifications:deadline', [
            '--dry-run' => true,
            '--minutes' => $minutes,
            '--window' => $window,
        ])
            ->expectsOutputToContain("DRY deadline u#{$user->id} g#{$game->id}")
            ->assertExitCode(0);
    }
}
