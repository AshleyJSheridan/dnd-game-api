<?php

namespace Tests\Feature;

use App\Http\Factories\ItemFactory;
use App\Models\Character;
use App\Models\CharBackground;
use App\Models\CharClass;
use App\Models\GameItem;
use App\Models\ItemStarterPack;
use App\Models\User;
use App\Services\CreatureService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ItemControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function mockItemFactoryReturning(GameItem $item)
    {
        $mockFactory = Mockery::mock(ItemFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($item);

        $this->app->instance(ItemFactory::class, $mockFactory);
    }

    public function test_it_returns_all_non_generated_items()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        GameItem::factory()->create(['name' => 'Some Sword', 'type' => 'weapon', 'generated' => 'no']);
        GameItem::factory()->create(['name' => 'Some Potion', 'type' => 'potion', 'generated' => 'no']);
        GameItem::factory()->create(['name' => 'Some Shield', 'type' => 'armor', 'generated' => 'yes']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/game/items');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Some Sword']);
        $response->assertJsonFragment(['name' => 'Some Potion']);
        $response->assertJsonMissing(['name' => 'Some Shield']);
    }

    public function test_it_returns_items_filtered_by_type()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        GameItem::factory()->create(['name' => 'Some Bow', 'type' => 'weapon', 'generated' => 'no']);
        GameItem::factory()->create(['name' => 'Some Elixir', 'type' => 'potion', 'generated' => 'no']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/game/items/weapon');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Some Bow']);
        $response->assertJsonMissing(['name' => 'Some Elixir']);
    }

    public function test_it_returns_404_with_incorrect_type()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/game/items/foo');

        $response->assertStatus(404);
    }

    public function test_it_returns_a_random_item_of_specified_type()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $item = GameItem::factory()->create(['name' => 'Some Item', 'type' => 'armor']);

        $this->mockItemFactoryReturning($item);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/game/items/armor/random');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'type' => 'armor',
        ]);
    }

    public function test_it_returns_equipment_if_class_and_background_are_present()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $charClass = CharClass::factory()->create();
        $charBackground = CharBackground::factory()->create();

        $classPack = ItemStarterPack::factory()->create(['type' => 'class', 'char_class_id' => $charClass->id]);
        $backgroundPack = ItemStarterPack::factory()->create(['type' => 'background', 'char_background_id' => $charBackground->id]);

        $charClass->StartingEquipmentPacks()->save($classPack);
        $charBackground->StartingEquipmentPacks()->save($backgroundPack);

        $character = Character::factory()->create([
            'user_id' => $user->id,
            'guid' => 'abc-123',
            'class_id' => $charClass->id,
            'background_id' => $charBackground->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/characters/abc-123/startingEquipment');

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $classPack->id]);
        $response->assertJsonFragment(['id' => $backgroundPack->id]);
    }
}
