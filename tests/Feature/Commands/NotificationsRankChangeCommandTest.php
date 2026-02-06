<?php

namespace Tests\Feature\Commands;

use App\Models\Season;
use App\Models\User;
use App\Services\LeaderboardService;
use App\Services\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class NotificationsRankChangeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_rank_change_command_outputs_dry_line_and_does_not_snapshot_in_dry_run(): void
    {
        // PushSender should not send in dry-run
        $push = Mockery::mock(PushSender::class);
        $push->shouldNotReceive('sendToUser');
        $this->app->instance(PushSender::class, $push);

        $season = Season::create([
            'name' => 'Saison 25/26',
            'is_active' => true,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => null,
        ]);

        $user = User::create([
            'name' => 'Lisa',
            'email' => 'lisa@test.local',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'balance' => 0,
            'jokers_remaining' => 3,
            'wants_email_reminder' => false,
            'wants_sms_reminder' => false,
            'notification_settings' => [
                'v' => 1,
                'push_enabled' => true,
                'notify_on_rank_change' => true,
                'rank_change_threshold' => 3,
                'rank_last_position' => 10,
            ],
        ]);

        // Mock LeaderboardService::rankForUser to simulate rank improved from 10 -> 5 (delta 5 >= threshold 3)
        $lb = Mockery::mock(LeaderboardService::class);
        $lb->shouldReceive('rankForUser')
            ->andReturnUsing(function ($u, $s) use ($user, $season) {
                if ((int)$u->id === (int)$user->id && (int)$s->id === (int)$season->id) {
                    return 5;
                }
                return null;
            });

        $this->app->instance(LeaderboardService::class, $lb);

        $this->artisan('notifications:rank-change', [
            '--dry-run' => true,
            '--snapshot-after' => true,
        ])
            ->expectsOutputToContain("DRY rank-change u#{$user->id}: 10 -> 5")
            ->assertExitCode(0);

        // dry-run must NOT update snapshot
        $fresh = $user->fresh();
        $this->assertSame(10, (int)($fresh->notification_settings['rank_last_position'] ?? 0));
    }
}
