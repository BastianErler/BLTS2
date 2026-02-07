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

        // Ensure teams exist first
        $this->call(TeamSeeder::class);

        // Admin
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

        // // Users
        // $users = collect([
        //     ['name' => 'Bastian', 'email' => 'bastian@blts.test'],
        //     ['name' => 'Lisa', 'email' => 'lisa@blts.test'],
        //     ['name' => 'Kevin', 'email' => 'kevin@blts.test'],
        //     ['name' => 'Maria', 'email' => 'maria@blts.test'],
        //     ['name' => 'Heiko', 'email' => 'heiko@blts.test'],
        // ])->map(
        //     fn($data) =>
        //     User::firstOrCreate(
        //         ['email' => $data['email']],
        //         [
        //             'name' => $data['name'],
        //             'password' => bcrypt('password'),
        //             'wants_email_reminder' => true,
        //             'balance' => 100.00,
        //             'jokers_remaining' => 3,
        //         ]
        //     )
        // );

        // $this->command->info("✅ Created {$users->count()} users");

        // $eisbaeren = Team::where('name', 'Eisbären Berlin')->first();
        // $opponents = Team::where('name', '!=', 'Eisbären Berlin')->get();

        // if (!$eisbaeren || $opponents->isEmpty()) {
        //     $this->command->error('❌ Teams missing. Please check TeamSeeder.');
        //     return;
        // }

        // /**
        //  * =========================
        //  * SEASONS
        //  * =========================
        //  */
        // $seasons = [
        //     [
        //         'name' => '22/23',
        //         'start_date' => now()->subYears(2)->startOfYear(),
        //         'end_date' => now()->subYears(2)->addMonths(4),
        //         'is_active' => false,
        //     ],
        //     [
        //         'name' => '23/24',
        //         'start_date' => now()->subYear()->startOfYear(),
        //         'end_date' => now()->subYear()->addMonths(4),
        //         'is_active' => false,
        //     ],
        //     [
        //         'name' => '24/25',
        //         'start_date' => now()->startOfYear(),
        //         'end_date' => now()->addMonths(4),
        //         'is_active' => true,
        //     ],
        // ];

        // $totalGamesCreated = 0;
        // $totalBetsCreated = 0;

        // foreach ($seasons as $seasonData) {
        //     $season = Season::firstOrCreate(
        //         ['name' => $seasonData['name']],
        //         [
        //             'start_date' => $seasonData['start_date'],
        //             'end_date' => $seasonData['end_date'],
        //             'is_active' => $seasonData['is_active'],
        //             'phase_1_multiplier' => 1.0,
        //             'phase_2_multiplier' => 1.5,
        //             'phase_3_multiplier' => 2.0,
        //             'playoff_multiplier' => 3.0,
        //         ]
        //     );

        //     $this->command->info("✅ Season {$season->name} created/ensured");

        //     /**
        //      * =========================
        //      * SKIP GAMES FOR ACTIVE SEASON
        //      * =========================
        //      */
        //     if ($season->is_active) {
        //         $this->command->line("   → Skipping games & bets for active season {$season->name}");
        //         continue;
        //     }

        //     /**
        //      * =========================
        //      * PAST SEASONS: CREATE GAMES
        //      * =========================
        //      */
        //     $existingMaxNumber = Game::where('season_id', $season->id)->max('game_number') ?? 0;
        //     $gameNumber = max(1, (int) $existingMaxNumber + 1);

        //     $games = collect();
        //     $seasonStart = $seasonData['start_date'];
        //     $seasonEnd = $seasonData['end_date'];
        //     $daysBetween = $seasonStart->diffInDays($seasonEnd);

        //     for ($i = 0; $i < 52; $i++) {
        //         $opponent = $opponents->random();

        //         $dayOffset = (int) (($daysBetween / 52) * $i);
        //         $kickoffAt = $seasonStart
        //             ->copy()
        //             ->addDays($dayOffset)
        //             ->setTime(rand(18, 20), [0, 30][array_rand([0, 1])]);

        //         $ebbGoals = rand(0, 6);
        //         $oppGoals = rand(0, 6);
        //         if ($ebbGoals === $oppGoals) {
        //             rand(0, 1) ? $ebbGoals++ : $oppGoals++;
        //         }

        //         $game = Game::firstOrCreate(
        //             [
        //                 'season_id' => $season->id,
        //                 'game_number' => $gameNumber,
        //             ],
        //             [
        //                 'opponent_id' => $opponent->id,
        //                 'is_home' => (bool) rand(0, 1),
        //                 'kickoff_at' => $kickoffAt,
        //                 'eisbaeren_goals' => $ebbGoals,
        //                 'opponent_goals' => $oppGoals,
        //                 'status' => 'finished',
        //                 'is_derby' => rand(1, 8) === 1,
        //                 'is_playoff' => $i >= 42,
        //                 'difficulty_rating' => rand(2, 4),
        //             ]
        //         );

        //         $games->push($game);
        //         $gameNumber++;
        //     }

        //     $totalGamesCreated += $games->count();
        //     $this->command->info("   → {$games->count()} games created");

        //     /**
        //      * =========================
        //      * BETS (PAST SEASONS ONLY)
        //      * =========================
        //      */
        //     $betCount = 0;

        //     foreach ($games as $game) {
        //         foreach ($users as $user) {
        //             if (Bet::where('user_id', $user->id)->where('game_id', $game->id)->exists()) {
        //                 continue;
        //             }

        //             $ebbGoals = rand(0, 6);
        //             $oppGoals = rand(0, 6);
        //             if ($ebbGoals === $oppGoals && rand(1, 4) !== 1) {
        //                 rand(0, 1) ? $ebbGoals++ : $oppGoals++;
        //             }

        //             $jokerType = ($user->jokers_remaining > 0 && rand(1, 8) === 1)
        //                 ? collect(['safety', 'double_down'])->random()
        //                 : null;

        //             $bet = Bet::create([
        //                 'user_id' => $user->id,
        //                 'game_id' => $game->id,
        //                 'eisbaeren_goals' => $ebbGoals,
        //                 'opponent_goals' => $oppGoals,
        //                 'joker_type' => $jokerType,
        //             ]);

        //             if ($jokerType) {
        //                 $user->useJoker($jokerType, $bet);
        //             }

        //             $bet->updatePrices();

        //             if ($bet->final_price > 0) {
        //                 Transaction::create([
        //                     'user_id' => $user->id,
        //                     'type' => 'bet_cost',
        //                     'amount' => -$bet->final_price,
        //                     'description' => "Bet on game #{$game->game_number} ({$season->name})",
        //                     'bet_id' => $bet->id,
        //                 ]);
        //             }

        //             $betCount++;
        //         }
        //     }

        //     $totalBetsCreated += $betCount;
        //     $this->command->info("   → {$betCount} bets created");
        // }

        // /**
        //  * =========================
        //  * INITIAL DEPOSITS
        //  * =========================
        //  */
        // foreach ($users as $user) {
        //     if (!Transaction::where('user_id', $user->id)->where('type', 'deposit')->exists()) {
        //         Transaction::create([
        //             'user_id' => $user->id,
        //             'creator_id' => $admin->id,
        //             'type' => 'deposit',
        //             'amount' => 200.00,
        //             'description' => 'Initial deposit',
        //         ]);
        //     }
        // }

        // foreach ($users as $user) {
        //     $user->updateBalance();
        // }

        // $this->command->info("✅ Total games created: {$totalGamesCreated}");
        // $this->command->info("✅ Total bets created: {$totalBetsCreated}");
        $this->command->info('🎯 Development data created successfully!');
    }
}
