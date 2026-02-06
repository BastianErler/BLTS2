<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class NotificationsDispatch extends Command
{
    protected $signature = 'notifications:dispatch
        {--dry-run : Do not send}
        {--deadline-window=120 : Window seconds for deadline checks}
        {--game-start-window=180 : Window seconds for game start checks}
        {--results-since-minutes=240 : Lookback for bet results}
        {--season-id= : Pass through to rank-change (optional)}';

    protected $description = 'Runs all notification dispatch commands (deadline 120/60/30, game-start, bet-results, rank-change).';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $deadlineWindow = (int) $this->option('deadline-window');
        $gameStartWindow = (int) $this->option('game-start-window');
        $resultsSinceMinutes = (int) $this->option('results-since-minutes');
        $seasonId = $this->option('season-id');

        $this->line('Dispatching notifications…' . ($dry ? ' (dry-run)' : ''));

        // Deadlines: 120 / 60 / 30
        foreach ([120, 60, 30] as $m) {
            $args = [
                '--minutes' => $m,
                '--window' => $deadlineWindow,
            ];
            if ($dry) $args['--dry-run'] = true;

            Artisan::call('notifications:deadline', $args);
            $this->output->write(Artisan::output());
        }

        // Game start
        $args = ['--window' => $gameStartWindow];
        if ($dry) $args['--dry-run'] = true;

        Artisan::call('notifications:game-start', $args);
        $this->output->write(Artisan::output());

        // Bet results
        $args = ['--since-minutes' => $resultsSinceMinutes];
        if ($dry) $args['--dry-run'] = true;

        Artisan::call('notifications:bet-results', $args);
        $this->output->write(Artisan::output());

        // Rank change
        $args = [];
        if ($dry) $args['--dry-run'] = true;
        if (!empty($seasonId)) $args['--season-id'] = $seasonId;
        $args['--snapshot-after'] = true;

        Artisan::call('notifications:rank-change', $args);
        $this->output->write(Artisan::output());

        $this->info('Dispatch done.');
        return self::SUCCESS;
    }
}
