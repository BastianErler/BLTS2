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

        /**
         * =========================
         * CREATE 3 SEASONS
         * =========================
         */
        $seasons = [
            [
                'name' => '22/23',
                'start_date' => now()->subYears(2)->startOfYear(),
                'end_date' => now()->subYears(2)->addMonths(4),
                'is_active' => false,
            ],
            [
                'name' => '23/24',
                'start_date' => now()->subYear()->startOfYear(),
                'end_date' => now()->subYear()->addMonths(4),
                'is_active' => false,
            ],
            [
                'name' => '24/25',
                'start_date' => now()->startOfYear(),
                'end_date' => now()->addMonths(4),
                'is_active' => true,
            ],
        ];

        $totalGamesCreated = 0;
        $totalBetsCreated = 0;

        foreach ($seasons as $seasonData) {
            $season = Season::firstOrCreate(
                ['name' => $seasonData['name']],
                [
                    'start_date' => $seasonData['start_date'],
                    'end_date' => $seasonData['end_date'],
                    'is_active' => $seasonData['is_active'],
                    'phase_1_multiplier' => 1.0,
                    'phase_2_multiplier' => 1.5,
                    'phase_3_multiplier' => 2.0,
                    'playoff_multiplier' => 3.0,
                ]
            );

            $this->command->info("✅ Season {$season->name} created/ensured");

            // Start game number after already existing games in this season
            $existingMaxNumber = Game::where('season_id', $season->id)->max('game_number') ?? 0;
            $gameNumber = max(1, (int) $existingMaxNumber + 1);

            $games = collect();

            // Helper: pick random opponent
            $pickOpponent = fn() => $opponents->random();

            /**
             * =========================
             * GAME DISTRIBUTION PER SEASON
             * =========================
             */
            if ($season->is_active) {
                // Current season: 24/25
                $pastGamesCount = 35;      // finished games
                $upcomingGamesCount = 20;  // scheduled games
                $liveGamesCount = 2;       // live games
            } else {
                // Past seasons: all finished
                $pastGamesCount = 52;      // all games finished
                $upcomingGamesCount = 0;
                $liveGamesCount = 0;
            }

            // PAST/FINISHED GAMES
            for ($i = 0; $i < $pastGamesCount; $i++) {
                $opponent = $pickOpponent();

                // Distribute dates throughout the season
                if ($season->is_active) {
                    // Current season: recent games
                    $daysAgo = 2 + ($i * 2);
                    $kickoffAt = now()->subDays($daysAgo)->setTime(rand(18, 20), [0, 30][array_rand([0, 1])]);
                } else {
                    // Past seasons: spread across the season period
                    $seasonStart = $seasonData['start_date'];
                    $seasonEnd = $seasonData['end_date'];
                    $daysBetween = $seasonStart->diffInDays($seasonEnd);
                    $dayOffset = ($daysBetween / $pastGamesCount) * $i;
                    $kickoffAt = $seasonStart->copy()->addDays($dayOffset)->setTime(rand(18, 20), [0, 30][array_rand([0, 1])]);
                }

                $ebbGoals = rand(0, 6);
                $oppGoals = rand(0, 6);

                // Avoid too many draws
                if ($ebbGoals === $oppGoals) {
                    if (rand(0, 1) === 0) $ebbGoals++;
                    else $oppGoals++;
                }

                $isDerby = rand(1, 8) === 1;
                $isPlayoff = $i >= ($pastGamesCount - 10) && rand(1, 3) === 1; // Last ~10 games more likely playoff

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

            // UPCOMING GAMES (only for current season)
            for ($i = 0; $i < $upcomingGamesCount; $i++) {
                $opponent = $pickOpponent();

                $daysAhead = 1 + ($i * 2);
                $kickoffAt = now()->addDays($daysAhead)->setTime(rand(18, 20), [0, 30][array_rand([0, 1])]);

                $isDerby = rand(1, 10) === 1;
                $isPlayoff = $i >= ($upcomingGamesCount - 5) && rand(1, 3) === 1;

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

            // LIVE GAMES (only for current season)
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

            $totalGamesCreated += $games->count();
            $this->command->info("   → {$games->count()} games for season {$season->name}");

            /**
             * =========================
             * CREATE BETS FOR THIS SEASON
             * =========================
             */
            $betCount = 0;

            $finishedGames = Game::where('season_id', $season->id)
                ->where('status', 'finished')
                ->get();

            foreach ($finishedGames as $game) {
                // For past seasons: all users bet on all games
                // For current season: randomize a bit more
                $usersForThisGame = $season->is_active && rand(1, 5) === 1
                    ? $users->random(rand(3, 5))
                    : $users;

                foreach ($usersForThisGame as $user) {
                    $already = Bet::where('user_id', $user->id)
                        ->where('game_id', $game->id)
                        ->exists();

                    if ($already) {
                        continue;
                    }

                    $eisbaerenGoals = rand(0, 6);
                    $opponentGoals = rand(0, 6);

                    // Avoid draw tips
                    if ($eisbaerenGoals === $opponentGoals && rand(1, 4) !== 1) {
                        if (rand(0, 1) === 0) $eisbaerenGoals++;
                        else $opponentGoals++;
                    }

                    $jokerType = null;
                    if ($user->jokers_remaining > 0 && rand(1, 8) === 1) {
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
                            'description' => "Bet on game #{$game->game_number} ({$season->name})",
                            'bet_id' => $bet->id,
                        ]);
                    }

                    $betCount++;
                }
            }

            // Upcoming games (only current season): some users have already tipped
            if ($season->is_active) {
                $upcomingGames = Game::where('season_id', $season->id)
                    ->where('status', 'scheduled')
                    ->orderBy('kickoff_at')
                    ->get();

                foreach ($upcomingGames as $game) {
                    foreach ($users as $user) {
                        // ~60% chance user has tipped
                        if (rand(1, 100) > 60) {
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

                        if ($eisbaerenGoals === $opponentGoals && rand(1, 5) !== 1) {
                            if (rand(0, 1) === 0) $eisbaerenGoals++;
                            else $opponentGoals++;
                        }

                        $jokerType = null;
                        if ($user->jokers_remaining > 0 && rand(1, 15) === 1) {
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

                        $betCount++;
                    }
                }
            }

            $totalBetsCreated += $betCount;
            $this->command->info("   → {$betCount} bets for season {$season->name}");
        }

        $this->command->info("✅ Total: {$totalGamesCreated} games across 3 seasons");
        $this->command->info("✅ Total: {$totalBetsCreated} bets created");

        /**
         * =========================
         * DEPOSITS (only once)
         * =========================
         */
        foreach ($users as $user) {
            $hasInitialDeposit = Transaction::where('user_id', $user->id)
                ->where('type', 'deposit')
                ->where('description', 'Initial deposit')
                ->exists();

            if (!$hasInitialDeposit) {
                Transaction::create([
                    'user_id' => $user->id,
                    'creator_id' => $admin->id,
                    'type' => 'deposit',
                    'amount' => 200.00,
                    'description' => 'Initial deposit',
                ]);
            }
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
