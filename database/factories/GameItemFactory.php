<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameItem>
 */
class GameItemFactory extends Factory
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
            'cost' => $this->faker->numberBetween(1, 1000),
            'cost_unit' => $this->faker->randomElement(['cp', 'sp', 'ep', 'gp', 'pp']),
            'type' => $this->faker->randomElement(['weapon', 'pack', 'armor', 'projectile', 'other', 'potion', 'clothing',
                'book', 'food', 'gemstone', 'art object', 'bag', 'artisan', 'instrument', 'gaming']),
            'container' => $this->faker->boolean(),
            'capacity' => $this->faker->numberBetween(1, 100),
            'rarity' => $this->faker->randomElement(['common', 'uncommon', 'rare', 'very rare', 'legendary']),
            'generated' => $this->faker->randomElement(['yes', 'no']),
            'generic' => $this->faker->randomElement(['yes', 'no']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
