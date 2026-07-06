<?php

use App\Enums\MemberRole;
use App\Models\Event;

it('un responsable de otra rama recibe 403 al editar un evento', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $event = Event::factory()->create(['branches' => [MemberRole::Pionero->value]]);

    $this->actingAs($user)->put(route('events.update', $event), [
        'title' => 'Hackeado',
        'type' => $event->type->value,
        'start_at' => $event->start_at->toDateTimeString(),
        'branches' => [MemberRole::Pionero->value],
    ])->assertForbidden();
});

it('un responsable de la misma rama puede editar el evento', function () {
    $user = userWithRole('responsable', [MemberRole::Pionero->value]);
    $event = Event::factory()->create(['branches' => [MemberRole::Pionero->value], 'type' => \App\Enums\EventType::Reunion]);

    $this->actingAs($user)->put(route('events.update', $event), [
        'title' => 'Actualizado por su responsable',
        'type' => $event->type->value,
        'start_at' => $event->start_at->toDateTimeString(),
        'branches' => [MemberRole::Pionero->value],
    ])->assertRedirect();

    $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Actualizado por su responsable']);
});
