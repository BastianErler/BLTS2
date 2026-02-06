<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeSeason(): Season
    {
        return Season::create([
            'name' => 'Saison 25/26',
            'is_active' => true,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => null,
        ]);
    }

    private function makeTeam(): Team
    {
        return Team::create([
            'name' => 'Opponent',
            'short_name' => 'OPP',
            'logo_url' => null,
        ]);
    }

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_replace([
            'name' => 'User ' . uniqid(),
            'email' => uniqid('u') . '@test.local',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'balance' => 0,
            'jokers_remaining' => 3,
            'wants_email_reminder' => false,
            'wants_sms_reminder' => false,
            'notification_settings' => [],
        ], $overrides));
    }

    private function makeFinishedGame(Season $season, Team $team, array $overrides = []): Game
    {
        return Game::create(array_replace([
            'game_number' => 1,
            'opponent_id' => $team->id,
            'season_id' => $season->id,
            'is_home' => true,
            'kickoff_at' => now()->subDays(2)->setTime(19, 30),
            'status' => 'finished',
            'eisbaeren_goals' => 3,
            'opponent_goals' => 1,
            'is_derby' => false,
            'is_playoff' => false,
            'difficulty_rating' => 1,
            'email_reminder_sent' => false,
            'sms_reminder_sent' => false,
        ], $overrides));
    }

    private function placeBet(User $user, Game $game, float $basePrice, float $finalPrice): Bet
    {
        return Bet::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'eisbaeren_goals' => 1,
            'opponent_goals' => 2,
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'locked_at' => now(),
        ]);
    }

    public function test_user_can_get_leaderboard(): void
    {
        $season = $this->makeSeason();
        $team = $this->makeTeam();

        $users = collect([
            $this->makeUser(['name' => 'U1']),
            $this->makeUser(['name' => 'U2']),
            $this->makeUser(['name' => 'U3']),
        ]);

        $game = $this->makeFinishedGame($season, $team);

        // some costs
        $this->placeBet($users[0], $game, 0.00, 0.00);
        $this->placeBet($users[1], $game, 0.60, 0.60);
        $this->placeBet($users[2], $game, 0.30, 0.30);

        $response = $this->actingAs($users->first())->getJson('/api/leaderboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'season' => ['id', 'name'],
                'entries' => [
                    '*' => [
                        'id',
                        'name',
                        'total_cost',
                        'bet_count',
                        'exact_bets',
                        'average_cost',
                        'jokers_remaining',
                        'rank',
                        'delta',
                        'is_me',
                    ],
                ],
                'top3',
                'me',
                'generated_at',
            ]);
    }

    public function test_leaderboard_is_sorted_by_total_cost_ascending(): void
    {
        $season = $this->makeSeason();
        $team = $this->makeTeam();

        $user1 = $this->makeUser(['name' => 'U1']);
        $user2 = $this->makeUser(['name' => 'U2']);
        $user3 = $this->makeUser(['name' => 'U3']);

        $game = $this->makeFinishedGame($season, $team);

        // User1: 0.00
        $this->placeBet($user1, $game, 0.00, 0.00);

        // User3: 0.30
        $this->placeBet($user3, $game, 0.30, 0.30);

        // User2: 0.60
        $this->placeBet($user2, $game, 0.60, 0.60);

        $response = $this->actingAs($user1)->getJson('/api/leaderboard');
        $response->assertStatus(200);

        $entries = $response->json('entries');
        $this->assertIsArray($entries);

        $this->assertSame($user1->id, $entries[0]['id']);
        $this->assertSame($user3->id, $entries[1]['id']);
        $this->assertSame($user2->id, $entries[2]['id']);

        // rank should start at 1
        $this->assertSame(1, $entries[0]['rank']);
        $this->assertSame(2, $entries[1]['rank']);
        $this->assertSame(3, $entries[2]['rank']);
    }

    public function test_user_can_get_their_stats(): void
    {
        $season = $this->makeSeason();
        $team = $this->makeTeam();

        $user = $this->makeUser();
        $game = $this->makeFinishedGame($season, $team);

        $this->placeBet($user, $game, 0.30, 0.30);

        $response = $this->actingAs($user)->getJson('/api/leaderboard/user-stats');
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

    public function test_user_can_get_another_users_stats(): void
    {
        $this->makeSeason(); // ensure current season exists
        $a = $this->makeUser();
        $b = $this->makeUser();

        $response = $this->actingAs($a)->getJson("/api/leaderboard/user-stats/{$b->id}");
        $response->assertStatus(200)
            ->assertJsonPath('user.id', $b->id);
    }

    public function test_leaderboard_only_includes_finished_games(): void
    {
        $season = $this->makeSeason();
        $team = $this->makeTeam();

        $user = $this->makeUser();

        $finished = $this->makeFinishedGame($season, $team, [
            'kickoff_at' => now()->subDays(2),
            'game_number' => 1,
        ]);

        $scheduled = Game::create([
            'game_number' => 2,
            'opponent_id' => $team->id,
            'season_id' => $season->id,
            'is_home' => true,
            'kickoff_at' => now()->addDays(2),
            'status' => 'scheduled',
            'is_derby' => false,
            'is_playoff' => false,
            'difficulty_rating' => 1,
            'email_reminder_sent' => false,
            'sms_reminder_sent' => false,
        ]);

        // finished bet should count
        $this->placeBet($user, $finished, 0.30, 0.30);

        // scheduled bet should NOT count in leaderboard aggregation (join filters finished)
        Bet::create([
            'user_id' => $user->id,
            'game_id' => $scheduled->id,
            'eisbaeren_goals' => 1,
            'opponent_goals' => 2,
            'base_price' => 1.00,
            'final_price' => 1.00,
            'locked_at' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/leaderboard');
        $response->assertStatus(200);

        $entries = $response->json('entries');
        $row = collect($entries)->firstWhere('id', $user->id);

        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row['bet_count']);
        $this->assertSame(0.30, (float) $row['total_cost']);
    }

    public function test_admins_are_excluded_from_leaderboard(): void
    {
        $season = $this->makeSeason();
        $team = $this->makeTeam();

        $admin = $this->makeUser(['is_admin' => true, 'name' => 'Admin']);
        $user = $this->makeUser(['is_admin' => false, 'name' => 'User']);

        $game = $this->makeFinishedGame($season, $team);

        $this->placeBet($admin, $game, 0.30, 0.30);
        $this->placeBet($user, $game, 0.60, 0.60);

        $response = $this->actingAs($user)->getJson('/api/leaderboard');
        $response->assertStatus(200);

        $ids = collect($response->json('entries'))->pluck('id')->all();

        $this->assertFalse(in_array($admin->id, $ids, true));
        $this->assertTrue(in_array($user->id, $ids, true));
    }
}
