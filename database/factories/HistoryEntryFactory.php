<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\HistoryEntry>
 */
class HistoryEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'year' => fake()->numberBetween(1970, 2026),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'photo_file_id' => null,
            'position' => fake()->numberBetween(0, 50),
            'published' => true,
        ];
    }
}
