<?php

use App\Enums\ChargeStatus;
use App\Enums\PaymentMethod;
use App\Models\Charge;
use App\Models\ChargeMember;
use App\Models\Member;

it('marcar pagado en un clic actualiza el estado, la fecha y el método de pago', function () {
    $user = userWithRole('tesoreria');
    $charge = Charge::factory()->create();
    $member = Member::factory()->create();

    $assignment = ChargeMember::create([
        'charge_id' => $charge->id,
        'member_id' => $member->id,
        'amount' => $charge->amount,
        'status' => ChargeStatus::Pending,
    ]);

    $this->actingAs($user)->post(route('charges.members.mark-paid', $assignment), [
        'payment_method' => PaymentMethod::Bizum->value,
    ])->assertSessionHasNoErrors();

    $assignment->refresh();

    expect($assignment->status)->toBe(ChargeStatus::Paid)
        ->and($assignment->payment_method)->toBe(PaymentMethod::Bizum)
        ->and($assignment->paid_at)->not->toBeNull();
});
