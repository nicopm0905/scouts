<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Family>
 */
class FamilyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Familia '.fake()->lastName().' '.fake()->lastName(),
            'contact_phone' => fake()->numerify('6########'),
            'contact_email' => fake()->safeEmail(),
            'notes' => null,
        ];
    }
}
