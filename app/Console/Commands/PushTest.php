<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PushSubscription;
use App\Services\WebPushService;

class PushTest extends Command
{
    protected $signature = 'push:test {--user=}';
    protected $description = 'Send test push to iOS PWA';

    public function handle(WebPushService $push)
    {
        $subs = PushSubscription::where('id', PushSubscription::max('id'))
            ->get();


        if ($subs->isEmpty()) {
            $this->error('No subscriptions found');
            return 1;
        }

        foreach ($subs as $sub) {
            $this->info("Sending to {$sub->endpoint}");

            $report = $push->sendToSubscription($sub, [
                'title' => 'CLI Push Test',
                'body'  => 'Wenn du das siehst, funktioniert iOS Push 🎉',
                'url'   => '/profile',
            ]);

            // falls dein Service Reports zurückgibt
            if ($report) {
                $this->line(json_encode($report, JSON_PRETTY_PRINT));
            }
        }

        $results = $push->sendToSubscription($sub, [
            'title' => 'CLI Push Test',
            'body'  => 'Wenn du das siehst, funktioniert iOS Push 🎉',
            'url'   => '/profile',
        ]);

        $results = $push->sendToSubscription($sub, [
            'title' => 'CLI Push Test',
            'body'  => 'kommt das an?',
            'url'   => '/profile',
        ]);

        $this->info('Reports:');
        foreach ($results as $r) {
            $this->line(json_encode($r, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }


        return 0;
    }
}
