<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\HealthRecord>
 */
class HealthRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'allergies' => fake()->optional()->randomElement(['Frutos secos', 'Polen', 'Ácaros', 'Ninguna']),
            'intolerances' => fake()->optional()->randomElement(['Lactosa', 'Gluten']),
            'medication' => fake()->optional()->sentence(3),
            'observations' => fake()->optional()->sentence(6),
            'health_card_number' => fake()->numerify('AN##########'),
            'drive_file_id' => null,
        ];
    }
}
