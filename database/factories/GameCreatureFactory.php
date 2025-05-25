<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameCreature>
 */
class GameCreatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'size' => $this->faker->randomElement(['tiny', 'small', 'medium', 'large', 'huge', 'gargantuan']),
            'type' => $this->faker->randomElement(['aberration', 'beast', 'celestial', 'construct', 'dragon', 'elemental', 'fey', 'fiend', 'giant', 'humanoid', 'monstrosity', 'ooze', 'plant', 'undead']),
            'alignment' => $this->faker->numberBetween(1, 9),
            'armor_class' => $this->faker->numberBetween(1, 20),
            'wears_armor' => $this->faker->boolean(),
            'hit_points_dice' => $this->faker->numberBetween(1, 10),
            'hit_points_dice_sides' => $this->faker->randomElement(['d4', 'd6', 'd8', 'd10', 'd12', 'd20']),
            'hit_point_additional' => $this->faker->numberBetween(1, 10),
            'speed' => $this->faker->numberBetween(1, 50),
            'abilities' => json_encode([
                '1' => $this->faker->numberBetween(1, 20),
                '2' => $this->faker->numberBetween(1, 20),
                '3' => $this->faker->numberBetween(1, 20),
                '4' => $this->faker->numberBetween(1, 20),
                '5' => $this->faker->numberBetween(1, 20),
                '6' => $this->faker->numberBetween(1, 20),
            ]),
            'challenge_rating' => $this->faker->randomFloat(),
            'exp' => $this->faker->numberBetween(1, 10000),
            'saving_throw_abilities' => '',
            'skill_modifiers' => '',
            'spell_caster' => $this->faker->boolean(),
        ];
    }
}
