<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Alignment;

class CreatureAlignmentControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_all_character_alignments()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $foo = Alignment::factory()->create(['alignment' => 'Foo']);
        $bar = Alignment::factory()->create(['alignment' => 'Bar']);
        $baz = Alignment::factory()->create(['alignment' => 'Baz']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/characters/alignments');

        $response->assertStatus(200);
        $response->assertJsonFragment(['alignment' => 'Foo']);
        $response->assertJsonFragment(['alignment' => 'Bar']);
        $response->assertJsonFragment(['alignment' => 'Baz']);
    }
}
