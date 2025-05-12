<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CampaignMap>
 */
class CampaignMapFactory extends Factory
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
            'game_id' => fake()->randomNumber(),
            'image' => fake()->imageUrl(),
            'width' => fake()->randomNumber(3),
            'height' => fake()->randomNumber(3),
        ];
    }
}
