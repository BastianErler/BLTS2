<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\TeamSeeder::class);
    }

    #[Test]
    public function user_can_get_list_of_games()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        Game::factory()->count(3)->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/games');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'game_number',
                        'opponent',
                        'kickoff_at',
                        'status',
                        'can_bet',
                    ]
                ]
            ]);
    }

    #[Test]
    public function user_can_get_upcoming_games()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        // Create past and future games
        Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'kickoff_at' => now()->subDays(2),
        ]);

        Game::factory()->count(3)->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'kickoff_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/games/upcoming');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function user_can_filter_games_by_status()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'status' => 'finished',
        ]);

        Game::factory()->count(2)->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/games?status=scheduled');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function user_can_get_single_game()
    {
        $user = User::factory()->create();
        $season = Season::factory()->create(['is_active' => true]);
        $opponent = Team::where('name', '!=', 'Eisbären Berlin')->first();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'opponent_id' => $opponent->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/games/{$game->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $game->id,
                    'game_number' => $game->game_number,
                ]
            ]);
    }

    #[Test]
    public function game_returns_404_if_not_found()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/games/999');

        $response->assertStatus(404);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_games()
    {
        $response = $this->getJson('/api/games');

        $response->assertStatus(401);
    }
}
