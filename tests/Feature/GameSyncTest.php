<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class GameSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_command_is_not_reported_as_success(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Artisan::shouldReceive('call')->once()->with('games:sync')->andReturn(1);
        Artisan::shouldReceive('output')->once()->andReturn('Import failed');

        $this->actingAs($admin)->postJson('/api/admin/games/sync')
            ->assertStatus(500)->assertJsonPath('success', false);
    }

    public function test_app_config_requires_authentication(): void
    {
        $this->getJson('/api/app-config')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/api/app-config')->assertOk();
    }
}
