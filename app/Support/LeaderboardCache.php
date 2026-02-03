<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class LeaderboardCache
{
    private const TAG_PREFIX = 'leaderboard';
    private const VERSION = 'v1';

    public static function rememberRanking(int $seasonId, ?int $cutoffTimestamp, int $ttlSeconds, callable $callback): array
    {
        $cutoffPart = $cutoffTimestamp ? (string) $cutoffTimestamp : 'none';
        $key = self::key($seasonId, $cutoffPart);

        // Prefer tags if supported
        if (self::supportsTags()) {
            return Cache::tags(self::tags($seasonId))->remember($key, $ttlSeconds, $callback);
        }

        // Fallback: remember without tags, but track keys so we can invalidate later
        $value = Cache::remember($key, $ttlSeconds, $callback);
        self::trackKey($seasonId, $key, $ttlSeconds);

        return $value;
    }

    public static function forgetSeason(int $seasonId): void
    {
        if (self::supportsTags()) {
            Cache::tags(self::tags($seasonId))->flush();
            return;
        }

        $indexKey = self::indexKey($seasonId);
        $keys = Cache::get($indexKey, []);

        foreach ($keys as $k) {
            Cache::forget($k);
        }

        Cache::forget($indexKey);
    }

    private static function key(int $seasonId, string $cutoffPart): string
    {
        return self::TAG_PREFIX . ':' . self::VERSION . ':season:' . $seasonId . ':cutoff:' . $cutoffPart;
    }

    private static function tags(int $seasonId): array
    {
        return [self::TAG_PREFIX, 'season:' . $seasonId];
    }

    private static function supportsTags(): bool
    {
        $store = Cache::getStore();
        return method_exists($store, 'tags');
    }

    private static function indexKey(int $seasonId): string
    {
        return self::TAG_PREFIX . ':' . self::VERSION . ':season:' . $seasonId . ':keys';
    }

    private static function trackKey(int $seasonId, string $key, int $ttlSeconds): void
    {
        $indexKey = self::indexKey($seasonId);

        $keys = Cache::get($indexKey, []);
        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
        }

        // index TTL slightly longer than the ranking TTL, so invalidation has time to find it
        Cache::put($indexKey, $keys, $ttlSeconds + 60);
    }
}
