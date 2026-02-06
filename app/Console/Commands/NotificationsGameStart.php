<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\User;
use App\Services\NotificationDeduper;
use App\Services\PushSender;
use Illuminate\Console\Command;

class NotificationsGameStart extends Command
{
    protected $signature = 'notifications:game-start
        {--window=180 : Window in seconds}
        {--dry-run : Do not send}';

    protected $description = 'Notify users at game start if they have not placed a bet yet (and setting enabled).';

    public function handle(PushSender $sender, NotificationDeduper $deduper): int
    {
        $window = (int) $this->option('window');
        $dry = (bool) $this->option('dry-run');

        $now = now();
        $from = $now->copy()->subSeconds($window);
        $to = $now->copy()->addSeconds($window);

        $games = Game::query()
            ->where('status', 'scheduled')
            ->whereBetween('kickoff_at', [$from, $to])
            ->orderBy('kickoff_at')
            ->get();

        if ($games->isEmpty()) {
            $this->line('No games in window.');
            return self::SUCCESS;
        }

        $users = User::query()
            ->where('is_admin', false)
            ->get();

        $sentUsers = 0;

        foreach ($games as $game) {
            foreach ($users as $user) {
                $settings = $user->mergedNotificationSettings();

                if (!($settings['push_enabled'] ?? false)) {
                    continue;
                }
                if (!($settings['remind_on_game_start_if_no_bet'] ?? true)) {
                    continue;
                }

                $hasBet = $user->bets()
                    ->where('game_id', $game->id)
                    ->exists();

                if ($hasBet) {
                    continue;
                }

                // Deduping: once per user/game for 6h
                $dedupeKey = $deduper->key('game_start_no_bet', [
                    'u' => $user->id,
                    'g' => $game->id,
                ]);

                if (!$deduper->shouldSend($dedupeKey, 6 * 3600)) {
                    continue;
                }

                $payload = [
                    'title' => 'BLUELINER BERLIN',
                    'body' => 'Spiel startet – du hast noch keinen Tipp abgegeben.',
                    'url' => "/games/{$game->id}",
                    'tag' => "game-start-{$game->id}",
                ];

                if ($dry) {
                    $this->line("DRY game-start u#{$user->id} g#{$game->id}");
                    continue;
                }

                $res = $sender->sendToUser($user, $payload);
                if (($res['sent'] ?? 0) > 0) {
                    $sentUsers++;
                }
            }
        }

        $this->info("Game-start done. Users notified: {$sentUsers}" . ($dry ? ' (dry-run)' : ''));
        return self::SUCCESS;
    }
}
