<?php

namespace Database\Factories;

use App\Enums\InvoiceCategory;
use App\Enums\InvoiceDirection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 20, 800);

        return [
            'direction' => InvoiceDirection::Received,
            'number' => null,
            'date' => fake()->dateTimeBetween('-1 year', 'now'),
            'supplier_or_client' => fake()->company(),
            'concept' => fake()->sentence(3),
            'amount' => $amount,
            'vat' => round($amount * 0.21, 2),
            'category' => fake()->randomElement(InvoiceCategory::cases()),
            'drive_file_id' => null,
            'created_by' => null,
        ];
    }

    public function issued(): static
    {
        return $this->state(fn () => ['direction' => InvoiceDirection::Issued]);
    }
}
