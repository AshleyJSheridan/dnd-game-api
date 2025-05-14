<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CharBackground>
 */
class CharBackgroundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'proficiency_1' => $this->faker->numberBetween(1, 10),
            'proficiency_2' => $this->faker->numberBetween(1, 10),
            'extra_languages' => $this->faker->numberBetween(1, 10),
            'gold' => $this->faker->numberBetween(1, 10),
        ];
    }
}
