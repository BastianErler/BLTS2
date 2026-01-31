<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\TeamSeeder::class);
    }

    #[Test]
    public function user_can_get_leaderboard()
    {
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $users = User::factory()->count(3)->create();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'status' => 'finished',
            'eisbaeren_goals' => 4,
            'opponent_goals' => 2,
        ]);

        foreach ($users as $user) {
            $bet = Bet::factory()->create([
                'user_id' => $user->id,
                'game_id' => $game->id,
                'eisbaeren_goals' => rand(1, 5),
                'opponent_goals' => rand(1, 5),
            ]);
            $bet->updatePrices();
        }

        $response = $this->actingAs($users->first())
            ->getJson('/api/leaderboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'season' => ['id', 'name'],
                'leaderboard' => [
                    '*' => [
                        'id',
                        'name',
                        'total_cost',
                        'bet_count',
                        'exact_bets',
                        'average_cost',
                        'jokers_remaining',
                        'position',
                    ]
                ],
                'my_position',
            ]);
    }

    #[Test]
    public function leaderboard_is_sorted_by_total_cost_ascending()
    {
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $user1 = User::factory()->create(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        $user3 = User::factory()->create(['name' => 'User 3']);

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'status' => 'finished',
            'eisbaeren_goals' => 4,
            'opponent_goals' => 2,
        ]);

        // User 1: Exact bet (0€)
        $bet1 = Bet::factory()->create([
            'user_id' => $user1->id,
            'game_id' => $game->id,
            'eisbaeren_goals' => 4,
            'opponent_goals' => 2,
        ]);
        $bet1->updatePrices();

        // User 2: Wrong bet (1€)
        $bet2 = Bet::factory()->create([
            'user_id' => $user2->id,
            'game_id' => $game->id,
            'eisbaeren_goals' => 2,
            'opponent_goals' => 4,
        ]);
        $bet2->updatePrices();

        // User 3: Tendency (0.30€)
        $bet3 = Bet::factory()->create([
            'user_id' => $user3->id,
            'game_id' => $game->id,
            'eisbaeren_goals' => 3,
            'opponent_goals' => 1,
        ]);
        $bet3->updatePrices();

        $response = $this->actingAs($user1)
            ->getJson('/api/leaderboard');

        $leaderboard = $response->json('leaderboard');

        // User 1 should be first (0€)
        $this->assertEquals($user1->id, $leaderboard[0]['id']);
        $this->assertEquals(1, $leaderboard[0]['position']);

        // User 3 should be second (0.30€)
        $this->assertEquals($user3->id, $leaderboard[1]['id']);
        $this->assertEquals(2, $leaderboard[1]['position']);

        // User 2 should be last (1€)
        $this->assertEquals($user2->id, $leaderboard[2]['id']);
        $this->assertEquals(3, $leaderboard[2]['position']);
    }

    #[Test]
    public function user_can_get_their_stats()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'status' => 'finished',
            'eisbaeren_goals' => 4,
            'opponent_goals' => 2,
        ]);

        $bet = Bet::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'eisbaeren_goals' => 4,
            'opponent_goals' => 2,
        ]);
        $bet->updatePrices();

        $response = $this->actingAs($user)
            ->getJson('/api/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'balance', 'jokers_remaining'],
                'season' => ['id', 'name'],
                'total_cost',
                'bet_count',
                'exact_bets',
                'tendency_bets',
                'winner_bets',
                'wrong_bets',
                'jokers_used',
                'position',
            ]);
    }

    #[Test]
    public function user_can_get_another_users_stats()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user1)
            ->getJson("/api/stats/{$user2->id}");

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user2->id,
                    'name' => $user2->name,
                ]
            ]);
    }

    #[Test]
    public function leaderboard_only_includes_finished_games()
    {
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();
        $user = User::factory()->create();

        // Finished game
        $finishedGame = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'status' => 'finished',
            'eisbaeren_goals' => 4,
            'opponent_goals' => 2,
        ]);

        $bet1 = Bet::factory()->create([
            'user_id' => $user->id,
            'game_id' => $finishedGame->id,
            'eisbaeren_goals' => 2,
            'opponent_goals' => 4,
        ]);
        $bet1->updatePrices();

        // Scheduled game (should not count)
        $scheduledGame = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'status' => 'scheduled',
            'kickoff_at' => now()->addDays(2),
        ]);

        Bet::factory()->create([
            'user_id' => $user->id,
            'game_id' => $scheduledGame->id,
            'eisbaeren_goals' => 5,
            'opponent_goals' => 5,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/leaderboard');

        $leaderboard = $response->json('leaderboard');
        $userStats = collect($leaderboard)->firstWhere('id', $user->id);

        // Should only count the finished game
        $this->assertEquals(1, $userStats['bet_count']);
    }

    #[Test]
    public function admins_are_excluded_from_leaderboard()
    {
        $season = Season::factory()->create(['is_active' => true]);
        $admin = User::factory()->create(['is_admin' => true]);
        $regularUser = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($regularUser)
            ->getJson('/api/leaderboard');

        $leaderboard = $response->json('leaderboard');
        $adminInLeaderboard = collect($leaderboard)->contains('id', $admin->id);

        $this->assertFalse($adminInLeaderboard);
    }
}
