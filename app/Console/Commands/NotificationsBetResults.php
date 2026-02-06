<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\User;
use App\Services\NotificationDeduper;
use App\Services\PushSender;
use Illuminate\Console\Command;

class NotificationsBetResults extends Command
{
    protected $signature = 'notifications:bet-results
        {--since-minutes=240 : Look back window for finished games}
        {--dry-run : Do not send}';

    protected $description = 'Notify users when their bet has been evaluated (finished game). Uses cache deduping.';

    public function handle(PushSender $sender, NotificationDeduper $deduper): int
    {
        $sinceMinutes = (int) $this->option('since-minutes');
        $dry = (bool) $this->option('dry-run');

        $since = now()->subMinutes($sinceMinutes);

        // Bets where game is finished recently-ish
        $bets = Bet::query()
            ->whereHas('game', function ($q) use ($since) {
                $q->where('status', 'finished')
                    ->where('kickoff_at', '>=', $since);
            })
            ->with(['game', 'user'])
            ->get();

        if ($bets->isEmpty()) {
            $this->line('No bets to evaluate in window.');
            return self::SUCCESS;
        }

        $sentUsers = 0;

        foreach ($bets as $bet) {
            /** @var User $user */
            $user = $bet->user;
            $game = $bet->game;

            if (!$user || !$game) {
                continue;
            }

            $settings = $user->mergedNotificationSettings();

            if (!($settings['push_enabled'] ?? false)) {
                continue;
            }
            if (!($settings['notify_on_bet_result'] ?? true)) {
                continue;
            }

            // Deduping: once per user/bet (30 days)
            $dedupeKey = $deduper->key('bet_result', [
                'u' => $user->id,
                'b' => $bet->id,
                'g' => $game->id,
            ]);

            if (!$deduper->shouldSend($dedupeKey, 30 * 24 * 3600)) {
                continue;
            }

            $resultText = match ((float) $bet->base_price) {
                0.00 => 'Exakt ✅',
                0.30 => 'Tendenz ✅',
                0.60 => 'Winner ✅',
                1.00 => 'Falsch ❌',
                default => 'Ausgewertet',
            };

            $payload = [
                'title' => 'BLUELINER BERLIN',
                'body' => "Tipp ausgewertet: {$resultText}",
                'url' => "/bets",
                'tag' => "bet-result-{$bet->id}",
            ];

            if ($dry) {
                $this->line("DRY bet-result u#{$user->id} b#{$bet->id} g#{$game->id}");
                continue;
            }

            $res = $sender->sendToUser($user, $payload);
            if (($res['sent'] ?? 0) > 0) {
                $sentUsers++;
            }
        }

        $this->info("Bet-results done. Users notified: {$sentUsers}" . ($dry ? ' (dry-run)' : ''));
        return self::SUCCESS;
    }
}
