<?php

use App\Enums\MemberRole;
use App\Models\Member;

it('secretaría puede listar miembros', function () {
    $user = userWithRole('secretaria');
    Member::factory()->count(3)->create();

    $this->actingAs($user)->get(route('members.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Members/Index')->has('members', 3));
});

it('secretaría puede crear un miembro', function () {
    $user = userWithRole('secretaria');

    $response = $this->actingAs($user)->post(route('members.store'), [
        'first_name' => 'Ana',
        'last_name' => 'García Pérez',
        'phone' => '600111222',
        'email' => 'ana@example.com',
        'role' => MemberRole::Lobato->value,
        'birth_date' => '2016-04-01',
        'active' => true,
        'joined_at' => '2023-09-01',
        'notes' => null,
    ]);

    $member = Member::firstWhere('first_name', 'Ana');
    $response->assertRedirect(route('members.show', $member));
    $this->assertDatabaseHas('members', ['first_name' => 'Ana', 'last_name' => 'García Pérez']);
});

it('secretaría puede ver la ficha de un miembro', function () {
    $user = userWithRole('secretaria');
    $member = Member::factory()->create();

    $this->actingAs($user)->get(route('members.show', $member))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Members/Show')->where('member.id', $member->id));
});

it('secretaría puede actualizar un miembro', function () {
    $user = userWithRole('secretaria');
    $member = Member::factory()->create(['first_name' => 'Antiguo']);

    $this->actingAs($user)->put(route('members.update', $member), [
        'first_name' => 'Nuevo',
        'last_name' => $member->last_name,
        'phone' => $member->phone,
        'email' => $member->email,
        'role' => $member->role->value,
        'birth_date' => $member->birth_date?->toDateString(),
        'active' => true,
        'joined_at' => $member->joined_at?->toDateString(),
        'notes' => null,
    ])->assertRedirect(route('members.show', $member));

    expect($member->fresh()->first_name)->toBe('Nuevo');
});

it('secretaría puede eliminar un miembro', function () {
    $user = userWithRole('secretaria');
    $member = Member::factory()->create();

    $this->actingAs($user)->delete(route('members.destroy', $member))
        ->assertRedirect(route('members.index'));

    $this->assertSoftDeleted('members', ['id' => $member->id]);
});

it('familia no puede crear miembros', function () {
    $user = userWithRole('familia');

    $this->actingAs($user)->post(route('members.store'), [
        'first_name' => 'Sin', 'last_name' => 'Permiso', 'role' => MemberRole::Lobato->value,
    ])->assertForbidden();
});
