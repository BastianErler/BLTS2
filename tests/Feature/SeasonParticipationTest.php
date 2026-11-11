<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Game;
use App\Models\Season;
use App\Models\SeasonParticipation;
use App\Models\User;
use App\Services\LeaderboardService;
use App\Services\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonParticipationTest extends TestCase
{
    use RefreshDatabase;

    public function test_join_and_admin_approve_withdrawal(): void
    {
        $season = Season::factory()->create(['is_active' => true]);
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user)->postJson('/api/season-participation')->assertOk();
        $participation = SeasonParticipation::where('season_id', $season->id)->where('user_id', $user->id)->firstOrFail();
        $this->postJson('/api/season-participation/withdrawal')->assertOk();
        $this->actingAs($user)->getJson('/api/admin/withdrawal-requests')->assertForbidden();
        $this->actingAs($admin)->getJson('/api/admin/withdrawal-requests')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/admin/withdrawal-requests/'.$participation->id, ['approve' => true])->assertOk();
        $this->assertNotNull($participation->fresh()->withdrawn_at);
        $this->assertSame('0.00', $participation->fresh()->frozen_cost);
    }

    public function test_user_must_join_before_placing_a_bet(): void
    {
        $season = Season::factory()->create(['is_active' => true]);
        $game = Game::factory()->create(['season_id' => $season->id, 'status' => 'scheduled', 'kickoff_at' => now()->addDays(2)]);
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/bets', ['game_id' => $game->id, 'eisbaeren_goals' => 2, 'opponent_goals' => 1])->assertUnprocessable();
        $this->assertDatabaseMissing('bets', ['game_id' => $game->id, 'user_id' => $user->id]);
    }

    public function test_frozen_cost_remains_visible_after_withdrawal(): void
    {
        $season = Season::factory()->create(['is_active' => true]);
        $user = User::factory()->create();
        SeasonParticipation::create(['user_id' => $user->id, 'season_id' => $season->id, 'joined_at' => now()->subMonth(), 'withdrawn_at' => now(), 'frozen_cost' => 4.25]);
        $this->actingAs($user)->getJson('/api/leaderboard/user-stats?season_id='.$season->id)->assertOk()->assertJsonPath('total_cost', 4.25);
    }

    public function test_withdrawn_user_cannot_change_or_delete_an_existing_bet(): void
    {
        $season = Season::factory()->create(['is_active' => true]);
        $user = User::factory()->create();
        $game = Game::factory()->create(['season_id' => $season->id, 'status' => 'scheduled', 'kickoff_at' => now()->addDays(2)]);
        $bet = Bet::factory()->create(['user_id' => $user->id, 'game_id' => $game->id, 'locked_at' => null]);
        SeasonParticipation::create(['user_id' => $user->id, 'season_id' => $season->id, 'joined_at' => now()->subMonth(), 'withdrawn_at' => now(), 'frozen_cost' => 0]);
        $this->actingAs($user)->putJson('/api/bets/'.$bet->id, ['eisbaeren_goals' => 2, 'opponent_goals' => 1])->assertForbidden();
        $this->deleteJson('/api/bets/'.$bet->id)->assertForbidden();
        $this->assertDatabaseHas('bets', ['id' => $bet->id]);
    }

    public function test_late_join_requires_explicit_acceptance_and_ranks_without_bets(): void
    {
        $season = Season::factory()->create(['is_active' => true]);
        Game::factory()->create(['season_id' => $season->id, 'status' => 'finished', 'kickoff_at' => now()->subDay()]);
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/season-participation')->assertUnprocessable();
        $this->postJson('/api/season-participation', ['accept_previous_games' => true])->assertOk();
        $ranking = app(LeaderboardService::class)->buildRanking($season);
        $this->assertSame($user->id, $ranking[0]['id']);
        $this->assertSame(1.0, $ranking[0]['total_cost']);
    }

    public function test_request_sends_push_once_and_approval_freezes_missing_bet_cost(): void
    {
        $season = Season::factory()->create(['is_active' => true]);
        Game::factory()->create(['season_id' => $season->id, 'status' => 'finished', 'kickoff_at' => now()->subDay()]);
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $participation = SeasonParticipation::create(['user_id' => $user->id, 'season_id' => $season->id, 'joined_at' => now()]);
        $sender = \Mockery::mock(PushSender::class);
        $sender->shouldReceive('sendToUser')->once()->andReturn(['sent' => 1, 'total' => 1, 'reports' => []]);
        $this->app->instance(PushSender::class, $sender);
        $this->actingAs($user)->postJson('/api/season-participation/withdrawal')->assertOk();
        $this->postJson('/api/season-participation/withdrawal')->assertOk();
        $this->actingAs($admin)->postJson('/api/admin/withdrawal-requests/'.$participation->id, ['approve' => true])->assertOk();
        $this->assertSame('1.00', $participation->fresh()->frozen_cost);
        Game::factory()->create(['season_id' => $season->id, 'status' => 'finished', 'kickoff_at' => now()]);
        $this->actingAs($user)->getJson('/api/leaderboard/user-stats?season_id='.$season->id)->assertJsonPath('total_cost', 1);
    }
}
