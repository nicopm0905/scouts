<?php

namespace Database\Factories;

use App\Enums\ObjectiveStatus;
use App\Models\BranchPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\BranchPlanObjective>
 */
class BranchPlanObjectiveFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_plan_id' => BranchPlan::factory(),
            'description' => fake()->sentence(8),
            'term' => fake()->numberBetween(1, 3),
            'status' => fake()->randomElement(ObjectiveStatus::cases()),
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
