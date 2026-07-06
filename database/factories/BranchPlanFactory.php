<?php

namespace Database\Factories;

use App\Enums\MemberRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\BranchPlan>
 */
class BranchPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch' => fake()->randomElement(MemberRole::branches()),
            'school_year' => '2026-2027',
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
