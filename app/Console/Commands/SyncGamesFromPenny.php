<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Import\DelGameSyncService;
use Illuminate\Console\Command;

class SyncGamesFromPenny extends Command
{
    protected $signature = 'games:sync';
    protected $description = 'Sync Eisbären Berlin games for the CURRENT season (Aug 1 -> Jul 31) from multiple sources.';

    public function handle(DelGameSyncService $sync): int
    {
        try {
            $result = $sync->syncCurrentSeason();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $season = $result['season'];

        $this->info("Season: {$season->name} ({$season->start_date} -> {$season->end_date})");
        $this->line("Merged drafts: {$result['total_merged']}");
        $this->info("Imported: {$result['imported']}");
        $this->line("Needs review: {$result['needs_review']}");
        $this->line("Skipped (outside season): {$result['skipped_outside_season']}");
        $this->line("Skipped (resolve failed): {$result['skipped_resolve']}");

        return self::SUCCESS;
    }
}
