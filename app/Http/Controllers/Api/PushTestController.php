<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushTestController extends Controller
{
    public function send(Request $request, WebPushService $webPush): JsonResponse
    {
        $user = $request->user();

        $isAdmin = false;
        if ($user) {
            if (method_exists($user, 'isAdmin')) $isAdmin = (bool) $user->isAdmin();
            elseif (isset($user->is_admin)) $isAdmin = (bool) $user->is_admin;
            elseif (isset($user->role)) $isAdmin = ($user->role === 'admin');
        }

        abort_unless($isAdmin, 403);

        $request->validate([
            'title' => ['nullable', 'string', 'max:80'],
            'body'  => ['nullable', 'string', 'max:200'],
            'url'   => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'title' => $request->input('title', 'BLUELINER BERLIN'),
            'body'  => $request->input('body', 'Test Push ✅'),
            'url'   => $request->input('url', '/profile'),
        ];

        $subs = PushSubscription::query()
            ->where('user_id', $user->id)
            ->get();

        $reports = [];
        $deleted = 0;

        foreach ($subs as $sub) {
            try {
                $r = $webPush->sendToSubscription($sub, $payload);

                // r kann leer sein, wenn flush() nichts zurückliefert (sollte nicht passieren)
                foreach ($r as $one) {
                    $one['subscription_id'] = $sub->id;
                    $reports[] = $one;

                    // expired subs direkt entfernen (410 Gone etc.)
                    if (!empty($one['expired'])) {
                        $sub->delete();
                        $deleted++;
                    }
                }
            } catch (\Throwable $e) {
                $reports[] = [
                    'subscription_id' => $sub->id,
                    'success' => false,
                    'status_code' => null,
                    'reason' => $e->getMessage(),
                    'expired' => false,
                ];
            }
        }

        $sent = count(array_filter($reports, fn($r) => ($r['success'] ?? false) === true));
        $failed = count(array_filter($reports, fn($r) => ($r['success'] ?? false) !== true));

        return response()->json([
            'subscriptions' => $subs->count(),
            'sent' => $sent,
            'failed' => $failed,
            'deleted_expired' => $deleted,
            'reports' => $reports,
        ]);
    }
}
