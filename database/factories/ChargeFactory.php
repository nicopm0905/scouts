<?php

namespace Database\Factories;

use App\Enums\ChargeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Charge>
 */
class ChargeFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(ChargeType::cases());

        return [
            'title' => $type->label().' '.fake()->year(),
            'description' => fake()->optional()->sentence(),
            'amount' => fake()->randomElement([15, 25, 40, 60, 120, 180]),
            'due_date' => fake()->dateTimeBetween('-1 month', '+2 months'),
            'type' => $type,
            'event_id' => null,
            'target_branches' => null,
            'sibling_discount_applies' => $type->isQuota(),
            'created_by' => null,
        ];
    }
}
