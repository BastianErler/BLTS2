<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:2048', function ($attribute, $value, $fail) {
                $url = parse_url($value);
                $host = strtolower($url['host'] ?? '');
                $allowed = ['fcm.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com'];
                $apple = str_ends_with($host, '.push.apple.com');
                if (($url['scheme'] ?? '') !== 'https' || isset($url['port']) || isset($url['user']) || (! in_array($host, $allowed, true) && ! $apple)) {
                    $fail('Unsupported push endpoint.');
                }
            }],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'in:aes128gcm,aesgcm'],
            'device' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            PushSubscription::where('endpoint', $request->endpoint)->where('user_id', '!=', $user->id)->delete();
            PushSubscription::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'endpoint' => $request->endpoint,
                ],
                [
                    'p256dh' => $request->input('keys.p256dh'),
                    'auth' => $request->input('keys.auth'),
                    'content_encoding' => $request->input('contentEncoding', 'aes128gcm'),
                    'user_agent' => substr($request->userAgent() ?? '', 0, 255),
                    'device' => $request->input('device'),
                ]
            );
        });

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:2048']]);
        PushSubscription::where('user_id', $request->user()->id)->where('endpoint', $data['endpoint'])->delete();

        return response()->json(['ok' => true]);
    }
}
