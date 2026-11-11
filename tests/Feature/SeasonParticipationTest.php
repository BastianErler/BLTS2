<?php

namespace Tests\Feature;

use App\Models\Season;
use App\Models\SeasonParticipation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonParticipationTest extends TestCase
{
    use RefreshDatabase;

    public function test_join_and_admin_approve_withdrawal(): void
    {
        $season = Season::factory()->create(['is_active' => true]);
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user)->postJson('/api/season-participation')->assertOk();
        $participation = SeasonParticipation::where('season_id', $season->id)->where('user_id', $user->id)->firstOrFail();
        $this->postJson('/api/season-participation/withdrawal')->assertOk();
        $this->actingAs($user)->getJson('/api/admin/withdrawal-requests')->assertForbidden();
        $this->actingAs($admin)->getJson('/api/admin/withdrawal-requests')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/admin/withdrawal-requests/'.$participation->id, ['approve' => true])->assertOk();
        $this->assertNotNull($participation->fresh()->withdrawn_at);
        $this->assertSame('0.00', $participation->fresh()->frozen_cost);
    }
}
