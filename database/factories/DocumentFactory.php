<?php

namespace Database\Factories;

use App\Enums\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'category' => fake()->randomElement(DocumentCategory::cases()),
            'drive_file_id' => 'fake-'.fake()->uuid(),
            'external_url' => null,
            'expires_at' => fake()->optional()->dateTimeBetween('-1 month', '+1 year'),
            'notes' => null,
            'created_by' => null,
        ];
    }

    public function expiringSoon(): static
    {
        return $this->state(fn () => [
            'category' => DocumentCategory::Seguros,
            'expires_at' => now()->addDays(fake()->numberBetween(1, 25)),
        ]);
    }
}
