<?php

namespace Tests\Unit;

use App\Services\NotificationDeduper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationDeduperTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_send_true_once_then_false_until_ttl_expires(): void
    {
        Cache::flush();

        /** @var NotificationDeduper $deduper */
        $deduper = $this->app->make(NotificationDeduper::class);

        $key = $deduper->key('test', ['u' => 1, 'g' => 2, 'm' => 120]);

        $first = $deduper->shouldSend($key, 60);
        $second = $deduper->shouldSend($key, 60);

        $this->assertTrue($first);
        $this->assertFalse($second);
    }

    public function test_key_is_stable_for_same_input(): void
    {
        /** @var NotificationDeduper $deduper */
        $deduper = $this->app->make(NotificationDeduper::class);

        $a = $deduper->key('deadline', ['u' => 5, 'g' => 10, 'm' => 120]);
        $b = $deduper->key('deadline', ['u' => 5, 'g' => 10, 'm' => 120]);

        $this->assertSame($a, $b);
    }
}
