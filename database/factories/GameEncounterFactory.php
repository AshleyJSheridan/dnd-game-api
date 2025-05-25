<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameEncounter>
 */
class GameEncounterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guid' => $this->faker->uuid(),
            'type' => 'creature',
            'description' => $this->faker->text(),
            'difficulty' => $this->faker->numberBetween(1, 1000),
            'party_difficulty' => $this->faker->numberBetween(1, 1000),
            'environment' => $this->faker->randomElement(['forest', 'desert', 'mountain', 'swamp', 'urban']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
