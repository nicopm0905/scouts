<?php

use App\Enums\EventType;
use App\Enums\MemberRole;
use App\Models\Event;

it('un usuario con events.manage puede crear un evento', function () {
    $user = userWithRole('secretaria');

    $response = $this->actingAs($user)->post(route('events.store'), [
        'title' => 'Reunión de lobatos',
        'type' => EventType::Reunion->value,
        'start_at' => now()->addWeek()->toDateTimeString(),
        'end_at' => now()->addWeek()->addHours(2)->toDateTimeString(),
        'location' => 'Local del grupo',
        'description' => 'Reunión semanal',
        'branches' => [MemberRole::Lobato->value],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('events', ['title' => 'Reunión de lobatos']);
});

it('lista y muestra un evento existente', function () {
    $user = userWithRole('secretaria');
    $event = Event::factory()->create(['type' => EventType::Reunion]);

    $this->actingAs($user)->get(route('events.index'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('Events/Index'));

    $this->actingAs($user)->get(route('events.show', $event))->assertOk()
        ->assertInertia(fn ($page) => $page->component('Events/Show')->where('event.id', $event->id));
});

it('actualiza un evento', function () {
    $user = userWithRole('secretaria');
    $event = Event::factory()->create(['type' => EventType::Reunion, 'title' => 'Original']);

    $response = $this->actingAs($user)->put(route('events.update', $event), [
        'title' => 'Actualizado',
        'type' => EventType::Reunion->value,
        'start_at' => $event->start_at->toDateTimeString(),
        'end_at' => null,
        'location' => null,
        'description' => null,
        'branches' => [],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Actualizado']);
});

it('elimina un evento', function () {
    $user = userWithRole('secretaria');
    $event = Event::factory()->create();

    $this->actingAs($user)->delete(route('events.destroy', $event))->assertRedirect();

    $this->assertSoftDeleted('events', ['id' => $event->id]);
});
