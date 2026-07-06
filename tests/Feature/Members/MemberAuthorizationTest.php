<?php

use App\Enums\MemberRole;
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
