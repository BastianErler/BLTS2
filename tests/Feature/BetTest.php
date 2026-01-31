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

class BetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\TeamSeeder::class);
    }

    #[Test]
    public function user_can_place_a_bet()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'kickoff_at' => now()->addDays(2),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/bets', [
                'game_id' => $game->id,
                'eisbaeren_goals' => 4,
                'opponent_goals' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'eisbaeren_goals', 'opponent_goals']
            ]);

        $this->assertDatabaseHas('bets', [
            'user_id' => $user->id,
            'game_id' => $game->id,
            'eisbaeren_goals' => 4,
            'opponent_goals' => 2,
        ]);
    }

    #[Test]
    public function user_cannot_place_bet_after_game_starts()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'kickoff_at' => now()->subMinutes(30), // Already started
            'status' => 'live',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/bets', [
                'game_id' => $game->id,
                'eisbaeren_goals' => 4,
                'opponent_goals' => 2,
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Betting is closed for this game']);
    }

    #[Test]
    public function user_cannot_place_duplicate_bet()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'kickoff_at' => now()->addDays(2),
        ]);

        // Place first bet
        Bet::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        // Try to place second bet
        $response = $this->actingAs($user)
            ->postJson('/api/bets', [
                'game_id' => $game->id,
                'eisbaeren_goals' => 4,
                'opponent_goals' => 2,
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'You already placed a bet on this game']);
    }

    #[Test]
    public function user_can_update_their_bet_before_game_starts()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'kickoff_at' => now()->addDays(2),
        ]);

        $bet = Bet::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'eisbaeren_goals' => 3,
            'opponent_goals' => 1,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/bets/{$bet->id}", [
                'game_id' => $game->id,
                'eisbaeren_goals' => 5,
                'opponent_goals' => 2,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bets', [
            'id' => $bet->id,
            'eisbaeren_goals' => 5,
            'opponent_goals' => 2,
        ]);
    }

    #[Test]
    public function user_cannot_update_locked_bet()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
        ]);

        $bet = Bet::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'locked_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/bets/{$bet->id}", [
                'game_id' => $game->id,
                'eisbaeren_goals' => 5,
                'opponent_goals' => 2,
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Bet is locked and cannot be changed']);
    }

    #[Test]
    public function user_cannot_update_another_users_bet()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'kickoff_at' => now()->addDays(2),
        ]);

        $bet = Bet::factory()->create([
            'user_id' => $user1->id,
            'game_id' => $game->id,
        ]);

        $response = $this->actingAs($user2)
            ->putJson("/api/bets/{$bet->id}", [
                'game_id' => $game->id,
                'eisbaeren_goals' => 5,
                'opponent_goals' => 2,
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function user_can_delete_their_bet_before_game_starts()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'kickoff_at' => now()->addDays(2),
        ]);

        $bet = Bet::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/bets/{$bet->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('bets', ['id' => $bet->id]);
    }

    #[Test]
    public function user_can_get_their_bets()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
        ]);

        $games = Game::factory()->count(3)->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
        ]);

        foreach ($games as $game) {
            Bet::factory()->create([
                'user_id' => $user->id,
                'game_id' => $game->id,
            ]);
        }

        $response = $this->actingAs($user)
            ->getJson('/api/bets');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function user_can_place_bet_with_joker()
    {
        $user = User::factory()->create(['jokers_remaining' => 3]);
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'kickoff_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/bets', [
                'game_id' => $game->id,
                'eisbaeren_goals' => 4,
                'opponent_goals' => 2,
                'joker_type' => 'safety',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('bets', [
            'user_id' => $user->id,
            'joker_type' => 'safety',
        ]);

        $user->refresh();
        $this->assertEquals(2, $user->jokers_remaining);
    }

    #[Test]
    public function user_cannot_use_joker_when_none_remaining()
    {
        $user = User::factory()->create(['jokers_remaining' => 0]);
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'kickoff_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/bets', [
                'game_id' => $game->id,
                'eisbaeren_goals' => 4,
                'opponent_goals' => 2,
                'joker_type' => 'safety',
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'No jokers remaining']);
    }

    #[Test]
    public function bet_validation_requires_valid_goals()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'kickoff_at' => now()->addDays(2),
        ]);

        // Negative goals
        $response = $this->actingAs($user)
            ->postJson('/api/bets', [
                'game_id' => $game->id,
                'eisbaeren_goals' => -1,
                'opponent_goals' => 2,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['eisbaeren_goals']);

        // Too many goals
        $response = $this->actingAs($user)
            ->postJson('/api/bets', [
                'game_id' => $game->id,
                'eisbaeren_goals' => 25,
                'opponent_goals' => 2,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['eisbaeren_goals']);
    }
}
