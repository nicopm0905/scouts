<?php

use App\Enums\EventType;
use App\Enums\MemberRole;
use App\Models\Event;
use App\Models\Member;
use Illuminate\Support\Str;

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
    $event = Event::factory()->create(['branches' => [MemberRole::Pionero->value], 'type' => EventType::Reunion]);

    $this->actingAs($user)->put(route('events.update', $event), [
        'title' => 'Actualizado por su responsable',
        'type' => $event->type->value,
        'start_at' => $event->start_at->toDateTimeString(),
        'branches' => [MemberRole::Pionero->value],
    ])->assertRedirect();

    $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Actualizado por su responsable']);
});

it('no expone el enlace público de inscripción a un usuario sin permiso de gestión', function () {
    $user = userWithRole('familia');
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Pionero->value]]);
    $member = Member::factory()->branch(MemberRole::Pionero)->create();
    $event->members()->attach($member->id, ['enrolled' => true, 'public_token' => Str::random(40)]);

    $this->actingAs($user)->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->has('enrollments', 1)
            ->missing('enrollments.0.public_url'));
});

it('expone el enlace público de inscripción a quien puede gestionar el evento', function () {
    $user = userWithRole('responsable', [MemberRole::Pionero->value]);
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Pionero->value]]);
    $member = Member::factory()->branch(MemberRole::Pionero)->create();
    $event->members()->attach($member->id, ['enrolled' => true, 'public_token' => Str::random(40)]);

    $this->actingAs($user)->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->has('enrollments.0.public_url'));
});
