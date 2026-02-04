<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AppConfigController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        // ✅ Admin-Erkennung robust halten:
        // - wenn du eine isAdmin() Methode hast -> die
        // - sonst bool column is_admin
        // - sonst optional: role === 'admin'
        $isAdmin = false;

        if ($user) {
            if (method_exists($user, 'isAdmin')) {
                $isAdmin = (bool) $user->isAdmin();
            } elseif (isset($user->is_admin)) {
                $isAdmin = (bool) $user->is_admin;
            } elseif (isset($user->role)) {
                $isAdmin = ($user->role === 'admin');
            }
        }

        // 🔧 Für Push brauchst du später VAPID public key (nur Admins sichtbar im Debug)
        // Beispiel ENV: VAPID_PUBLIC_KEY="BEl...deinKey..."
        $vapidPublicKey = env('VAPID_PUBLIC_KEY');

        return response()->json([
            'pwa' => [
                'debug' => $isAdmin,
                'push_test' => $isAdmin,

                // optional fürs Frontend Debug / Subscribe
                'vapid_public_key' => $isAdmin ? $vapidPublicKey : null,

                // optional: wir können auch eine "environment" Info geben
                'env' => $isAdmin ? app()->environment() : null,
            ],
        ]);
    }
}
