<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_remove_own_push_subscription(): void
    {
        $user = User::factory()->create();
        $payload = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/test', 'keys' => ['p256dh' => 'key', 'auth' => 'secret']];
        $this->actingAs($user)->postJson('/api/push-subscriptions', $payload)->assertOk();
        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $user->id, 'endpoint' => $payload['endpoint']]);
        $this->deleteJson('/api/push-subscriptions', ['endpoint' => $payload['endpoint']])->assertOk();
        $this->assertDatabaseMissing('push_subscriptions', ['user_id' => $user->id, 'endpoint' => $payload['endpoint']]);
    }

    public function test_arbitrary_push_endpoints_are_rejected(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://127.0.0.1/admin', 'keys' => ['p256dh' => 'key', 'auth' => 'secret'],
        ])->assertUnprocessable();
    }

    public function test_user_cannot_remove_another_users_subscription(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test';
        $this->actingAs($owner)->postJson('/api/push-subscriptions', [
            'endpoint' => $endpoint, 'keys' => ['p256dh' => 'key', 'auth' => 'secret'],
        ])->assertOk();

        $this->actingAs($other)->deleteJson('/api/push-subscriptions', ['endpoint' => $endpoint])->assertOk();
        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $owner->id, 'endpoint' => $endpoint]);
    }

    public function test_registering_an_existing_device_transfers_it_to_the_current_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $payload = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/test', 'keys' => ['p256dh' => 'key', 'auth' => 'secret']];
        $this->actingAs($owner)->postJson('/api/push-subscriptions', $payload)->assertOk();
        $this->actingAs($other)->postJson('/api/push-subscriptions', $payload)->assertOk();
        $this->assertDatabaseMissing('push_subscriptions', ['user_id' => $owner->id, 'endpoint' => $payload['endpoint']]);
        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $other->id, 'endpoint' => $payload['endpoint']]);
    }
}
