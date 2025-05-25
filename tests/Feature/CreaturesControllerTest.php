<?php

namespace Tests\Feature;

use App\Models\GameCreature;
use App\Models\User;
use App\Services\CreatureService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CreaturesControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function mockCreatureServiceWithPassthrough()
    {
        $mock = Mockery::mock(CreatureService::class);
        $mock->shouldReceive('addProcessedFields')
            ->andReturnUsing(fn(Collection $creatures) => $creatures);

        $this->app->instance(CreatureService::class, $mock);
    }

    public function test_it_returns_all_creatures()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $this->mockCreatureServiceWithPassthrough();

        GameCreature::factory()->create(['name' => 'Creature 1']);
        GameCreature::factory()->create(['name' => 'Creature 2']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/creatures');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Creature 1']);
        $response->assertJsonFragment(['name' => 'Creature 2']);
    }

    public function test_it_returns_creatures_of_a_specific_type()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $this->mockCreatureServiceWithPassthrough();

        GameCreature::factory()->create(['name' => 'Creature 1', 'type' => 'humanoid']);
        GameCreature::factory()->create(['name' => 'Creature 2', 'type' => 'dragon']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/creatures/humanoid');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Creature 1']);
        $response->assertJsonMissing(['name' => 'Creature 2']);
    }
}
