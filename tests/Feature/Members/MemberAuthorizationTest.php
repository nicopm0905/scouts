<?php

use App\Enums\MemberRole;
use App\Models\Family;
use App\Models\Member;

it('un responsable con rama lobato recibe 403 al abrir la ficha de un miembro pionero', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $pionero = Member::factory()->branch(MemberRole::Pionero)->create();

    $this->actingAs($user)->get(route('members.show', $pionero))
        ->assertForbidden();
});

it('un responsable con rama lobato no ve miembros de otras ramas en el listado', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    Member::factory()->branch(MemberRole::Lobato)->create();
    Member::factory()->branch(MemberRole::Pionero)->create();

    $response = $this->actingAs($user)->get(route('members.index'));

    $response->assertInertia(fn ($page) => $page->component('Members/Index')->has('members', 1));
});

it('un responsable con rama lobato recibe 403 al editar un miembro pionero', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $pionero = Member::factory()->branch(MemberRole::Pionero)->create();

    $this->actingAs($user)->put(route('members.update', $pionero), [
        'first_name' => 'Hackeado',
        'last_name' => $pionero->last_name,
        'role' => MemberRole::Pionero->value,
    ])->assertForbidden();
});

it('un responsable con rama lobato solo ve familias con miembros de sus ramas', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);

    $lobato = Member::factory()->branch(MemberRole::Lobato)->create();
    $familiaLobato = Family::create(['name' => 'Familia Lobato']);
    $familiaLobato->members()->attach($lobato->id, ['relationship' => 'madre']);

    $pionero = Member::factory()->branch(MemberRole::Pionero)->create();
    $familiaPionero = Family::create(['name' => 'Familia Pionera']);
    $familiaPionero->members()->attach($pionero->id, ['relationship' => 'padre']);

    $this->actingAs($user)->get(route('families.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Members/Families/Index')
            ->has('families', 1)
            ->where('families.0.name', 'Familia Lobato'));
});

it('secretaría ve todas las familias en el listado', function () {
    $user = userWithRole('secretaria');

    $lobato = Member::factory()->branch(MemberRole::Lobato)->create();
    $familiaLobato = Family::create(['name' => 'Familia Lobato']);
    $familiaLobato->members()->attach($lobato->id, ['relationship' => 'madre']);

    Family::create(['name' => 'Familia Sin Miembros']);

    $this->actingAs($user)->get(route('families.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Members/Families/Index')
            ->has('families', 2));
});
