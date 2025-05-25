<?php

namespace Tests\Feature;

use App\Models\CharClass;
use App\Models\GameSpell;
use App\Models\GameSpellSchool;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
class SpellControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_all_spells()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $foo = GameSpell::factory()->create(['name' => 'Foo']);
        $bar = GameSpell::factory()->create(['name' => 'Bar']);
        $baz = GameSpell::factory()->create(['name' => 'Baz']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/game/spells');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Foo']);
        $response->assertJsonFragment(['name' => 'Bar']);
        $response->assertJsonFragment(['name' => 'Baz']);
    }

    public function test_it_returns_all_spells_by_level()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $foo = GameSpell::factory()->create(['name' => 'Foo', 'level' => 1]);
        $bar = GameSpell::factory()->create(['name' => 'Bar', 'level' => 1]);
        $baz = GameSpell::factory()->create(['name' => 'Baz', 'level' => 2]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/game/spells/level/1');

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Foo']);
        $response->assertJsonFragment(['name' => 'Bar']);
        $response->assertJsonMissing(['name' => 'Baz']);
    }

    public function test_it_returns_all_spells_by_school()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $school1 = GameSpellSchool::factory()->create(['name' => 'School1']);
        $school2 = GameSpellSchool::factory()->create(['name' => 'School2']);

        $foo = GameSpell::factory()->create(['name' => 'Foo', 'school' => $school1->id]);
        $bar = GameSpell::factory()->create(['name' => 'Bar', 'school' => $school1->id]);
        $baz = GameSpell::factory()->create(['name' => 'Baz', 'school' => $school1->id]);
        $biz = GameSpell::factory()->create(['name' => 'Biz', 'school' => $school2->id]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/game/spells/school/{$school1->name}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Foo']);
        $response->assertJsonFragment(['name' => 'Bar']);
        $response->assertJsonFragment(['name' => 'Baz']);
        $response->assertJsonMissing(['name' => 'Biz']);
    }

    public function test_it_returns_all_spells_by_school_and_level()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $school1 = GameSpellSchool::factory()->create(['name' => 'School1']);
        $school2 = GameSpellSchool::factory()->create(['name' => 'School2']);

        $foo = GameSpell::factory()->create(['name' => 'Foo', 'school' => $school1->id, 'level' => 1]);
        $bar = GameSpell::factory()->create(['name' => 'Bar', 'school' => $school1->id, 'level' => 1]);
        $baz = GameSpell::factory()->create(['name' => 'Baz', 'school' => $school1->id, 'level' => 2]);
        $biz = GameSpell::factory()->create(['name' => 'Biz', 'school' => $school2->id, 'level' => 1]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/game/spells/school/{$school1->name}/level/1");

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Foo']);
        $response->assertJsonFragment(['name' => 'Bar']);
        $response->assertJsonMissing(['name' => 'Baz']);
        $response->assertJsonMissing(['name' => 'Biz']);
    }

    public function test_it_returns_all_spells_for_a_char_class()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $class = CharClass::factory()->create();

        $spell1 = GameSpell::factory()->create(['name' => 'Some spell 1', 'level' => 0]);
        $spell2 = GameSpell::factory()->create(['name' => 'Some spell 2', 'level' => 1]);
        $spell1->CharClasses()->attach($class->id);
        $spell2->CharClasses()->attach($class->id);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/game/spells/class/{$class->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Some spell 1']);
        $response->assertJsonFragment(['name' => 'Some spell 2']);
    }

    public function test_it_returns_spells_for_a_class_up_to_a_given_level()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $class = CharClass::factory()->create();

        $lvl1 = GameSpell::factory()->create(['name' => 'Some spell 1', 'level' => 1]);
        $lvl3 = GameSpell::factory()->create(['name' => 'Some spell 2', 'level' => 3]);
        $lvl1->CharClasses()->attach($class->id);
        $lvl3->CharClasses()->attach($class->id);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/game/spells/class/{$class->id}/level/2");

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Some spell 1']);
        $response->assertJsonMissing(['name' => 'Some spell 2']);
    }
}
