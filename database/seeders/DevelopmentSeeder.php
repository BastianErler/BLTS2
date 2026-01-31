<?php

namespace Database\Seeders;

use App\Models\Bet;
use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

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

        $this->command->info('✅ Created season 24/25');

        // Create games
        $gamesData = [
            // Past games (finished)
            ['opponent' => 'Adler Mannheim', 'days_ago' => 7, 'eisbaeren' => 4, 'opponent_goals' => 2, 'is_home' => true, 'is_derby' => true],
            ['opponent' => 'EHC Red Bull München', 'days_ago' => 5, 'eisbaeren' => 3, 'opponent_goals' => 3, 'is_home' => false],
            ['opponent' => 'Kölner Haie', 'days_ago' => 3, 'eisbaeren' => 2, 'opponent_goals' => 1, 'is_home' => true],
            ['opponent' => 'Grizzlys Wolfsburg', 'days_ago' => 2, 'eisbaeren' => 5, 'opponent_goals' => 3, 'is_home' => true],

            // Upcoming games (scheduled)
            ['opponent' => 'Straubing Tigers', 'days_ahead' => 1, 'is_home' => false],
            ['opponent' => 'Düsseldorfer EG', 'days_ahead' => 3, 'is_home' => true],
            ['opponent' => 'EHC Red Bull München', 'days_ahead' => 5, 'is_home' => false, 'is_derby' => true],
            ['opponent' => 'Adler Mannheim', 'days_ahead' => 7, 'is_home' => true, 'is_playoff' => true],
        ];

        $games = collect();
        $gameNumber = 1;

        foreach ($gamesData as $data) {
            $opponent = Team::where('name', $data['opponent'])->first();

            if (!$opponent) {
                $this->command->warn("⚠️  Team '{$data['opponent']}' not found, skipping...");
                continue;
            }

            $kickoffAt = isset($data['days_ago'])
                ? now()->subDays($data['days_ago'])->setTime(19, 30)
                : now()->addDays($data['days_ahead'])->setTime(19, 30);

            $game = Game::create([
                'game_number' => $gameNumber++,
                'opponent_id' => $opponent->id,
                'season_id' => $season->id,
                'is_home' => $data['is_home'] ?? true,
                'kickoff_at' => $kickoffAt,
                'eisbaeren_goals' => $data['eisbaeren'] ?? null,
                'opponent_goals' => $data['opponent_goals'] ?? null,
                'status' => isset($data['eisbaeren']) ? 'finished' : 'scheduled',
                'is_derby' => $data['is_derby'] ?? false,
                'is_playoff' => $data['is_playoff'] ?? false,
                'difficulty_rating' => rand(2, 4),
            ]);

            $games->push($game);
        }

        $this->command->info("✅ Created {$games->count()} games");

        // Create bets for past games
        $finishedGames = $games->where('status', 'finished');
        $betCount = 0;

        foreach ($finishedGames as $game) {
            foreach ($users as $user) {
                // Random bet with some variation
                $eisbaerenGoals = rand(1, 5);
                $opponentGoals = rand(0, 4);

                // Sometimes use a joker
                $jokerType = null;
                if ($user->jokers_remaining > 0 && rand(1, 5) === 1) {
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

                // Calculate prices
                $bet->updatePrices();

                // Create transaction
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

        $this->command->info("✅ Created {$betCount} bets");

        // Create some deposits
        foreach ($users as $user) {
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
            ['User', 'Balance', 'Total Cost', 'Jokers Left'],
            $users->map(function ($user) use ($season) {
                return [
                    $user->name,
                    number_format($user->balance, 2) . '€',
                    number_format($user->getTotalCostForSeason($season), 2) . '€',
                    $user->jokers_remaining,
                ];
            })
        );

        $this->command->info('');
        $this->command->info('🔐 Login credentials:');
        $this->command->line('Email: admin@blts.test / bastian@blts.test / lisa@blts.test');
        $this->command->line('Password: password');
    }
}
