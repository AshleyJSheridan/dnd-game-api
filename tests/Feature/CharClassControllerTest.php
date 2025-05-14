<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\CharClass;
class CharClassControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_all_character_classes_for_authenticated_user()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        CharClass::factory()->create(['name' => 'Foo']);
        CharClass::factory()->create(['name' => 'Bar']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/characters/classes');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Foo']);
        $response->assertJsonFragment(['name' => 'Bar']);
    }

    public function test_it_returns_a_specific_character_class_by_name()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        CharClass::factory()->create(['name' => 'Foo']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/characters/classes/foo');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Foo']);
    }

    public function test_it_returns_404_if_character_class_not_found()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/characters/classes/bar');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Character class not found',
            ]);
    }
}
