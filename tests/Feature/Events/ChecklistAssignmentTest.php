<?php

use App\Enums\EventType;
use App\Enums\MemberRole;
use App\Models\Event;
use App\Models\EventChecklistItem;

it('asigna un punto de la checklist a una persona con fecha límite', function () {
    $manager = userWithRole('secretaria');
    $assignee = userWithRole('responsable', [MemberRole::Lobato->value]);
    $event = Event::factory()->create(['type' => EventType::Acampada, 'branches' => [MemberRole::Lobato->value]]);

    $this->actingAs($manager)->post(route('events.checklist.store', $event), [
        'label' => 'Comprar material de cocina',
        'assigned_to' => $assignee->id,
        'due_at' => now()->addWeek()->toDateString(),
    ])->assertSessionHasNoErrors();

    $item = EventChecklistItem::where('label', 'Comprar material de cocina')->first();

    expect($item)->not->toBeNull()
        ->and($item->assigned_to)->toBe($assignee->id)
        ->and($item->due_at->toDateString())->toBe(now()->addWeek()->toDateString());
});

it('la tarea asignada aparece en el panel de inicio de esa persona', function () {
    $assignee = userWithRole('responsable', [MemberRole::Lobato->value]);
    $event = Event::factory()->create(['type' => EventType::Acampada, 'title' => 'Acampada de otoño']);

    EventChecklistItem::create([
        'event_id' => $event->id,
        'label' => 'Reservar autobús',
        'assigned_to' => $assignee->id,
        'due_at' => now()->addDays(3),
        'done' => false,
    ]);

    $this->actingAs($assignee)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('tasks', 1)
            ->where('tasks.0.label', 'Reservar autobús')
            ->where('tasks.0.event_title', 'Acampada de otoño'));
});

it('las tareas ya hechas no aparecen en el panel', function () {
    $assignee = userWithRole('responsable', [MemberRole::Lobato->value]);
    $event = Event::factory()->create(['type' => EventType::Acampada]);

    EventChecklistItem::create([
        'event_id' => $event->id,
        'label' => 'Tarea terminada',
        'assigned_to' => $assignee->id,
        'done' => true,
    ]);

    $this->actingAs($assignee)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->has('tasks', 0));
});
