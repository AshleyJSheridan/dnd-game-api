<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\CharRace;

class CharRaceControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_all_character_races()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $foo = CharRace::factory()->create(['name' => 'Foo', 'parent_race_id' => 0]);
        $bar = CharRace::factory()->create(['name' => 'Bar', 'parent_race_id' => 0]);
        $baz = CharRace::factory()->create(['name' => 'Baz', 'parent_race_id' => $bar->id]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/characters/races');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Foo']);
        $response->assertJsonFragment(['name' => 'Bar']);
        $response->assertJsonFragment(['name' => 'Baz']);
    }
}
