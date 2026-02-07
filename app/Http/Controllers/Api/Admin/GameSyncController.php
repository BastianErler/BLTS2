<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class GameSyncController
{
    private const CACHE_KEY = 'admin:games_sync_status';

    public function status(): JsonResponse
    {
        $status = Cache::get(self::CACHE_KEY, [
            'last_synced_at' => null,
            'last_run_ok' => null,
            'last_source' => null,
            'last_duration_ms' => null,
            'last_output' => null,
        ]);

        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $seasonId = (int)($request->input('season_id', 1));

        $startedAt = microtime(true);

        try {
            Artisan::call('games:sync');

            $output = Artisan::output();
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $status = [
                'last_synced_at' => now()->toISOString(),
                'last_run_ok' => true,
                'last_source' => 'manual',
                'last_duration_ms' => $durationMs,
                'last_output' => $output,
            ];

            Cache::put(self::CACHE_KEY, $status, now()->addDays(30));

            return response()->json([
                'success' => true,
                'output' => $output,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $status = [
                'last_synced_at' => now()->toISOString(),
                'last_run_ok' => false,
                'last_source' => 'manual',
                'last_duration_ms' => $durationMs,
                'last_output' => $e->getMessage(),
            ];

            Cache::put(self::CACHE_KEY, $status, now()->addDays(30));

            return response()->json([
                'success' => false,
                'message' => 'Sync failed',
                'status' => $status,
            ], 500);
        }
    }
}
