<?php

namespace Database\Seeders;

use App\Models\Bet;
use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏒 Creating BLTS2 development data...');

        // Ensure teams exist first!
        $this->call(TeamSeeder::class);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@blts.test'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'is_admin' => true,
                'balance' => 0,
                'jokers_remaining' => 3,
            ]
        );

        // Create test users
        $users = collect([
            ['name' => 'Bastian', 'email' => 'bastian@blts.test'],
            ['name' => 'Lisa', 'email' => 'lisa@blts.test'],
            ['name' => 'Kevin', 'email' => 'kevin@blts.test'],
            ['name' => 'Maria', 'email' => 'maria@blts.test'],
            ['name' => 'Heiko', 'email' => 'heiko@blts.test'],
        ])->map(function ($data) {
            return User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => bcrypt('password'),
                    'wants_email_reminder' => true,
                    'balance' => 100.00,
                    'jokers_remaining' => 3,
                ]
            );
        });

        $this->command->info("✅ Created {$users->count()} users");

        // Get teams
        $eisbaeren = Team::where('name', 'Eisbären Berlin')->first();
        $opponents = Team::where('name', '!=', 'Eisbären Berlin')->get();

        if (!$eisbaeren || $opponents->isEmpty()) {
            $this->command->error("❌ Teams missing. Please check TeamSeeder.");
            return;
        }

        // Create current season
        $season = Season::firstOrCreate(
            ['name' => '24/25'],
            [
                'start_date' => now()->startOfYear(),
                'end_date' => now()->addMonths(4),
                'is_active' => true,
                'phase_1_multiplier' => 1.0,
                'phase_2_multiplier' => 1.5,
                'phase_3_multiplier' => 2.0,
                'playoff_multiplier' => 3.0,
            ]
        );

        $this->command->info('✅ Ensured season 24/25');

        /**
         * =========================
         * CONFIG: HOW MUCH DATA?
         * =========================
         */
        $pastGamesCount = 18;     // finished games
        $upcomingGamesCount = 12; // scheduled games
        $liveGamesCount = 2;      // live games (optional; set 0 if you don't want)

        // start game number after already existing games in this season
        $existingMaxNumber = Game::where('season_id', $season->id)->max('game_number') ?? 0;
        $gameNumber = max(1, (int) $existingMaxNumber + 1);

        /**
         * =========================
         * CREATE GAMES (IDEMPOTENT-ish)
         * We avoid duplicates by using season_id + game_number as stable identity.
         * =========================
         */
        $games = collect();

        // Helper: pick random opponent
        $pickOpponent = fn() => $opponents->random();

        // Past (finished) games: spread over last ~35 days
        for ($i = 0; $i < $pastGamesCount; $i++) {
            $opponent = $pickOpponent();

            // distribute dates: 2 days apart, newest closer to now
            $daysAgo = 2 + ($i * 2);
            $kickoffAt = now()
                ->subDays($daysAgo)
                ->setTime(rand(18, 20), [0, 30][array_rand([0, 1])]);

            // Realistic-ish scores
            $ebbGoals = rand(0, 6);
            $oppGoals = rand(0, 6);

            // avoid too many draws (your bet model marks draw as invalid)
            if ($ebbGoals === $oppGoals) {
                if (rand(0, 1) === 0) $ebbGoals++;
                else $oppGoals++;
            }

            $isDerby = rand(1, 8) === 1;   // ~12.5%
            $isPlayoff = rand(1, 12) === 1; // ~8%

            $game = Game::firstOrCreate(
                [
                    'season_id' => $season->id,
                    'game_number' => $gameNumber,
                ],
                [
                    'opponent_id' => $opponent->id,
                    'is_home' => (bool) rand(0, 1),
                    'kickoff_at' => $kickoffAt,
                    'eisbaeren_goals' => $ebbGoals,
                    'opponent_goals' => $oppGoals,
                    'status' => 'finished',
                    'is_derby' => $isDerby,
                    'is_playoff' => $isPlayoff,
                    'difficulty_rating' => rand(2, 4),
                ]
            );

            $games->push($game);
            $gameNumber++;
        }

        // Upcoming (scheduled) games: next ~30 days
        for ($i = 0; $i < $upcomingGamesCount; $i++) {
            $opponent = $pickOpponent();

            $daysAhead = 1 + ($i * 2);
            $kickoffAt = now()
                ->addDays($daysAhead)
                ->setTime(rand(18, 20), [0, 30][array_rand([0, 1])]);

            $isDerby = rand(1, 10) === 1;
            $isPlayoff = rand(1, 14) === 1;

            $game = Game::firstOrCreate(
                [
                    'season_id' => $season->id,
                    'game_number' => $gameNumber,
                ],
                [
                    'opponent_id' => $opponent->id,
                    'is_home' => (bool) rand(0, 1),
                    'kickoff_at' => $kickoffAt,
                    'eisbaeren_goals' => null,
                    'opponent_goals' => null,
                    'status' => 'scheduled',
                    'is_derby' => $isDerby,
                    'is_playoff' => $isPlayoff,
                    'difficulty_rating' => rand(2, 4),
                ]
            );

            $games->push($game);
            $gameNumber++;
        }

        // Live games (optional): now-ish
        for ($i = 0; $i < $liveGamesCount; $i++) {
            $opponent = $pickOpponent();

            $kickoffAt = now()->subMinutes(rand(10, 55));

            $game = Game::firstOrCreate(
                [
                    'season_id' => $season->id,
                    'game_number' => $gameNumber,
                ],
                [
                    'opponent_id' => $opponent->id,
                    'is_home' => (bool) rand(0, 1),
                    'kickoff_at' => $kickoffAt,
                    'eisbaeren_goals' => rand(0, 4),
                    'opponent_goals' => rand(0, 4),
                    'status' => 'live',
                    'is_derby' => rand(1, 12) === 1,
                    'is_playoff' => false,
                    'difficulty_rating' => rand(2, 4),
                ]
            );

            $games->push($game);
            $gameNumber++;
        }

        $this->command->info("✅ Ensured/created {$games->count()} games (added this run)");

        /**
         * =========================
         * CREATE BETS
         * =========================
         * - For finished games: everyone gets a bet (like before)
         * - For upcoming games: create some bets randomly (so you see "already tipped" in UI)
         * - Avoid duplicates: one bet per user per game
         */
        $betCount = 0;

        $finishedGames = Game::where('season_id', $season->id)
            ->where('status', 'finished')
            ->get();

        foreach ($finishedGames as $game) {
            foreach ($users as $user) {
                $already = Bet::where('user_id', $user->id)
                    ->where('game_id', $game->id)
                    ->exists();

                if ($already) {
                    continue;
                }

                $eisbaerenGoals = rand(0, 6);
                $opponentGoals = rand(0, 6);

                // avoid draw tips (invalid) too often
                if ($eisbaerenGoals === $opponentGoals && rand(1, 4) !== 1) {
                    if (rand(0, 1) === 0) $eisbaerenGoals++;
                    else $opponentGoals++;
                }

                $jokerType = null;
                if ($user->jokers_remaining > 0 && rand(1, 6) === 1) {
                    $jokerType = collect(['safety', 'double_down'])->random();
                }

                $bet = Bet::create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                    'eisbaeren_goals' => $eisbaerenGoals,
                    'opponent_goals' => $opponentGoals,
                    'joker_type' => $jokerType,
                ]);

                if ($jokerType) {
                    $user->useJoker($jokerType, $bet);
                }

                $bet->updatePrices();

                if ($bet->final_price > 0) {
                    Transaction::create([
                        'user_id' => $user->id,
                        'type' => 'bet_cost',
                        'amount' => -$bet->final_price,
                        'description' => "Bet on game #{$game->game_number}",
                        'bet_id' => $bet->id,
                    ]);
                }

                $betCount++;
            }
        }

        // Upcoming games: create bets for some users (not all), so UI shows mixed state
        $upcomingGames = Game::where('season_id', $season->id)
            ->where('status', 'scheduled')
            ->orderBy('kickoff_at')
            ->take(10)
            ->get();

        foreach ($upcomingGames as $game) {
            foreach ($users as $user) {
                // ~55% chance a user has already tipped upcoming games
                if (rand(1, 100) > 55) {
                    continue;
                }

                $already = Bet::where('user_id', $user->id)
                    ->where('game_id', $game->id)
                    ->exists();

                if ($already) {
                    continue;
                }

                $eisbaerenGoals = rand(0, 6);
                $opponentGoals = rand(0, 6);

                // avoid draw tips mostly
                if ($eisbaerenGoals === $opponentGoals && rand(1, 5) !== 1) {
                    if (rand(0, 1) === 0) $eisbaerenGoals++;
                    else $opponentGoals++;
                }

                $jokerType = null;
                if ($user->jokers_remaining > 0 && rand(1, 12) === 1) {
                    $jokerType = collect(['safety', 'double_down'])->random();
                }

                $bet = Bet::create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                    'eisbaeren_goals' => $eisbaerenGoals,
                    'opponent_goals' => $opponentGoals,
                    'joker_type' => $jokerType,
                ]);

                if ($jokerType) {
                    $user->useJoker($jokerType, $bet);
                }

                // upcoming: prices will be 0 because game not finished, that's fine
                $bet->updatePrices();

                $betCount++;
            }
        }

        $this->command->info("✅ Created {$betCount} bets (new this run)");

        /**
         * =========================
         * DEPOSITS (only once-ish)
         * =========================
         */
        foreach ($users as $user) {
            $hasInitialDeposit = Transaction::where('user_id', $user->id)
                ->where('type', 'deposit')
                ->where('description', 'Initial deposit')
                ->exists();

            if ($hasInitialDeposit) {
                continue;
            }

            Transaction::create([
                'user_id' => $user->id,
                'creator_id' => $admin->id,
                'type' => 'deposit',
                'amount' => 50.00,
                'description' => 'Initial deposit',
            ]);
        }

        // Update user balances
        foreach ($users as $user) {
            $user->updateBalance();
        }

        $this->command->info('✅ Updated user balances');

        // Output summary
        $this->command->info('');
        $this->command->line('🎯 <fg=green>Development data created!</>');
        $this->command->table(
            ['User', 'Balance', 'Jokers Left'],
            $users->map(function ($user) {
                return [
                    $user->name,
                    number_format((float) $user->balance, 2) . '€',
                    $user->jokers_remaining,
                ];
            })->toArray()
        );

        $this->command->info('');
        $this->command->info('🔐 Login credentials:');
        $this->command->line('Email: admin@blts.test / bastian@blts.test / lisa@blts.test');
        $this->command->line('Password: password');
    }
}
