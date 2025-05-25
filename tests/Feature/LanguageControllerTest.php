<?php

namespace Tests\Feature;

use App\Models\CharLanguage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
class LanguageControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_all_languages_that_do_not_have_limited_access()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $foo = CharLanguage::factory()->create(['name' => 'Foo', 'limited_access' => 0]);
        $bar = CharLanguage::factory()->create(['name' => 'Bar', 'limited_access' => 0]);
        $baz = CharLanguage::factory()->create(['name' => 'Baz', 'limited_access' => 1]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/game/languages');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Foo']);
        $response->assertJsonFragment(['name' => 'Bar']);
        $response->assertJsonMissing(['name' => 'Baz']);
    }
}
