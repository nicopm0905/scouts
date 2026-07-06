<?php

namespace Database\Factories;

use App\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Photo>
 */
class PhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'album_id' => Album::factory(),
            'drive_file_id' => 'fake-'.fake()->uuid(),
            'caption' => fake()->optional()->sentence(3),
            'position' => fake()->numberBetween(0, 30),
        ];
    }
}
