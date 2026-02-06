<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTest extends TestCase
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

    private function makeGame(array $overrides = []): Game
    {
        $season = $overrides['season'] ?? $this->makeSeason();
        $team = $overrides['team'] ?? $this->makeTeam();

        unset($overrides['season'], $overrides['team']);

        return Game::create(array_replace([
            'game_number' => 1,
            'opponent_id' => $team->id,
            'season_id' => $season->id,
            'is_home' => true,
            'kickoff_at' => now()->addDays(2)->setTime(19, 30),
            'status' => 'scheduled',
            'is_derby' => false,
            'is_playoff' => false,
            'difficulty_rating' => 1,
            'email_reminder_sent' => false,
            'sms_reminder_sent' => false,
        ], $overrides));
    }

    public function test_bet_deadline_is_kickoff_minus_6_hours(): void
    {
        $kickoff = now()->addDays(2)->setTime(19, 30);

        $game = $this->makeGame(['kickoff_at' => $kickoff]);

        $this->assertSame(
            $kickoff->copy()->subHours(6)->toIso8601String(),
            $game->betDeadline()->toIso8601String()
        );
    }

    public function test_can_bet_true_before_deadline_and_scheduled(): void
    {
        // kickoff in 8h => deadline in 2h => canBet true
        $kickoff = now()->addHours(8);

        $game = $this->makeGame([
            'kickoff_at' => $kickoff,
            'status' => 'scheduled',
        ]);

        $this->assertTrue($game->canBet());
    }

    public function test_can_bet_false_after_deadline(): void
    {
        // kickoff in 5h => deadline 1h ago => canBet false
        $kickoff = now()->addHours(5);

        $game = $this->makeGame([
            'kickoff_at' => $kickoff,
            'status' => 'scheduled',
        ]);

        $this->assertFalse($game->canBet());
    }

    public function test_can_bet_false_when_not_scheduled(): void
    {
        $game = $this->makeGame([
            'status' => 'finished',
        ]);

        $this->assertFalse($game->canBet());
    }
}
