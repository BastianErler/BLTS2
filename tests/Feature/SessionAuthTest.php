<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_session_without_returning_a_token_and_logout_ends_it(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);
        $headers = ['Origin' => 'http://localhost:5173', 'Referer' => 'http://localhost:5173/'];
        $this->withHeaders($headers)->postJson('/api/login', [
            'email' => $user->email, 'password' => 'correct-password',
        ])->assertOk()->assertJsonMissingPath('token');
        $this->withHeaders($headers)->getJson('/api/me')->assertOk()->assertJsonPath('id', $user->id);
        $this->withHeaders($headers)->postJson('/api/logout')->assertOk();
        $this->withHeaders($headers)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_bearer_token_alone_does_not_authenticate_the_web_api(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('old-token')->plainTextToken;
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }
}
