<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationSettingsRequest;
use Illuminate\Http\JsonResponse;

class NotificationSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $user = request()->user();

        return response()->json($user->mergedNotificationSettings());
    }

    public function update(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $user = $request->user();

        $current = $user->mergedNotificationSettings();
        $patch = $request->validated();

        // Patch merges into current
        $merged = array_replace($current, $patch);

        // enforce version always
        $merged['v'] = (int) ($merged['v'] ?? 1);

        $user->notification_settings = $merged;
        $user->save();

        return response()->json($merged);
    }
}
