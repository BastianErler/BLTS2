<?php

namespace App\Console\Commands;

use App\Models\Season;
use App\Models\User;
use App\Services\LeaderboardService;
use App\Services\NotificationDeduper;
use App\Services\PushSender;
use Illuminate\Console\Command;

class NotificationsRankChange extends Command
{
    protected $signature = 'notifications:rank-change
        {--season-id= : Override season (optional)}
        {--dry-run : Do not send}
        {--snapshot-after : Update rank_last_position after processing (default on)}';

    protected $description = 'Notify users if rank changed beyond threshold (uses rank_last_position snapshot in user settings).';

    public function handle(
        PushSender $sender,
        LeaderboardService $lb,
        NotificationDeduper $deduper
    ): int {
        $dry = (bool) $this->option('dry-run');
        $seasonId = $this->option('season-id') !== null ? (int) $this->option('season-id') : null;
        $snapshotAfter = (bool) $this->option('snapshot-after');

        $season = $seasonId
            ? Season::query()->find($seasonId)
            : Season::current();

        if (!$season) {
            $this->error('No active season');
            return self::FAILURE;
        }

        $users = User::query()
            ->where('is_admin', false)
            ->get();

        $sentUsers = 0;

        foreach ($users as $user) {
            $settings = $user->mergedNotificationSettings();

            if (!($settings['push_enabled'] ?? false)) {
                continue;
            }
            if (!($settings['notify_on_rank_change'] ?? false)) {
                continue;
            }

            $threshold = (int) ($settings['rank_change_threshold'] ?? 3);
            $last = $settings['rank_last_position'] ?? null;

            // kein snapshot => still
            if ($last === null) {
                continue;
            }

            $current = $lb->rankForUser($user, $season);
            if ($current === null) {
                continue;
            }

            $last = (int) $last;
            $current = (int) $current;

            $delta = $last - $current; // + improved, - worse
            if (abs($delta) < $threshold) {
                if ($snapshotAfter && !$dry) {
                    // optional: keep snapshot fresh even when no notify
                    $settings['rank_last_position'] = $current;
                    $user->notification_settings = $settings;
                    $user->save();
                }
                continue;
            }

            // Deduping: once per user/season/current-last (24h)
            $dedupeKey = $deduper->key('rank_change', [
                's' => $season->id,
                'u' => $user->id,
                'c' => $current,
                'l' => $last,
            ]);

            if (!$deduper->shouldSend($dedupeKey, 24 * 3600)) {
                continue;
            }

            $dir = $delta > 0 ? 'hoch' : 'runter';
            $payload = [
                'title' => 'BLUELINER BERLIN',
                'body' => "Rang geändert: {$dir} ({$last} → {$current})",
                'url' => "/leaderboard",
                'tag' => "rank-change-{$season->id}-{$user->id}",
            ];

            if ($dry) {
                $this->line("DRY rank-change u#{$user->id}: {$last} -> {$current}");
            } else {
                $res = $sender->sendToUser($user, $payload);
                if (($res['sent'] ?? 0) > 0) {
                    $sentUsers++;
                }
            }

            if ($snapshotAfter && !$dry) {
                $settings['rank_last_position'] = $current;
                $user->notification_settings = $settings;
                $user->save();
            }
        }

        $this->info("Rank-change done. Users notified: {$sentUsers}" . ($dry ? ' (dry-run)' : ''));
        return self::SUCCESS;
    }
}
