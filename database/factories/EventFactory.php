<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Enums\MemberRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+3 months');
        $end = (clone $start)->modify('+'.fake()->numberBetween(2, 72).' hours');

        return [
            'title' => fake()->randomElement(['Reunión de rama', 'Salida al campo', 'Acampada de otoño', 'Consejo de grupo']),
            'type' => fake()->randomElement(EventType::cases()),
            'start_at' => $start,
            'end_at' => $end,
            'location' => fake()->city(),
            'description' => fake()->optional()->paragraph(),
            'branches' => fake()->randomElements(MemberRole::values(), fake()->numberBetween(1, 3)),
            'created_by' => null,
        ];
    }

    public function ofType(EventType $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    public function camp(): static
    {
        return $this->state(fn () => [
            'type' => EventType::Campamento,
            'title' => 'Campamento de verano',
        ]);
    }
}
