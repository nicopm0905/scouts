<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'event_id' => null,
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'present' => fake()->boolean(80),
            'recorded_by' => null,
        ];
    }
}
