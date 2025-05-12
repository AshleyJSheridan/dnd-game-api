<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CampaignMapDrawingEntity>
 */
class CampaignMapDrawingEntityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guid' => 'some-guid',
            'linked_id' => 0,
            'type' => 'drawing',
            'map_id' => fake()->randomNumber(),
            'x' => fake()->randomNumber(),
            'y' => fake()->randomNumber(),
            'highlight_colour' => '#000000',
        ];
    }
}
