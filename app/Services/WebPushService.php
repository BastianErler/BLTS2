<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\VAPID;

class WebPushService
{
    private WebPush $webPush;

    public function __construct()
    {
        $publicKey = config('services.webpush.vapid.public_key');
        $privateKey = config('services.webpush.vapid.private_key');
        $subject = config('services.webpush.vapid.subject');

        if (!$publicKey || !$privateKey || !$subject) {
            throw new \RuntimeException('WebPush VAPID config missing (public/private/subject).');
        }

        VAPID::validate([
            'subject' => $subject,
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ]);

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $this->webPush->setReuseVAPIDHeaders(true);
    }

    public function sendToSubscription(PushSubscription $sub, array $payload): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // iOS/Safari will praktisch immer aes128gcm (Apple Web Push erst recht).
        // Wenn du in der DB "aesgcm" gespeichert hast, kann das der Killer sein.
        $encoding = $sub->content_encoding ?: 'aes128gcm';
        if ($sub->device === 'ios' || str_contains($sub->endpoint, 'web.push.apple.com')) {
            $encoding = 'aes128gcm';
        }

        $subscription = Subscription::create([
            'endpoint' => $sub->endpoint,
            'publicKey' => $sub->p256dh,
            'authToken' => $sub->auth,
            'contentEncoding' => $encoding,
        ]);

        $report = $this->webPush->sendOneNotification($subscription, $json);

        // sendOneNotification() => MessageSentReport (und NICHT erst flush()).
        if (!$report instanceof MessageSentReport) {
            return [[
                'endpoint' => $this->shortEndpoint($sub->endpoint),
                'success' => false,
                'status_code' => null,
                'reason' => 'sendOneNotification did not return MessageSentReport',
                'expired' => null,
            ]];
        }

        return [[
            'endpoint' => $this->shortEndpoint($report->getRequest()->getUri()->__toString()),
            'success' => $report->isSuccess(),
            'status_code' => $report->getResponse() ? $report->getResponse()->getStatusCode() : null,
            'reason' => $report->getReason(),
            'expired' => $report->isSubscriptionExpired(),
        ]];
    }

    private function shortEndpoint(string $endpoint): string
    {
        return (strlen($endpoint) > 80)
            ? (substr($endpoint, 0, 40) . '…' . substr($endpoint, -30))
            : $endpoint;
    }
}
