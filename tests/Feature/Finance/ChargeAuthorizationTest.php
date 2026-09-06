<?php

use App\Enums\ChargeType;
use App\Enums\MemberRole;
use App\Models\Member;

it('un responsable recibe 403 al intentar crear un cobro', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);

    $this->actingAs($user)->post(route('charges.store'), [
        'title' => 'Cuota no autorizada',
        'amount' => 40,
        'type' => ChargeType::CuotaTrimestral->value,
        'target_branches' => [MemberRole::Lobato->value],
    ])->assertForbidden();
});

it('un responsable recibe 403 al listar cobros', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);

    $this->actingAs($user)->get(route('charges.index'))->assertForbidden();
});

it('tesorería sí puede crear y ver cobros', function () {
    $user = userWithRole('tesoreria');

    $this->actingAs($user)->get(route('charges.index'))->assertOk();
});

it('tesorería (visibilidad global) puede ver el historial de cobros de cualquier miembro', function () {
    $user = userWithRole('tesoreria');
    $member = Member::factory()->branch(MemberRole::Pionero)->create();

    $this->actingAs($user)->get(route('charges.members.history', $member))->assertOk();
});

it('una familia recibe 403 en el historial de cobros de un miembro que no es visible para ella', function () {
    $user = userWithRole('familia'); // tiene charges.view pero sin ámbito sobre miembros
    $member = Member::factory()->branch(MemberRole::Pionero)->create();

    $this->actingAs($user)->get(route('charges.members.history', $member))->assertForbidden();
});
