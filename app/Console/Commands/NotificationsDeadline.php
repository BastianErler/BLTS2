<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\User;
use App\Services\NotificationDeduper;
use App\Services\PushSender;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotificationsDeadline extends Command
{
    protected $signature = 'notifications:deadline
        {--minutes=120 : Minutes before bet deadline (deadline = kickoff - 6h)}
        {--window=120 : Window in seconds (tolerance)}
        {--dry-run : Do not send}';

    protected $description = 'Notify users before tip deadline (kickoff - 6h) if they have not placed a bet yet.';

    private const DEADLINE_HOURS_BEFORE_KICKOFF = 6;

    public function handle(PushSender $sender, NotificationDeduper $deduper): int
    {
        $minutes = (int) $this->option('minutes');
        $window = (int) $this->option('window');
        $dry = (bool) $this->option('dry-run');

        if (!in_array($minutes, [30, 60, 120], true)) {
            $this->error('minutes must be one of 30/60/120');
            return self::FAILURE;
        }

        $now = now();

        // We want bet_deadline_at in [now+minutes-window, now+minutes+window].
        // bet_deadline_at = kickoff_at - 6h  => kickoff_at = bet_deadline_at + 6h
        $kickoffFrom = $now->copy()
            ->addMinutes($minutes)
            ->subSeconds($window)
            ->addHours(self::DEADLINE_HOURS_BEFORE_KICKOFF);

        $kickoffTo = $now->copy()
            ->addMinutes($minutes)
            ->addSeconds($window)
            ->addHours(self::DEADLINE_HOURS_BEFORE_KICKOFF);

        $games = Game::query()
            ->where('status', 'scheduled')
            ->whereNotNull('kickoff_at')
            ->whereBetween('kickoff_at', [$kickoffFrom, $kickoffTo])
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
            $kickoffAt = Carbon::parse($game->kickoff_at);
            $deadlineAt = $game->betDeadline() ?? $kickoffAt->copy()->subHours(self::DEADLINE_HOURS_BEFORE_KICKOFF);

            foreach ($users as $user) {
                $settings = $user->mergedNotificationSettings();

                if (!($settings['push_enabled'] ?? false)) continue;
                if (!($settings['remind_before_deadline'] ?? true)) continue;
                if ((int) ($settings['remind_before_deadline_minutes'] ?? 120) !== $minutes) continue;

                // Only if user has NOT bet yet
                $hasBet = $user->bets()
                    ->where('game_id', $game->id)
                    ->exists();

                if ($hasBet) continue;

                $dedupeKey = $deduper->key('deadline', [
                    'u' => $user->id,
                    'g' => $game->id,
                    'm' => $minutes,
                ]);

                // TTL: until deadline + 2h (not kickoff)
                $ttl = max(3600, ($deadlineAt->timestamp + 2 * 3600) - $now->timestamp);

                if (!$deduper->shouldSend($dedupeKey, $ttl)) continue;

                $payload = [
                    'title' => 'BLUELINER BERLIN',
                    'body' => "Noch {$minutes} Min bis Tipp-Sperre – du hast noch keinen Tipp abgegeben.",
                    'url' => "/games/{$game->id}",
                    'tag' => "deadline-{$game->id}-{$minutes}",
                ];

                if ($dry) {
                    $this->line("DRY deadline u#{$user->id} g#{$game->id} ({$minutes}m) deadline={$deadlineAt->toIso8601String()}");
                    continue;
                }

                $res = $sender->sendToUser($user, $payload);
                if (($res['sent'] ?? 0) > 0) $sentUsers++;
            }
        }

        $this->info("Deadline done. Users notified: {$sentUsers}" . ($dry ? ' (dry-run)' : ''));
        return self::SUCCESS;
    }
}
