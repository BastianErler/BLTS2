<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NotificationDeduper
{
    /**
     * Returns true if we should send now (i.e. key not seen yet).
     * Stores a lock with TTL seconds.
     */
    public function shouldSend(string $key, int $ttlSeconds): bool
    {
        $ttlSeconds = max(10, $ttlSeconds);

        // Cache::add only stores if not exists
        return Cache::add($this->normalizeKey($key), true, $ttlSeconds);
    }

    public function key(string $type, array $parts): string
    {
        $safe = array_map(fn($v) => is_scalar($v) ? (string) $v : json_encode($v), $parts);

        return 'push:sent:' . $type . ':' . implode(':', $safe);
    }

    private function normalizeKey(string $key): string
    {
        // avoid crazy long keys
        if (strlen($key) > 190) {
            return 'push:sent:' . Str::sha1($key);
        }

        return $key;
    }
}
