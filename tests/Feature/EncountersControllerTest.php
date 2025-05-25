<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\GameCreature;
use App\Models\GameEncounter;
use App\Models\User;
use App\Services\CreatureService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class EncountersControllerTest extends TestCase
{
    use DatabaseTransactions;

    /*protected function setUp(): void
    {
        parent::setUp();

        $mock = Mockery::mock(CreatureService::class);
        $this->app->instance(CreatureService::class, $mock);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }*/

    protected function mockCreatureServiceWithResult(array $creatures, int $partyDifficulty)
    {
        $creatures = collect($creatures)->map(function ($creature) {
            // assume max hit points without additional modifiers for the sake of the controller test
            $creature->hp = $creature->hit_points_dice * intval(substr($creature->hit_points_dice_sides, 1));

            return $creature;
        });

        $mock = Mockery::mock(CreatureService::class);
        $mock->shouldReceive('createEncounter')
            ->andReturn([
                'creatures' => collect($creatures),
                'partyDifficulty' => $partyDifficulty,
            ]);
        $this->app->instance(CreatureService::class, $mock);
    }

    public function test_it_creates_an_encounter_with_valid_input()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $character = Character::factory()->create(['user_id' => $user->id, 'level' => 3]);

        $creature = GameCreature::factory()->create();

        $this->mockCreatureServiceWithResult([$creature], 150);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/encounters', [
                'characters' => [$character->guid],
                'difficulty' => 2,
                'environment' => 'swamp',
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'type' => 'creature',
            'difficulty' => 2,
            'environment' => 'swamp',
        ]);

        $this->assertDatabaseHas('game_encounters', ['environment' => 'swamp']);
        $this->assertDatabaseHas('game_encounter_creatures', ['creature_id' => $creature->id]);
    }

    public function test_it_returns_tarasque_when_encounter_service_returns_null()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $character = Character::factory()->create(['user_id' => $user->id, 'level' => 99]);

        //GameCreature::factory()->create(['name' => 'Tarasque']);

        $mock = Mockery::mock(CreatureService::class);
        $mock->shouldReceive('createEncounter')->andReturn(null);
        $this->app->instance(CreatureService::class, $mock);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/encounters', [
                'characters' => [$character->guid],
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Tarasque',
            'amount' => 1,
            'difficulty' => 155000,
        ]);
    }

    public function test_it_returns_400_if_no_characters_are_provided()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/encounters', [
                // empty characters
                'characters' => [],
            ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'No characters specified']);
    }

    public function test_it_returns_null_if_encounter_does_not_exist()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/encounters/non-existent-guid');

        $response->assertStatus(404);
        $response->assertExactJson(['error' => 'Encounter not found']);
    }

    public function test_it_returns_an_encounter_by_guid_for_authenticated_user()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $encounter = GameEncounter::factory()->create([
            'guid' => 'test-guid-123',
            'type' => 'creature',
            'difficulty' => 3,
            'environment' => 'mountain',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/encounters/{$encounter->guid}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'guid' => 'test-guid-123',
            'type' => 'creature',
            'difficulty' => 3,
            'environment' => 'mountain',
        ]);
    }
}
