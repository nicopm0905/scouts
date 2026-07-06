<?php

namespace Database\Factories;

use App\Enums\ConsentType;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Consent>
 */
class ConsentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'type' => fake()->randomElement(ConsentType::cases()),
            'granted' => fake()->boolean(85),
            'signed_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'drive_file_id' => null,
        ];
    }

    public function ofType(ConsentType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }
}
