<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Checkout>
 */
class CheckoutFactory extends Factory
{
    public function definition(): array
    {
        $out = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'inventory_item_id' => InventoryItem::factory(),
            'quantity' => fake()->numberBetween(1, 3),
            'event_id' => null,
            'member_id' => null,
            'checked_out_at' => $out,
            'expected_return_at' => (clone $out)->modify('+7 days'),
            'returned_at' => null,
            'notes' => null,
        ];
    }

    public function returned(): static
    {
        return $this->state(fn (array $attrs) => [
            'returned_at' => $attrs['expected_return_at'] ?? now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'checked_out_at' => now()->subDays(20),
            'expected_return_at' => now()->subDays(6),
            'returned_at' => null,
        ]);
    }
}
