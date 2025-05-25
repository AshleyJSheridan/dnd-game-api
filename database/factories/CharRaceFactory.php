<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CharRace>
 */
class CharRaceFactory extends Factory
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
            'description' => fake()->text(),
            'max_age' => fake()->numberBetween(1, 300),
            'max_height' => fake()->numberBetween(1, 10),
            'speed' => fake()->numberBetween(1, 50),
            'has_sub_races' => fake()->boolean(),
            'parent_race_id' => fake()->randomDigit(),
        ];
    }
}
