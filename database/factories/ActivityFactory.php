<?php

namespace Database\Factories;

use App\Enums\MemberRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement([
                'Gran juego de pistas', 'Taller de nudos', 'Velada nocturna',
                'Dinámica de equipos', 'Construcción de pionerismo', 'Rastreo por señales',
            ]),
            'branch' => fake()->optional()->randomElement(MemberRole::branches())?->value,
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90, 120]),
            'objectives_text' => fake()->sentence(10),
            'development' => "## Desarrollo\n\n".fake()->paragraphs(3, true),
            'attachment_file_ids' => null,
            'created_by' => null,
        ];
    }
}
