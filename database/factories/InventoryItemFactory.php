<?php

namespace Database\Factories;

use App\Enums\InventoryCategory;
use App\Enums\ItemCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Tienda canadiense', 'Hornillo de gas', 'Olla grande', 'Botiquín',
                'Cuerda 20m', 'Lona', 'Bidón de agua', 'Caja de juegos',
            ]),
            'category' => fake()->randomElement(InventoryCategory::cases()),
            'quantity' => fake()->numberBetween(1, 15),
            'condition' => fake()->randomElement(ItemCondition::cases()),
            'location' => fake()->randomElement(['Local', 'Contenedor', 'Casa de Pedro', 'Almacén']),
            'next_review_at' => fake()->optional()->dateTimeBetween('-1 month', '+6 months'),
            'photo_file_id' => null,
            'notes' => null,
        ];
    }

    public function reviewDue(): static
    {
        return $this->state(fn () => [
            'category' => InventoryCategory::Botiquin,
            'next_review_at' => now()->addDays(fake()->numberBetween(1, 20)),
        ]);
    }
}
