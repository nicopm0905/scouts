<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Album>
 */
class AlbumFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['Acampada de otoño', 'San Jorge', 'Campamento verano', 'Salida al río']),
            'description' => fake()->optional()->sentence(),
            'event_id' => null,
            'drive_folder_id' => 'folder-'.fake()->uuid(),
            'visibility' => fake()->randomElement(['internal', 'publishable']),
        ];
    }

    public function publishable(): static
    {
        return $this->state(fn () => ['visibility' => 'publishable']);
    }
}
