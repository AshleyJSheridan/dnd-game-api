<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
class HeartbeatControllerTest extends TestCase
{
    public function test_it_returns_a_no_content_response_for_a_heartbeat_check()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/heartbeat');

        $response->assertStatus(204);
    }
}
