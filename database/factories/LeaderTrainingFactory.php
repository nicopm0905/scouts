<?php

namespace Database\Factories;

use App\Models\LeaderProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\LeaderTraining>
 */
class LeaderTrainingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'leader_profile_id' => LeaderProfile::factory(),
            'name' => fake()->randomElement([
                'Manipulador de alimentos',
                'Primeros auxilios',
                'MSC Módulo 0',
                'Monitor de tiempo libre',
            ]),
            'obtained_at' => fake()->dateTimeBetween('-3 years', '-1 month'),
            'expires_at' => fake()->optional()->dateTimeBetween('+1 month', '+4 years'),
            'drive_file_id' => null,
        ];
    }
}
