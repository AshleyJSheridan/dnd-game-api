<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\CharBackground;

class CharBackgroundControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_all_character_backgrounds()
    {
        $user = User::factory()->create();

        $bg1 = CharBackground::factory()->create(['name' => 'Foo']);
        $bg2 = CharBackground::factory()->create(['name' => 'Bar']);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/characters/backgrounds');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Foo']);
        $response->assertJsonFragment(['name' => 'Bar']);
    }
}
