<?php

use App\Enums\ChargeType;
use App\Enums\MemberRole;

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
