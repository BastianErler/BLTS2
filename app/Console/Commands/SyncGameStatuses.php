<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;

class SyncGameStatuses extends Command
{
    protected $signature = 'games:sync-status {--dry-run : Show changes without persisting}';

    protected $description = 'Synchronize game status from kickoff time and scores (scheduled/live/finished).';

    public function handle(): int
    {
        $now = now();
        $dryRun = (bool) $this->option('dry-run');

        $games = Game::query()->whereIn('status', ['scheduled', 'live', 'finished'])->get();

        $changed = 0;

        foreach ($games as $game) {
            $newStatus = $this->deriveStatus($game, $now);
            if ($newStatus === $game->status) {
                continue;
            }

            $changed++;
            $this->line("g#{$game->id}: {$game->status} -> {$newStatus}");

            if (!$dryRun) {
                $game->forceFill(['status' => $newStatus])->save();
            }
        }

        $mode = $dryRun ? 'DRY-RUN' : 'APPLIED';
        $this->info("{$mode}: {$changed} Status-Änderung(en).");

        return self::SUCCESS;
    }

    private function deriveStatus(Game $game, $now): string
    {
        $hasScore = $game->eisbaeren_goals !== null && $game->opponent_goals !== null;

        if ($hasScore) {
            return 'finished';
        }

        if (!$game->kickoff_at) {
            return 'scheduled';
        }

        return $game->kickoff_at->lte($now) ? 'live' : 'scheduled';
    }
}
