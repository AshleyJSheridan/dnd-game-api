<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameSpell>
 */
class GameSpellFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'level' => fake()->numberBetween(1, 20),
            'school' => fake()->numberBetween(1, 8),
            'cast_time' => fake()->text(10),
            'duration' => fake()->text(10),
            'range' => fake()->text(10),
            'components' => fake()->text(10),
            'concentration' => fake()->boolean(),
            'ritual' => fake()->boolean(),
            'description' => fake()->text(),
        ];
    }
}
