<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomNumber(),
            'description' => fake()->text(),
            'guid' => fake()->uuid(),
            'user_id' => fake()->randomNumber(),
            'created_at' => now(),
        ];
    }
}
