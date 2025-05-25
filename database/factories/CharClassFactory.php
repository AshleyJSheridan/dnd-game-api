<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CharClass>
 */
class CharClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->numberBetween(100, 200),
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'primary_ability_1' => $this->faker->numberBetween(1, 6),
            'primary_ability_2' => $this->faker->numberBetween(1, 6),
            'saving_throw_ability_1' => $this->faker->numberBetween(1, 6),
            'saving_throw_ability_2' => $this->faker->numberBetween(1, 6),
            'hit_points_per_level' => $this->faker->numberBetween(1, 10),
            'hit_points_start' => $this->faker->numberBetween(1, 10),
            'max_tools' => $this->faker->numberBetween(1, 5),
            'path_name' => $this->faker->word(),
            'path_level' => $this->faker->numberBetween(1, 6),
            'path_description' => $this->faker->sentence(),
        ];
    }
}
