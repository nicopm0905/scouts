<?php

namespace Database\Factories;

use App\Enums\MemberRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName().' '.fake()->lastName(),
            'phone' => fake()->numerify('6########'),
            'email' => fake()->safeEmail(),
            'role' => fake()->randomElement(MemberRole::branches()),
            'birth_date' => fake()->dateTimeBetween('-17 years', '-6 years'),
            'active' => true,
            'joined_at' => fake()->dateTimeBetween('-4 years', 'now'),
            'notes' => null,
        ];
    }

    /** Miembro de una rama concreta con edad coherente. */
    public function branch(MemberRole $branch): static
    {
        [$min, $max] = match ($branch) {
            MemberRole::Castor => ['-9 years', '-6 years'],
            MemberRole::Lobato => ['-12 years', '-8 years'],
            MemberRole::Ranger => ['-15 years', '-11 years'],
            MemberRole::Pionero => ['-17 years', '-14 years'],
            MemberRole::Ruta => ['-21 years', '-17 years'],
            MemberRole::Responsable => ['-45 years', '-19 years'],
        };

        return $this->state(fn () => [
            'role' => $branch,
            'birth_date' => fake()->dateTimeBetween($min, $max),
        ]);
    }

    public function leader(): static
    {
        return $this->branch(MemberRole::Responsable);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
