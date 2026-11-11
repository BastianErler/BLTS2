<?php

namespace Tests\Feature;

use App\Models\Season;
use App\Models\SeasonParticipation;
use App\Models\User;
use App\Models\Game;
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
}
