<?php

use App\Enums\ChargeStatus;
use App\Enums\PaymentMethod;
use App\Models\Charge;
use App\Models\ChargeMember;
use App\Models\Member;

function pendingAssignment(Charge $charge): ChargeMember
{
    return ChargeMember::create([
        'charge_id' => $charge->id,
        'member_id' => Member::factory()->create()->id,
        'amount' => $charge->amount,
        'status' => ChargeStatus::Pending,
    ]);
}

it('marca varias filas como pagadas en efectivo de una vez', function () {
    $user = userWithRole('tesoreria');
    $charge = Charge::factory()->create();
    $a = pendingAssignment($charge);
    $b = pendingAssignment($charge);
    $c = pendingAssignment($charge);

    $this->actingAs($user)->post(route('charges.members.bulk-mark-paid', $charge), [
        'assignment_ids' => [$a->id, $b->id],
        'payment_method' => PaymentMethod::Efectivo->value,
    ])->assertSessionHasNoErrors();

    expect($a->refresh()->status)->toBe(ChargeStatus::Paid)
        ->and($a->payment_method)->toBe(PaymentMethod::Efectivo)
        ->and($a->paid_at)->not->toBeNull()
        ->and($b->refresh()->status)->toBe(ChargeStatus::Paid)
        ->and($c->refresh()->status)->toBe(ChargeStatus::Pending);
});

it('ignora filas de otro cobro y las ya pagadas', function () {
    $user = userWithRole('tesoreria');
    $charge = Charge::factory()->create();
    $otherCharge = Charge::factory()->create();

    $mine = pendingAssignment($charge);
    $foreign = pendingAssignment($otherCharge);

    $this->actingAs($user)->post(route('charges.members.bulk-mark-paid', $charge), [
        'assignment_ids' => [$mine->id, $foreign->id],
        'payment_method' => PaymentMethod::Efectivo->value,
    ])->assertSessionHasNoErrors();

    expect($mine->refresh()->status)->toBe(ChargeStatus::Paid)
        ->and($foreign->refresh()->status)->toBe(ChargeStatus::Pending);
});

it('un responsable sin permiso de cobros no puede marcar pagos en bloque', function () {
    $user = userWithRole('responsable', ['lobato']);
    $charge = Charge::factory()->create();
    $a = pendingAssignment($charge);

    $this->actingAs($user)->post(route('charges.members.bulk-mark-paid', $charge), [
        'assignment_ids' => [$a->id],
        'payment_method' => PaymentMethod::Efectivo->value,
    ])->assertForbidden();

    expect($a->refresh()->status)->toBe(ChargeStatus::Pending);
});
