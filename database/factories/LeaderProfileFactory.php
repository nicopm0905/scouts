<?php

namespace Database\Factories;

use App\Enums\LeaderQualification;
use App\Enums\MemberRole;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\LeaderProfile>
 */
class LeaderProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_id' => Member::factory()->leader(),
            'qualification' => fake()->randomElement(LeaderQualification::cases()),
            'sexual_offenses_certificate_date' => fake()->dateTimeBetween('-2 years', '-1 month'),
            'sexual_offenses_certificate_expires_at' => fake()->dateTimeBetween('+1 month', '+3 years'),
            'branches' => [fake()->randomElement(MemberRole::branches())->value],
        ];
    }

    public function director(): static
    {
        return $this->state(fn () => ['qualification' => LeaderQualification::Director]);
    }

    public function inTraining(): static
    {
        return $this->state(fn () => ['qualification' => LeaderQualification::InTraining]);
    }

    /** Certificado de delitos sexuales caducado (para probar el validador). */
    public function expiredCertificate(): static
    {
        return $this->state(fn () => [
            'sexual_offenses_certificate_date' => fake()->dateTimeBetween('-5 years', '-3 years'),
            'sexual_offenses_certificate_expires_at' => fake()->dateTimeBetween('-2 years', '-1 month'),
        ]);
    }

    public function withoutCertificate(): static
    {
        return $this->state(fn () => [
            'sexual_offenses_certificate_date' => null,
            'sexual_offenses_certificate_expires_at' => null,
        ]);
    }
}
