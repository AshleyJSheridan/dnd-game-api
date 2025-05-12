<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\DiceRoll;
use App\Models\User;
use App\Services\NameGeneratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CharactersControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_characters_belonging_to_authenticated_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Character::factory()->create(['user_id' => $user->id, 'name' => 'Alice']);
        Character::factory()->create(['user_id' => $user->id, 'name' => 'Bob']);
        Character::factory()->create(['user_id' => $otherUser->id, 'name' => 'Eve']);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/characters');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(2, $data);
        $this->assertTrue(collect($data)->pluck('name')->contains('Alice'));
        $this->assertTrue(collect($data)->pluck('name')->contains('Bob'));
        $this->assertFalse(collect($data)->pluck('name')->contains('Eve'));
    }

    public function test_it_returns_empty_array_if_user_has_no_characters()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/characters');

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    public function test_it_returns_unauthorized_without_token()
    {
        $response = $this->getJson('/api/characters');

        $response->assertStatus(401); // Default for missing/invalid token
    }

    public function test_it_returns_default_generic_names()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Mock the service
        App::bind(NameGeneratorService::class, function () {
            return new class {
                public function generateName() {
                    return 'GenericName';
                }
            };
        });

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/names');

        $response->assertStatus(200);
        $this->assertEquals('generic', $response->json('style'));
        $this->assertEquals(array_fill(0, 6, 'GenericName'), $response->json('names'));
    }

    #[runInSeparateProcess]
    #[preserveGlobalState(false)]
    public function test_it_returns_names_for_specified_style()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Mock the service
        App::bind(NameGeneratorService::class, function ($app, $params) {
            return new class($params['nameType']) {
                public string $type;
                public function __construct($type) { $this->type = $type; }
                public function generateName() {
                    return $this->type . 'Name';
                }
            };
        });

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/names/elf');

        $response->assertStatus(200);
        $this->assertEquals('elf', $response->json('style'));
        $this->assertEquals(array_fill(0, 6, 'elfName'), $response->json('names'));
    }

    public function test_it_creates_a_character_for_the_authenticated_user()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $payload = [
            'charName' => 'Thalgrim',
            'charLevel' => 3,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/characters', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Thalgrim',
                'level' => 3,
            ]);

        $this->assertDatabaseHas('characters', [
            'name' => 'Thalgrim',
            'level' => 3,
        ]);
    }

    public function test_it_returns_400_for_missing_fields()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/characters', [
                'charName' => 'NoLevel',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Bad Request',
            ]);
    }

    public function test_it_updates_character_class_and_path()
    {
        $user = User::factory()->create();
        $character = Character::factory()->create([
            'user_id' => $user->id,
            'class_id' => 0,
            'selected_path' => 0,
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'updateType' => 'class',
            'charClassId' => 1,
            'classPathId' => 2,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/characters/{$character->guid}", $payload);

        $response->assertStatus(200);
        $this->assertEquals($character->name, $response->json('name'));
        $character->refresh();
        $this->assertEquals(1, $character->class_id);
        $this->assertEquals(2, $character->selected_path);
    }

    public function test_it_updates_alignment()
    {
        $user = User::factory()->create();
        $character = Character::factory()->create([
            'user_id' => $user->id,
            'alignment' => 0,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/characters/{$character->guid}", [
                'updateType' => 'alignment',
                'alignment' => 5,
            ]);

        $response->assertStatus(200);
        $character->refresh();
        $this->assertEquals(5, $character->alignment);
    }

    public function test_it_updates_background()
    {
        $user = User::factory()->create();
        $character = Character::factory()->create([
            'user_id' => $user->id,
            'background_id' => 0,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/characters/{$character->guid}", [
                'updateType' => 'background',
                'charBackgroundId' => 2,
                'characteristics' => [1, 2],
            ]);

        $response->assertStatus(200);
        $character->refresh();
        $this->assertEquals(2, $character->background_id);

        $characteristicsResponse = $response->json('charBackground')['characteristics'];
        $this->assertEquals(1, $characteristicsResponse[0]['id']);
        $this->assertEquals(2, $characteristicsResponse[1]['id']);
    }

    public function test_it_updates_race()
    {
        $user = User::factory()->create();
        $character = Character::factory()->create([
            'user_id' => $user->id,
            'race_id' => 0,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/characters/{$character->guid}", [
                'updateType' => 'race',
                'charRaceId' => 4,
            ]);

        $response->assertStatus(200);
        $character->refresh();
        $this->assertEquals(4, $character->race_id);
    }

    public function test_it_updates_skills()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'guid' => 'test-guid',
            'user_id' => $user->id,
            'class_id' => 3,
            'level' => 1,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/characters/{$character->guid}", [
                'updateType' => 'skills',
                'skills' => [5, 6],
            ]);

        $response->assertStatus(200);
        $this->assertEquals(3, $character->class_id);
        $charSkills = $response->json('skills')['known'];
        $this->assertEquals(5, $charSkills[0]['id']);
        $this->assertEquals(6, $charSkills[1]['id']);
    }

    public function test_it_updates_abilities()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'guid' => 'test-guid',
            'user_id' => $user->id,
            'level' => 1,
        ]);
        $diceRoll1 = DiceRoll::create(['guid' => 'roll-guid-1', 'roll_data' => '{"d6": [2,6,2,6]}']);
        $diceRoll2 = DiceRoll::create(['guid' => 'roll-guid-2', 'roll_data' => '{"d6": [1,5,5,1]}']);
        $diceRoll3 = DiceRoll::create(['guid' => 'roll-guid-3', 'roll_data' => '{"d6": [3,6,1,1]}']);
        $diceRoll4 = DiceRoll::create(['guid' => 'roll-guid-4', 'roll_data' => '{"d6": [4,4,1,2]}']);
        $diceRoll5 = DiceRoll::create(['guid' => 'roll-guid-5', 'roll_data' => '{"d6": [1,5,2,1]}']);
        $diceRoll6 = DiceRoll::create(['guid' => 'roll-guid-6', 'roll_data' => '{"d6": [4,1,6,4]}']);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/characters/{$character->guid}", [
                'updateType' => 'abilities',
                'abilityRolls' => [
                    ['abilityId' => 1, 'guid' => $diceRoll1->guid],
                    ['abilityId' => 2, 'guid' => $diceRoll2->guid],
                    ['abilityId' => 3, 'guid' => $diceRoll3->guid],
                    ['abilityId' => 4, 'guid' => $diceRoll4->guid],
                    ['abilityId' => 5, 'guid' => $diceRoll5->guid],
                    ['abilityId' => 6, 'guid' => $diceRoll6->guid],
                ],
            ]);


        $response->assertStatus(200);
        $character->refresh();
        $this->assertEquals('{"cha":14,"con":11,"dex":10,"int":10,"str":8,"wis":14}', $character->abilities);
    }
}
