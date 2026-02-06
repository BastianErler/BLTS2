<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;

class PushSender
{
    public function __construct(
        private readonly WebPushService $webPush,
    ) {}

    /**
     * Send a push payload to all subscriptions of a user.
     *
     * @return array{sent:int,total:int,reports:array<int,array<string,mixed>>}
     */
    public function sendToUser(User $user, array $payload): array
    {
        $subs = PushSubscription::query()
            ->where('user_id', $user->id)
            ->get();

        $reports = [];
        $sentOk = 0;

        foreach ($subs as $sub) {
            $r = $this->webPush->sendToSubscription($sub, $payload);
            $reports = array_merge($reports, $r);

            foreach ($r as $row) {
                if (!empty($row['success'])) {
                    $sentOk++;
                    continue;
                }

                $status = $row['status_code'] ?? null;
                $expired = $row['expired'] ?? null;

                // 404/410 oder expired => subscription löschen
                if ($expired === true || in_array((int) $status, [404, 410], true)) {
                    try {
                        $sub->delete();
                    } catch (\Throwable) {
                        // ignore
                    }
                }
            }
        }

        return [
            'sent' => $sentOk,
            'total' => (int) $subs->count(),
            'reports' => $reports,
        ];
    }
}
