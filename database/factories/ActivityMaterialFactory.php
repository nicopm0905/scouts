<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ActivityMaterial>
 */
class ActivityMaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'name' => fake()->randomElement(['Cuerdas', 'Pañuelos', 'Folios', 'Rotuladores', 'Pelotas', 'Conos']),
            'quantity' => fake()->numberBetween(1, 20),
            'inventory_item_id' => null,
        ];
    }
}
