<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeploymentHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_checks_database_and_is_not_cacheable(): void
    {
        $this->getJson('/api/deployment-health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_health_endpoint_does_not_expose_database_errors(): void
    {
        DB::shouldReceive('select')->once()->with('SELECT 1')->andThrow(new \RuntimeException('private database error'));
        $this->getJson('/api/deployment-health')
            ->assertStatus(503)
            ->assertExactJson(['status' => 'unavailable'])
            ->assertDontSee('private database error');
    }
}
