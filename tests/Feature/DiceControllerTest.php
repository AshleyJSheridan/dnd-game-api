<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DiceRollService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DiceControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_a_dice_roll_result()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $payload = ['dice' => ['d6' => '2', 'd20' => '1']];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/game/dice', $payload);

        $response->assertStatus(200);
        $responseJson = $response->json();
        $this->assertMatchesRegularExpression('/^[a-f0-9\-]+/', $responseJson['guid']);
        $this->assertCount(2, $responseJson['rolls']['d6']);
        $this->assertCount(1, $responseJson['rolls']['d20']);
        $this->assertMatchesRegularExpression('/^[1-6]/', $responseJson['rolls']['d6'][0]);
        $this->assertMatchesRegularExpression('/^[1-6]/', $responseJson['rolls']['d6'][1]);
        $this->assertMatchesRegularExpression('/^(20|(1?[0-9]))/', $responseJson['rolls']['d20'][0]);
    }

    public function test_it_returns_400_if_no_dice_data_is_sent()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->post('/api/game/dice', [], ['Content-Type' => 'application/json']);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'No dice',
        ]);
    }
}
