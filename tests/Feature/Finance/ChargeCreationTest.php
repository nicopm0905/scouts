<?php

use App\Enums\ChargeStatus;
use App\Enums\ChargeType;
use App\Enums\MemberRole;
use App\Models\Charge;
use App\Models\Member;

it('crear un cobro por rama reparte una fila charge_member a cada miembro de esa rama', function () {
    $user = userWithRole('tesoreria');

    $lobatos = Member::factory()->count(3)->branch(MemberRole::Lobato)->create();
    Member::factory()->count(2)->branch(MemberRole::Pionero)->create(); // no deben recibir el cobro

    $response = $this->actingAs($user)->post(route('charges.store'), [
        'title' => 'Cuota trimestral otoño',
        'amount' => 40,
        'type' => ChargeType::CuotaTrimestral->value,
        'target_branches' => [MemberRole::Lobato->value],
        'member_ids' => [],
        'sibling_discount_applies' => false,
    ]);

    $charge = Charge::firstWhere('title', 'Cuota trimestral otoño');

    $response->assertRedirect(route('charges.show', $charge));
    expect($charge)->not->toBeNull();
    expect($charge->assignments()->count())->toBe(3);

    foreach ($lobatos as $lobato) {
        $assignment = $charge->assignments()->where('member_id', $lobato->id)->first();
        expect($assignment)->not->toBeNull()
            ->and((float) $assignment->amount)->toBe(40.0)
            ->and($assignment->status)->toBe(ChargeStatus::Pending);
    }
});

it('crear un cobro por miembros individuales solo reparte a los seleccionados', function () {
    $user = userWithRole('tesoreria');
    $selected = Member::factory()->branch(MemberRole::Ranger)->create();
    Member::factory()->branch(MemberRole::Ranger)->create();

    $this->actingAs($user)->post(route('charges.store'), [
        'title' => 'Salida de otoño',
        'amount' => 15,
        'type' => ChargeType::Salida->value,
        'member_ids' => [$selected->id],
    ])->assertRedirect();

    $charge = Charge::firstWhere('title', 'Salida de otoño');

    expect($charge->assignments()->count())->toBe(1)
        ->and($charge->assignments()->first()->member_id)->toBe($selected->id);
});
