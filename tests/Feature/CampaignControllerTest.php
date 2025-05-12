<?php
namespace Tests\Feature;

use App\Models\CampaignMap;
use App\Models\CampaignMapCharacterEntity;
use App\Models\CampaignMapCreatureEntity;
use App\Models\CampaignMapDrawingEntity;
use App\Models\Character;
use App\Models\GameCreature;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Campaign;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\UploadedFile;

class CampaignControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_campaigns_belonging_to_authenticated_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create campaigns for both users
        $campaign1 = Campaign::create(['name' => 'Campaign 1', 'user_id' => $user->id, 'guid' => Str::uuid()->toString(), 'description' => '']);
        $campaign2 = Campaign::create(['name' => 'Campaign 2', 'user_id' => $user->id, 'guid' => Str::uuid()->toString(), 'description' => '']);
        $campaign3 = Campaign::create(['name' => 'Campaign 3', 'user_id' => $otherUser->id, 'guid' => Str::uuid()->toString(), 'description' => '']);
        $campaign4 = Campaign::create(['name' => 'Campaign 4', 'user_id' => $otherUser->id, 'guid' => Str::uuid()->toString(), 'description' => '']);
        $campaign5 = Campaign::create(['name' => 'Campaign 5', 'user_id' => $otherUser->id, 'guid' => Str::uuid()->toString(), 'description' => '']);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/campaigns');

        $response->assertStatus(200);

        $campaignGuids = collect($response->json())->pluck('guid');
        $this->assertCount(2, $campaignGuids);
        $this->assertTrue($campaignGuids->contains($campaign1->guid));
        $this->assertTrue($campaignGuids->contains($campaign2->guid));
    }

    public function test_it_returns_empty_array_if_user_has_no_campaigns()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/campaigns');

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    public function test_it_creates_a_campaign_for_authenticated_user()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $payload = [
            'name' => 'My Campaign',
            'description' => 'An epic journey awaits...',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/campaigns', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'My Campaign',
                'description' => 'An epic journey awaits...',
            ]);

        $this->assertDatabaseHas('games', [
            'name' => 'My Campaign',
            'user_id' => $user->id,
            'state' => 'paused',
        ]);
    }

    public function test_it_returns_bad_request_for_missing_data()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->post('/api/campaigns', [], ['Content-Type' => 'application/json']);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Bad Request',
            ]);
    }

    public function test_it_returns_owner_resource_if_user_owns_the_campaign()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'name' => 'Owner Campaign',
            'description' => 'Owned by user',
            'guid' => 'abc123',
            'user_id' => $user->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/campaigns/{$campaign->guid}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Owner Campaign',
        ]);
    }

    public function test_it_returns_player_resource_if_user_is_not_the_owner()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $campaign = Campaign::create([
            'name' => 'Shared Campaign',
            'description' => 'Multiplayer',
            'guid' => 'shared-123',
            'user_id' => $owner->id,
        ]);

        $token = JWTAuth::fromUser($otherUser);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/campaigns/{$campaign->guid}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Shared Campaign',
        ]);
    }

    public function test_it_returns_404_if_campaign_not_found()
    {
        $user = User::factory()->create();

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/campaigns/nonexistent-guid");

        $response->assertStatus(404);
    }

    public function test_it_creates_a_campaign_map_with_image()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'test-guid',
            'name' => 'Test Campaign',
            'description' => 'A test campaign',
            'user_id' => $user->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'name' => 'Dungeon Map',
            'description' => 'A scary cave',
            'image' => UploadedFile::fake()->image('map.jpg', 300, 300),
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/campaigns/{$campaign->guid}/maps", $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'name' => 'Dungeon Map',
            'description' => 'A scary cave',
        ]);

        $data = $response->json();
        $this->assertFileExists(storage_path('images/' . $data['image']));
        $this->assertFileExists(storage_path('thumbs/' . $data['image']));
    }

    public function test_it_returns_validation_error_if_image_is_missing()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'test-guid',
            'name' => 'Test Campaign',
            'description' => 'A test campaign',
            'user_id' => $user->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'name' => 'Map with no image',
            'description' => 'Missing file',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/campaigns/{$campaign->guid}/maps", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('image');
    }

    public function test_it_returns_validation_error_if_image_is_not_valid_file()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'test-guid',
            'name' => 'Test Campaign',
            'description' => 'A test campaign',
            'user_id' => $user->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'name' => 'Map with bad file',
            'description' => 'Wrong format',
            'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/campaigns/{$campaign->guid}/maps", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('image');
    }

    public function test_it_returns_404_if_campaign_does_not_exist()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $payload = [
            'name' => 'Orphan Map',
            'description' => 'No campaign exists',
            'image' => UploadedFile::fake()->image('map.jpg'),
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/campaigns/nonexistent-guid/maps', $payload);

        $response->assertStatus(404);
    }

    public function test_it_returns_a_map_given_valid_guids()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'name' => 'Test Campaign',
            'description' => 'A test campaign',
            'guid' => 'campaign-123',
            'user_id' => $user->id,
        ]);

        $map = CampaignMap::create([
            'name' => 'Forest Map',
            'description' => 'A dense forest',
            'guid' => 'map-abc',
            'game_id' => $campaign->id,
            'image' => 'test.jpg',
            'width' => 200,
            'height' => 200,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/campaigns/{$campaign->guid}/maps/{$map->guid}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Forest Map',
                'guid' => 'map-abc',
            ]);
    }

    public function test_it_returns_404_if_map_not_found()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'name' => 'Lonely Campaign',
            'description' => 'A campaign with no maps',
            'guid' => 'lonely-123',
            'user_id' => $user->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/campaigns/{$campaign->guid}/maps/nonexistent-guid");

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Campaign map not found',
        ]);
    }

    public function test_it_returns_404_if_map_does_not_belong_to_campaign()
    {
        $user = User::factory()->create();
        $campaign1 = Campaign::create([
            'name' => 'Campaign A',
            'description' => 'A campaign with maps',
            'guid' => 'camp-a',
            'user_id' => $user->id,
        ]);

        $campaign2 = Campaign::create([
            'name' => 'Campaign B',
            'description' => 'Another campaign',
            'guid' => 'camp-b',
            'user_id' => $user->id,
        ]);

        $map = CampaignMap::create([
            'name' => 'Orphaned Map',
            'description' => 'This map belongs to another campaign',
            'guid' => 'map-orphan',
            'game_id' => $campaign2->id,
            'image' => 'orphan.jpg',
            'width' => 100,
            'height' => 100,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/campaigns/{$campaign1->guid}/maps/{$map->guid}");

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Campaign map not found',
        ]);
    }

    public function test_it_returns_the_image_file_for_a_valid_map()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'name' => 'Test Campaign',
            'description' => 'A test campaign',
            'guid' => 'camp-1',
            'user_id' => $user->id,
        ]);

        $imageName = 'test-image.jpg';
        $imagePath = storage_path('images/' . $imageName);
        file_put_contents($imagePath, UploadedFile::fake()->image($imageName)->getContent());

        $map = CampaignMap::create([
            'name' => 'Map With Image',
            'description' => 'A map with a test image',
            'guid' => 'map-guid-1',
            'game_id' => $campaign->id,
            'image' => $imageName,
            'width' => 200,
            'height' => 200,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get("/api/campaigns/maps/{$map->guid}/image");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');

        // Clean up fake image
        unlink($imagePath);
    }

    public function test_it_returns_404_for_map_image_if_map_is_not_found()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get('/api/campaigns/maps/nonexistent-guid/image');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Campaign map not found',
        ]);
    }

    public function test_it_returns_404_if_image_file_does_not_exist()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'name' => 'No Image Campaign',
            'description' => 'A campaign with a missing image',
            'guid' => 'camp-2',
            'user_id' => $user->id,
        ]);

        $map = CampaignMap::create([
            'name' => 'Missing Image Map',
            'description' => 'This map has a missing image file',
            'guid' => 'map-missing-image',
            'game_id' => $campaign->id,
            'image' => 'does-not-exist.jpg',
            'width' => 100,
            'height' => 100,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get("/api/campaigns/maps/{$map->guid}/image");

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Image file not found',
        ]);
    }

    public function test_it_returns_the_thumb_file_for_a_valid_map()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'name' => 'Test Campaign',
            'description' => 'A test campaign',
            'guid' => 'camp-1',
            'user_id' => $user->id,
        ]);

        $imageName = 'test-image.jpg';
        $imagePath = storage_path('thumbs/' . $imageName);
        file_put_contents($imagePath, UploadedFile::fake()->image($imageName)->getContent());

        $map = CampaignMap::create([
            'name' => 'Map With Image',
            'description' => 'A map with a test thumb',
            'guid' => 'map-guid-1',
            'game_id' => $campaign->id,
            'image' => $imageName,
            'width' => 200,
            'height' => 200,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get("/api/campaigns/maps/{$map->guid}/thumb");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');

        // Clean up fake image
        unlink($imagePath);
    }

    public function test_it_returns_404_for_map_thumb_if_map_is_not_found()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get('/api/campaigns/maps/nonexistent-guid/thumb');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Campaign map not found',
        ]);
    }

    public function test_it_returns_404_if_thumb_file_does_not_exist()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'name' => 'No Image Campaign',
            'description' => 'A campaign with a missing thumb',
            'guid' => 'camp-2',
            'user_id' => $user->id,
        ]);

        $map = CampaignMap::create([
            'name' => 'Missing Image Map',
            'description' => 'This map has a missing thumb file',
            'guid' => 'map-missing-image',
            'game_id' => $campaign->id,
            'image' => 'does-not-exist.jpg',
            'width' => 100,
            'height' => 100,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get("/api/campaigns/maps/{$map->guid}/thumb");

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'Image file not found',
        ]);
    }

    public function test_it_updates_allowed_fields_on_a_campaign_map()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'name' => 'Update Test',
            'description' => 'A campaign for testing updates',
            'guid' => 'camp-123',
            'user_id' => $user->id,
        ]);

        $map = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'show_grid' => true,
            'grid_size' => 40,
            'grid_colour' => '#ff0000',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/campaigns/{$campaign->guid}/maps/{$map->guid}", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'show_grid' => true,
                'grid_size' => 40,
                'grid_colour' => '#ff0000',
            ]);

        $this->assertDatabaseHas('game_maps', [
            'id' => $map->id,
            'show_grid' => true,
            'grid_size' => 40,
            'grid_colour' => '#ff0000',
        ]);
    }

    public function test_it_ignores_fields_not_in_the_allowed_list()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'name' => 'Update Test',
            'description' => 'A campaign for testing updates',
            'guid' => 'camp-123',
            'user_id' => $user->id,
        ]);

        $map = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'name' => 'New Name', // not allowed
            'grid_size' => 32,    // allowed
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/campaigns/{$campaign->guid}/maps/{$map->guid}", $payload);

        $response->assertStatus(200)
            ->assertJsonMissing(['name' => 'New Name'])
            ->assertJsonFragment(['grid_size' => 32]);

        $map->refresh();
        $this->assertEquals('Map 1', $map->name);
        $this->assertEquals(32, $map->grid_size);
    }

    public function test_it_returns_404_if_map_is_not_found()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'name' => 'Update Test',
            'description' => 'A campaign for testing updates',
            'guid' => 'camp-123',
            'user_id' => $user->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = ['grid_size' => 64];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/campaigns/{$campaign->guid}/maps/nonexistent-map", $payload);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Campaign map not found',
            ]);
    }

    public function test_it_adds_character_to_campaign_for_owner()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-guid-1',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);

        $character = Character::create([
            'guid' => 'char-guid-1',
            'name' => 'Hero',
            'user_id' => $user->id,
            'level' => 1,
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'character_guid' => $character->guid,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/campaigns/{$campaign->guid}/characters", $payload);

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Campaign 1']);
        $this->assertDatabaseHas('game_character', [
            'game_id' => $campaign->id,
            'char_id' => $character->id,
        ]);
    }

    public function test_it_adds_character_to_campaign_for_non_owner()
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();

        $campaign = Campaign::create([
            'guid' => 'camp-guid-1',
            'user_id' => $owner->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);

        $character = Character::create([
            'guid' => 'char-guid-1',
            'name' => 'Hero',
            'user_id' => $player->id,
            'level' => 1,
        ]);

        $token = JWTAuth::fromUser($player);

        $payload = ['character_guid' => $character->guid];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/campaigns/{$campaign->guid}/characters", $payload);

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Campaign 1']);
        $this->assertDatabaseHas('game_character', [
            'game_id' => $campaign->id,
            'char_id' => $character->id,
        ]);
    }

    public function test_it_returns_400_if_character_not_found()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-guid-1',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/campaigns/{$campaign->guid}/characters", [
                'character_guid' => 'non-existent-guid',
            ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Bad Request']);
    }

    public function test_it_returns_400_if_campaign_not_found()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'guid' => 'char-guid-3',
            'name' => 'Sorcerer',
            'user_id' => $user->id,
            'level' => 1,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/campaigns/fake-campaign/characters", [
                'character_guid' => $character->guid,
            ]);

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Campaign not found']);
    }

    public function test_it_returns_400_for_malformed_payload()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-guid-1',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/campaigns/{$campaign->guid}/characters", []);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Bad Request']);
    }

    public function test_it_removes_character_from_campaign_as_owner()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-guid-1',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);

        $character = Character::create([
            'guid' => 'char-guid-3',
            'name' => 'Sorcerer',
            'user_id' => $user->id,
            'level' => 1,
        ]);

        $campaign->characters()->attach($character->id);

        $this->assertDatabaseHas('game_character', [
            'game_id' => $campaign->id,
            'char_id' => $character->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/campaigns/{$campaign->guid}/characters/{$character->guid}");

        $response->assertStatus(200)
            ->assertJsonFragment(['guid' => $campaign->guid]);

        $this->assertDatabaseMissing('game_character', [
            'game_id' => $campaign->id,
            'char_id' => $character->id,
        ]);
    }

    public function test_it_allows_non_owner_to_remove_their_character()
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();

        $campaign = Campaign::create([
            'guid' => 'camp-remove-2',
            'user_id' => $owner->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);

        $character = Character::create([
            'guid' => 'char-remove-2',
            'user_id' => $player->id,
            'name' => 'Sorcerer',
            'level' => 1,
        ]);

        $campaign->characters()->attach($character->id);

        $token = JWTAuth::fromUser($player);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/campaigns/{$campaign->guid}/characters/{$character->guid}");

        $response->assertStatus(200)
            ->assertJsonFragment(['guid' => $campaign->guid]);

        $this->assertDatabaseMissing('game_character', [
            'game_id' => $campaign->id,
            'char_id' => $character->id,
        ]);
    }

    public function test_it_does_not_allow_player_to_remove_another_character()
    {
        $owner = User::factory()->create();
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();

        $campaign = Campaign::create([
            'guid' => 'camp-remove-2',
            'user_id' => $owner->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);

        $character1 = Character::create([
            'guid' => 'char-remove-2',
            'user_id' => $player1->id,
            'name' => 'Sorcerer',
            'level' => 1,
        ]);
        $character2 = Character::create([
            'guid' => 'char-remove-2',
            'user_id' => $player2->id,
            'name' => 'Sorcerer',
            'level' => 1,
        ]);

        $campaign->characters()->attach($character1->id);
        $campaign->characters()->attach($character2->id);

        $token = JWTAuth::fromUser($player1);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/campaigns/{$campaign->guid}/characters/{$character2->guid}");

        $response->assertStatus(200)
            ->assertJsonFragment(['guid' => $campaign->guid]);

        $this->assertDatabaseHas('game_character', [
            'game_id' => $campaign->id,
            'char_id' => $character2->id,
        ]);
    }

    public function test_it_returns_400_if_character_not_found_when_removing_from_campaign()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-remove-2',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/campaigns/{$campaign->guid}/characters/fake-char");

        $response->assertStatus(400)
            ->assertJson(['error' => 'Bad Request']);
    }

    public function test_it_gracefully_handles_character_not_attached()
    {
        $user = User::factory()->create();

        $campaign = Campaign::create([
            'guid' => 'camp-remove-5',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);

        $character = Character::create([
            'guid' => 'some-char-guid',
            'user_id' => $user->id,
            'level' => 1,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/campaigns/{$campaign->guid}/characters/{$character->guid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('game_character', [
            'game_id' => $campaign->id,
            'char_id' => $character->id,
        ]);
    }

    public function test_it_adds_a_character_to_the_map()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-remove-5',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);
        $map = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);
        $character = Character::create([
            'guid' => 'some-char-guid',
            'user_id' => $user->id,
            'level' => 1,
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'type' => 'character',
            'linked_id' => $character->guid,
            'x' => 5,
            'y' => 10,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/campaigns/{$campaign->guid}/maps/{$map->guid}/entities", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('game_map_entities', [
            'map_id' => $map->id,
            'linked_id' => $character->id,
            'x' => 5,
            'y' => 10,
        ]);
    }

    public function test_it_does_not_add_character_twice_to_the_map()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-remove-5',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);
        $map = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);
        $character = Character::create([
            'guid' => 'some-char-guid',
            'user_id' => $user->id,
            'level' => 1,
        ]);

        CampaignMapCharacterEntity::create([
            'guid' => 'some-guid',
            'map_id' => $map->id,
            'linked_id' => $character->id,
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'type' => 'character',
            'linked_id' => $character->guid,
            'x' => 10,
            'y' => 10,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/campaigns/{$campaign->guid}/maps/{$map->guid}/entities", $payload);

        $response->assertStatus(200);
        $this->assertEquals(1, CampaignMapCharacterEntity::where('map_id', $map->id)->where('linked_id', $character->id)->count());
    }

    public function test_it_adds_a_creature_to_the_map()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-remove-5',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);
        $map = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);
        // there will _always_ be Goblins!
        $creature = GameCreature::where('name', 'Goblin')->first();

        $token = JWTAuth::fromUser($user);

        $payload = [
            'type' => 'creature',
            'linked_id' => $creature->id,
            'entity_name' => $creature->entity_name,
            'x' => 7,
            'y' => 8,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/campaigns/{$campaign->guid}/maps/{$map->guid}/entities", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('game_map_entities', [
            'map_id' => $map->id,
            'entity_name' => 'Goblin',
            'linked_id' => $creature->id,
            'x' => 7,
            'y' => 8,
        ]);
    }

    public function test_it_adds_a_drawing_to_the_map()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-remove-5',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);
        $map = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'type' => 'drawing',
            'startX' => 15,
            'startY' => 20,
            'shape' => 'circle',
            'colour' => '#ff0000',
            'distance' => 10,
            'width' => 0,
            'height' => 0,
            'angle' => 0,
            'fillSymbol' => 'dots',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/campaigns/{$campaign->guid}/maps/{$map->guid}/entities", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('game_map_entities', [
            'map_id' => $map->id,
            'highlight_colour' => '#ff0000',
            'x' => 15,
            'y' => 20,
        ]);
    }

    public function test_it_updates_a_character_entity_position_and_colour()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-remove-5',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);
        $map = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);
        $character = Character::create([
            'guid' => 'some-char-guid',
            'user_id' => $user->id,
            'level' => 1,
        ]);

        $entity = CampaignMapCharacterEntity::create([
            'guid' => 'some-guid',
            'map_id' => $map->id,
            'linked_id' => $character->id,
            'x' => 0,
            'y' => 0,
            'highlight_colour' => '#000000'
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'type' => 'character',
            'x' => 10,
            'y' => 20,
            'highlight_colour' => '#ff00ff',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/campaigns/{$campaign->guid}/maps/{$map->guid}/entities/{$entity->guid}", $payload);

        $response->assertStatus(200);
        $entity->refresh();

        $this->assertEquals(10, $entity->x);
        $this->assertEquals(20, $entity->y);
        $this->assertEquals('#ff00ff', $entity->highlight_colour);
    }

    public function test_it_updates_a_creature_entity_name_and_colour()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-remove-5',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);
        $map = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);
        $entity = CampaignMapCreatureEntity::create([
            'guid' => 'some-guid',
            'linked_id' => 1,
            'type' => 'creature',
            'map_id' => $map->id,
            'entity_name' => 'Orc',
            'highlight_colour' => '#111111',
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'type' => 'creature',
            'entity_name' => 'Orc Leader',
            'highlight_colour' => '#00ff00',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/campaigns/{$campaign->guid}/maps/{$map->guid}/entities/{$entity->guid}", $payload);

        $response->assertStatus(200);
        $entity->refresh();

        $this->assertEquals('Orc Leader', $entity->entity_name);
        $this->assertEquals('#00ff00', $entity->highlight_colour);
    }

    public function test_it_updates_a_drawing_entity_position_and_colour()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-remove-5',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);
        $map = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);
        $entity = CampaignMapDrawingEntity::create([
            'guid' => 'some-guid',
            'linked_id' => 0,
            'type' => 'drawing',
            'map_id' => $map->id,
            'x' => 3,
            'y' => 4,
            'highlight_colour' => '#111111',
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'type' => 'drawing',
            'x' => 50,
            'y' => 100,
            'highlight_colour' => '#abcdef',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/campaigns/{$campaign->guid}/maps/{$map->guid}/entities/{$entity->guid}", $payload);

        $response->assertStatus(200);
        $entity->refresh();

        $this->assertEquals(50, $entity->x);
        $this->assertEquals(100, $entity->y);
        $this->assertEquals('#abcdef', $entity->highlight_colour);
    }

    public function test_it_does_not_update_if_entity_does_not_belong_to_map()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-remove-5',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);
        $map1 = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);
        $map2 = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);

        $entity = CampaignMapCreatureEntity::create([
            'guid' => 'some-guid',
            'linked_id' => 1,
            'type' => 'creature',
            'map_id' => $map1->id,
            'entity_name' => 'Orc',
            'highlight_colour' => '#111111',
            'x' => 100,
            'y' => 100,
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'x' => 1,
            'y' => 1,
            'entity_name' => 'something new',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/campaigns/{$campaign->guid}/maps/{$map2->guid}/entities/{$entity->guid}", $payload);

        $response->assertStatus(200);
        $entity->refresh();

        $this->assertNotEquals(1, $entity->x);
        $this->assertNotEquals(1, $entity->y);
        $this->assertNotEquals('something new', $entity->entity_name);
    }

    public function test_it_soft_deletes_a_map_entity()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-remove-5',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);
        $map = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);
        $entity = CampaignMapCreatureEntity::create([
            'guid' => 'some-guid',
            'linked_id' => 1,
            'type' => 'creature',
            'map_id' => $map->id,
            'entity_name' => 'Orc',
            'highlight_colour' => '#111111',
            'x' => 100,
            'y' => 100,
            'deleted_at' => null,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/campaigns/{$campaign->guid}/maps/{$map->guid}/entities/{$entity->guid}");

        $response->assertStatus(200);
        $entity->refresh();
        $this->assertNotNull($entity->deleted_at);
        $this->assertTrue(Carbon::parse($entity->deleted_at)->isToday());
    }

    public function test_it_returns_404_if_map_does_not_exist_when_deleting_entities()
    {
        $user = User::factory()->create();
        $entityGuid = 'fake-guid-123';

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/campaigns/abc/maps/fake-map/entities/{$entityGuid}");

        $response->assertStatus(404)
            ->assertJson(['error' => 'Campaign map not found']);
    }

    public function test_it_handles_already_soft_deleted_entity()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-remove-5',
            'user_id' => $user->id,
            'name' => 'Campaign 1',
            'description' => 'A test campaign',
        ]);
        $map = CampaignMap::create([
            'name' => 'Map 1',
            'description' => 'A test map',
            'image' => 'some-image.jpg',
            'guid' => 'map-123',
            'game_id' => $campaign->id,
            'width' => 100,
            'height' => 100,
            'show_grid' => false,
            'grid_size' => 20,
            'grid_colour' => '#000000',
        ]);
        $entity = CampaignMapCreatureEntity::create([
            'guid' => 'some-guid',
            'linked_id' => 1,
            'type' => 'creature',
            'map_id' => $map->id,
            'entity_name' => 'Orc',
            'highlight_colour' => '#111111',
            'x' => 100,
            'y' => 100,
            'deleted_at' => now()->subDay(),
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/campaigns/{$campaign->guid}/maps/{$map->guid}/entities/{$entity->guid}");

        $response->assertStatus(200);
        $entity->refresh();
        $this->assertNotNull($entity->deleted_at);
    }

    public function test_it_allows_owner_to_update_their_campaign()
    {
        $user = User::factory()->create();
        $campaign = Campaign::create([
            'guid' => 'camp-001',
            'user_id' => $user->id,
            'name' => 'Old Name',
            'description' => 'Old description',
        ]);

        $token = JWTAuth::fromUser($user);

        $payload = [
            'name' => 'New Campaign Name',
            'description' => 'New campaign description',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/campaigns/{$campaign->guid}", $payload);

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'New Campaign Name']);

        $campaign->refresh();
        $this->assertEquals('New Campaign Name', $campaign->name);
        $this->assertEquals('New campaign description', $campaign->description);
    }

    public function test_it_should_not_allow_non_owner_to_update_a_campaign()
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();

        $campaign = Campaign::create([
            'guid' => 'camp-002',
            'user_id' => $owner->id,
            'name' => 'Old Name',
            'description' => 'Old description',
        ]);

        $token = JWTAuth::fromUser($player);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/campaigns/{$campaign->guid}", [
                'name' => 'New Name',
            ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Not your campaign',
            ]);

        $campaign->refresh();
        $this->assertNotEquals('New Name', $campaign->name);
    }

    public function test_it_returns_400_if_campaign_not_found_when_updating_campaign()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/campaigns/nonexistent-guid", [
                'name' => 'Should Not Exist',
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Bad Request']);
    }
}
