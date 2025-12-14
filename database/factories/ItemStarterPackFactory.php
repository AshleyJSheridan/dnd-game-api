<?php

namespace Database\Factories;

use App\Models\CharClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ItemStarterPack>
 */
class ItemStarterPackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'char_class_id' => 0,
            'char_background_id' => 0,
            'choice_name' => $this->faker->name(),
            'gold' => $this->faker->numberBetween(1, 100),
            'type' => $this->faker->randomElement(['class', 'background']),
        ];
    }
}
