<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CampaignMapCharacterEntity>
 */
class CampaignMapCharacterEntityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guid' => fake()->uuid(),
            'map_id' => fake()->randomNumber(),
            'linked_id' => fake()->randomNumber(),
            'type' => 'character',
        ];
    }
}
