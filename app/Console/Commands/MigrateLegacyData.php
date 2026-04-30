<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MigrateLegacyData extends Command
{
    protected $signature = 'blts:migrate-legacy-data 
                            {dump-path? : Path to the SQL dump file}
                            {--dry-run : Run without making changes}
                            {--only= : Only migrate specific table (seasons|users|games|season_bets|bets|transactions)}';

    protected $description = 'Migrate data from old BLTS database dump to new schema';

    public function handle()
    {
        $dumpPath = $this->resolveDumpPath($this->argument('dump-path'));
        $dryRun = $this->option('dry-run');
        $only = $this->option('only');

        if (!$dumpPath || !file_exists($dumpPath)) {
            $this->error('Dump file not found at: ' . $dumpPath);
            $this->line('Tip: Place your file as "Dump.sql" in the project root or pass a custom path.');
            return 1;
        }

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('📦 Parsing SQL dump...');
        $data = $this->parseSqlDump($dumpPath);

        $this->table(
            ['Table', 'Records'],
            [
                ['Users', count($data['users'])],
                ['Seasons', count($data['season_information'])],
                ['Season Winner Bets', count($data['season_winner_tips'])],
                ['Deposits', count($data['deposits'])],
                ['Games', count($data['games'])],
                ['Tips/Bets', count($data['tips'])],
            ]
        );

        if (!$this->confirm('Continue with migration?', true)) {
            $this->info('Migration cancelled.');
            return 0;
        }

        $migrations = [
            'seasons' => fn() => $this->migrateSeasons($data['season_information'], $dryRun),
            'users' => fn() => $this->migrateUsers($data['users'], $dryRun),
            'games' => fn() => $this->migrateGames($data['games'], $data['season_information'], $data['teams'], $dryRun),
            'season_bets' => fn() => $this->migrateSeasonWinnerBets($data['season_winner_tips'], $data['users'], $data['season_information'], $data['teams'], $dryRun),
            'bets' => fn() => $this->migrateTips($data['tips'], $data['users'], $data['games'], $dryRun),
            'transactions' => fn() => $this->migrateDeposits($data['deposits'], $data['users'], $dryRun),
        ];

        if ($only) {
            if (!isset($migrations[$only])) {
                $this->error("Unknown table: $only");
                return 1;
            }
            $migrations[$only]();
        } else {
            foreach ($migrations as $migration) {
                $migration();
            }
        }

        $this->newLine();
        $this->info('✅ Migration completed!');

        return 0;
    }

    private function parseSqlDump(string $path): array
    {
        $content = file_get_contents($path);

        $data = [
            'users' => [],
            'season_information' => [],
            'season_winner_tips' => [],
            'deposits' => [],
            'games' => [],
            'tips' => [],
            'teams' => [],
        ];

        $tables = [
            'users' => '/INSERT INTO `users` VALUES (.*?);/s',
            'season_information' => '/INSERT INTO `season_information` VALUES (.*?);/s',
            'season_winner_tips' => '/INSERT INTO `season_winner_tips` VALUES (.*?);/s',
            'deposits' => '/INSERT INTO `deposits` VALUES (.*?);/s',
            'games' => '/INSERT INTO `games` VALUES (.*?);/s',
            'tips' => '/INSERT INTO `tips` VALUES (.*?);/s',
            'teams' => '/INSERT INTO `teams` VALUES (.*?);/s',
        ];

        foreach ($tables as $table => $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $data[$table] = $this->parseInsertValues($matches[1]);
            }
        }

        return $data;
    }

    private function resolveDumpPath(?string $customPath): ?string
    {
        if ($customPath) {
            return $customPath;
        }

        $defaultCandidates = [
            base_path('Dump.sql'),
            base_path('dump.sql'),
            base_path('Dump20260131.sql'),
        ];

        foreach ($defaultCandidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return $defaultCandidates[0];
    }

    private function parseInsertValues(string $values): array
    {
        $rows = [];
        $tuples = preg_split('/\),\s*\(/', $values);

        foreach ($tuples as $tuple) {
            $tuple = trim($tuple, '()');
            $fields = [];
            $current = '';
            $inQuotes = false;
            $quoteChar = null;

            for ($i = 0; $i < strlen($tuple); $i++) {
                $char = $tuple[$i];
                $prevChar = $i > 0 ? $tuple[$i - 1] : '';

                if (($char === '"' || $char === "'") && $prevChar !== '\\') {
                    if (!$inQuotes) {
                        $inQuotes = true;
                        $quoteChar = $char;
                        continue;
                    } elseif ($char === $quoteChar) {
                        $inQuotes = false;
                        $quoteChar = null;
                        continue;
                    }
                }

                if ($char === ',' && !$inQuotes) {
                    $field = trim($current);
                    $fields[] = $field === 'NULL' ? null : $field;
                    $current = '';
                    continue;
                }

                $current .= $char;
            }

            if ($current !== '') {
                $field = trim($current);
                $fields[] = $field === 'NULL' ? null : $field;
            }

            $rows[] = $fields;
        }

        return $rows;
    }

    private function migrateSeasons(array $seasonData, bool $dryRun): void
    {
        $this->info('🏒 Migrating seasons...');

        if (!$dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('seasons')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $bar = $this->output->createProgressBar(count($seasonData));
        $bar->start();

        foreach ($seasonData as $row) {
            [$id, $season, $winnerId, $createdAt, $updatedAt] = $row;

            [$startYear, $endYear] = explode('/', $season);
            $startYear = '20' . $startYear;
            $endYear = '20' . $endYear;

            $data = [
                'id' => $id,
                'name' => $season,
                'winner_team_id' => $winnerId,
                'start_date' => "$startYear-09-01",
                'end_date' => $winnerId ? "$endYear-05-01" : null,
                'is_active' => $season === '25/26',
                'phase_1_multiplier' => 1.0,
                'phase_2_multiplier' => 1.5,
                'phase_3_multiplier' => 2.0,
                'playoff_multiplier' => 3.0,
                'created_at' => $createdAt ? Carbon::parse($createdAt) : now(),
                'updated_at' => $updatedAt ? Carbon::parse($updatedAt) : now(),
            ];

            if (!$dryRun) {
                DB::table('seasons')->insert($data);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Processed " . count($seasonData) . " seasons");
    }

    private function migrateUsers(array $userData, bool $dryRun): void
    {
        $this->info('👤 Migrating users...');

        $bar = $this->output->createProgressBar(count($userData));
        $bar->start();

        $skipped = 0;

        foreach ($userData as $row) {
            [
                $id,
                $name,
                $email,
                $wantsEmailReminder,
                $mobile,
                $wantsSmsReminder,
                $password,
                $admin,
                $guest,
                $rememberToken,
                $createdAt,
                $updatedAt
            ] = $row;

            // Skip guest users completely
            if ($guest) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $data = [
                'name' => $name,
                'email' => $email,
                'email_verified_at' => null,
                'password' => $password,
                'mobile' => $mobile,
                'wants_email_reminder' => (bool) $wantsEmailReminder,
                'wants_sms_reminder' => (bool) $wantsSmsReminder,
                'is_admin' => (bool) $admin,
                'balance' => 0,
                'jokers_remaining' => 3,
                'jokers_used' => null,
                'remember_token' => $rememberToken,
                'created_at' => $createdAt ? Carbon::parse($createdAt) : now(),
                'updated_at' => $updatedAt ? Carbon::parse($updatedAt) : now(),
                'deleted_at' => null,
            ];

            if (!$dryRun) {
                DB::table('users')->updateOrInsert(['id' => $id], $data);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Processed " . (count($userData) - $skipped) . " users");
        if ($skipped > 0) {
            $this->warn("⚠ Skipped $skipped guest users");
        }
    }

    private function migrateSeasonWinnerBets(array $tipsData, array $userData, array $seasonData, array $legacyTeamsData, bool $dryRun): void
    {
        $this->info('🎯 Migrating season winner bets...');

        if (!$dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('season_winner_bets')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $bar = $this->output->createProgressBar(count($tipsData));
        $bar->start();

        $skipped = 0;

        $validUserIds = $this->getValidUserIds($userData, $dryRun);
        $seasonNameToId = $this->getSeasonNameToIdMap($seasonData, $dryRun);

        $legacyTeamsById = $this->getLegacyTeamsByIdMap($legacyTeamsData);

        foreach ($tipsData as $row) {
            [$id, $userId, $seasonName, $teamId, $createdAt, $updatedAt] = $row;

            $seasonId = $seasonNameToId[$seasonName] ?? null;

            if (!$seasonId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (!isset($validUserIds[(int) $userId])) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $resolvedTeamId = $this->resolveTeamId((int) $teamId, $legacyTeamsById);
            if (!$resolvedTeamId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $data = [
                'id' => $id,
                'user_id' => $userId,
                'season_id' => $seasonId,
                'team_id' => $resolvedTeamId,
                'created_at' => $createdAt ? Carbon::parse($createdAt) : now(),
                'updated_at' => $updatedAt ? Carbon::parse($updatedAt) : now(),
            ];

            if (!$dryRun) {
                DB::table('season_winner_bets')->insert($data);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Processed " . (count($tipsData) - $skipped) . " season winner bets");
        if ($skipped > 0) {
            $this->warn("⚠ Skipped $skipped bets (user, season, or team not found)");
        }
    }

    private function migrateDeposits(array $depositsData, array $userData, bool $dryRun): void
    {
        $this->info('💰 Migrating deposits to transactions...');

        if (!$dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('transactions')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $bar = $this->output->createProgressBar(count($depositsData));
        $bar->start();

        $skipped = 0;

        $validUserIds = $this->getValidUserIds($userData, $dryRun);

        foreach ($depositsData as $row) {
            [$id, $userId, $creatorId, $amount, $createdAt] = $row;

            if (!isset($validUserIds[(int) $userId])) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Check if creator exists, otherwise set to null
            $creatorExists = isset($validUserIds[(int) $creatorId]);

            if (!$creatorExists) {
                $creatorId = null; // Set to null if creator doesn't exist
            }

            $data = [
                'user_id' => $userId,
                'creator_id' => $creatorId,
                'type' => 'deposit',
                'amount' => $amount,
                'description' => 'Migrated from legacy deposits',
                'bet_id' => null,
                'created_at' => $createdAt ? Carbon::parse($createdAt) : now(),
                'updated_at' => $createdAt ? Carbon::parse($createdAt) : now(),
            ];

            if (!$dryRun) {
                DB::table('transactions')->insert($data);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($skipped > 0) {
            $this->warn("⚠ Skipped $skipped deposits (user not found)");
        }

        if (!$dryRun) {
            $this->info('📊 Calculating user balances...');

            $balances = DB::table('transactions')
                ->select('user_id', DB::raw('SUM(amount) as total'))
                ->groupBy('user_id')
                ->get();

            foreach ($balances as $balance) {
                DB::table('users')
                    ->where('id', $balance->user_id)
                    ->update(['balance' => $balance->total]);
            }

            $this->info("✓ Updated balances for " . $balances->count() . " users");
        }

        $this->info("✓ Processed " . (count($depositsData) - $skipped) . " deposits");
    }

    /**
     * Migrate games
     */
    private function migrateGames(array $gamesData, array $seasonData, array $legacyTeamsData, bool $dryRun): void
    {
        $this->info('🏒 Migrating games...');

        if (!$dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('games')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $bar = $this->output->createProgressBar(count($gamesData));
        $bar->start();

        $skipped = 0;
        $seasonMapping = $this->buildSeasonMapping($seasonData, $dryRun);

        $legacyTeamsById = $this->getLegacyTeamsByIdMap($legacyTeamsData);

        foreach ($gamesData as $row) {
            // OLD: id, game_number, home_team_id, away_team_id, game_date, home_goals, away_goals, tip_mail_reminder_send, tip_sms_reminder_send
            [$id, $gameNumber, $homeTeamId, $awayTeamId, $gameDate, $homeGoals, $awayGoals, $emailReminder, $smsReminder] = $row;

            // Skip if essential data is missing
            if (!$gameDate) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Determine which team is Eisbären Berlin (ID 4)
            $isHome = $homeTeamId == 4;
            $legacyOpponentId = $isHome ? (int) $awayTeamId : (int) $homeTeamId;
            $opponentId = $this->resolveTeamId($legacyOpponentId, $legacyTeamsById);
            if (!$opponentId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Determine season based on game date
            $seasonId = $this->getSeasonIdForDate($gameDate, $seasonMapping);
            if (!$seasonId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Calculate Eisbären vs Opponent goals
            $eisbaerenGoals = $isHome ? $homeGoals : $awayGoals;
            $opponentGoalsValue = $isHome ? $awayGoals : $homeGoals;

            // Determine status
            $status = 'scheduled';
            if ($eisbaerenGoals !== null && $opponentGoalsValue !== null) {
                $status = 'finished';
            } elseif (Carbon::parse($gameDate)->isPast()) {
                $status = 'finished'; // Assume finished if date passed
            }

            $data = [
                'id' => $id,
                'game_number' => $gameNumber,
                'opponent_id' => $opponentId,
                'season_id' => $seasonId,
                'is_home' => $isHome,
                'kickoff_at' => Carbon::parse($gameDate),
                'eisbaeren_goals' => $eisbaerenGoals,
                'opponent_goals' => $opponentGoalsValue,
                'status' => $status,
                'is_derby' => in_array($opponentId, [3, 7]), // DEG, KEC are derbies
                'is_playoff' => false, // Can't determine from old data
                'difficulty_rating' => 3,
                'email_reminder_sent' => (bool) $emailReminder,
                'sms_reminder_sent' => (bool) $smsReminder,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (!$dryRun) {
                DB::table('games')->insert($data);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Processed " . (count($gamesData) - $skipped) . " games");
        if ($skipped > 0) {
            $this->warn("⚠ Skipped $skipped games (missing data or team not found)");
        }
    }

    /**
     * Migrate tips to bets
     */
    private function migrateTips(array $tipsData, array $userData, array $gamesData, bool $dryRun): void
    {
        $this->info('🎯 Migrating tips to bets...');

        if (!$dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('bets')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $bar = $this->output->createProgressBar(count($tipsData));
        $bar->start();

        $skipped = 0;

        $validUserIds = $this->getValidUserIds($userData, $dryRun);
        $gameMap = $this->getGameMap($gamesData, $dryRun);

        foreach ($tipsData as $row) {
            // OLD: id, game_id, user_id, home_goals, away_goals
            [$id, $gameId, $userId, $homeGoals, $awayGoals] = $row;

            // Skip if game_id is null
            if (!$gameId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Check if user exists
            if (!isset($validUserIds[(int) $userId])) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Check if game exists
            $game = $gameMap[(int) $gameId] ?? null;
            if (!$game) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Convert home/away to eisbaeren/opponent based on game's is_home flag
            $eisbaerenGoals = $game->is_home ? $homeGoals : $awayGoals;
            $opponentGoals = $game->is_home ? $awayGoals : $homeGoals;

            $data = [
                'user_id' => $userId,
                'game_id' => $gameId,
                'eisbaeren_goals' => $eisbaerenGoals,
                'opponent_goals' => $opponentGoals,
                'joker_type' => null,
                'joker_data' => null,
                'base_price' => 0.50, // Default legacy price
                'multiplier' => 1.0,
                'final_price' => 0.50,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (!$dryRun) {
                try {
                    DB::table('bets')->insert($data);
                } catch (\Exception $e) {
                    // Skip duplicates or constraint violations
                    $skipped++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Processed " . (count($tipsData) - $skipped) . " bets");
        if ($skipped > 0) {
            $this->warn("⚠ Skipped $skipped bets (user, game not found, or duplicate)");
        }
    }

    /**
     * Build season mapping for date lookups
     */
    private function buildSeasonMapping(array $seasonData, bool $dryRun): array
    {
        if ($dryRun) {
            $mapping = [];
            foreach ($seasonData as $row) {
                [$id, $seasonName, $winnerId] = $row;
                [$startYear, $endYear] = explode('/', $seasonName);
                $start = Carbon::parse('20' . $startYear . '-09-01');
                $end = $winnerId ? Carbon::parse('20' . $endYear . '-05-01') : $start->copy()->addMonths(9);
                $mapping[] = ['id' => (int) $id, 'name' => $seasonName, 'start' => $start, 'end' => $end];
            }
            return $mapping;
        }

        $seasons = DB::table('seasons')->select('id', 'name', 'start_date', 'end_date')->get();

        $mapping = [];
        foreach ($seasons as $season) {
            $mapping[] = [
                'id' => $season->id,
                'name' => $season->name,
                'start' => Carbon::parse($season->start_date),
                'end' => $season->end_date ? Carbon::parse($season->end_date) : Carbon::parse($season->start_date)->addMonths(9),
            ];
        }

        return $mapping;
    }

    /**
     * Get season ID for a given game date
     */
    private function getSeasonIdForDate(string $gameDate, array $seasonMapping): ?int
    {
        $date = Carbon::parse($gameDate);

        foreach ($seasonMapping as $season) {
            if ($date->between($season['start'], $season['end'])) {
                return $season['id'];
            }
        }

        return null;
    }

    private function getValidUserIds(array $userData, bool $dryRun): array
    {
        if (!$dryRun) {
            return DB::table('users')->pluck('id')->mapWithKeys(fn($id) => [(int) $id => true])->all();
        }

        $ids = [];
        foreach ($userData as $row) {
            $id = (int) $row[0];
            $guest = (bool) ($row[8] ?? false);
            if (!$guest) {
                $ids[$id] = true;
            }
        }
        return $ids;
    }

    private function getSeasonNameToIdMap(array $seasonData, bool $dryRun): array
    {
        if (!$dryRun) {
            return DB::table('seasons')->pluck('id', 'name')->map(fn($id) => (int) $id)->all();
        }

        $map = [];
        foreach ($seasonData as $row) {
            $map[$row[1]] = (int) $row[0];
        }
        return $map;
    }

    private function getGameMap(array $gamesData, bool $dryRun): array
    {
        if (!$dryRun) {
            return DB::table('games')->select('id', 'is_home')->get()->mapWithKeys(fn($game) => [(int) $game->id => $game])->all();
        }

        $map = [];
        foreach ($gamesData as $row) {
            [$id, $gameNumber, $homeTeamId, $awayTeamId] = $row;
            $map[(int) $id] = (object) ['id' => (int) $id, 'is_home' => ((int) $homeTeamId === 4)];
        }
        return $map;
    }

    private function getLegacyTeamsByIdMap(array $legacyTeamsData): array
    {
        $map = [];
        foreach ($legacyTeamsData as $row) {
            $legacyId = (int) ($row[0] ?? 0);
            $name = trim((string) ($row[1] ?? ''));
            $shortName = trim((string) ($row[2] ?? ''));
            if ($legacyId > 0) {
                $map[$legacyId] = ['name' => $name, 'short_name' => $shortName];
            }
        }
        return $map;
    }

    private function resolveTeamId(int $legacyTeamId, array $legacyTeamsById): ?int
    {
        if ($legacyTeamId <= 0) {
            return null;
        }

        $directIdExists = DB::table('teams')->where('id', $legacyTeamId)->exists();
        if ($directIdExists) {
            return $legacyTeamId;
        }

        $legacyTeam = $legacyTeamsById[$legacyTeamId] ?? null;
        if (!$legacyTeam) {
            return null;
        }

        $name = $legacyTeam['name'];
        $shortName = $legacyTeam['short_name'];

        return DB::table('teams')
            ->where(function ($query) use ($name, $shortName) {
                if ($name !== '') {
                    $query->orWhere('name', $name);
                }
                if ($shortName !== '') {
                    $query->orWhere('short_name', $shortName);
                }
            })
            ->value('id');
    }
}
