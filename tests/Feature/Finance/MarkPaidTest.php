<?php

use App\Enums\ChargeStatus;
use App\Enums\PaymentMethod;
use App\Models\Charge;
use App\Models\ChargeMember;
use App\Models\Member;
use Spatie\Activitylog\Models\Activity;

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

it('markPaid deja rastro en activity_log (trazabilidad RGPD)', function () {
    $charge = Charge::factory()->create();
    $member = Member::factory()->create();

    $assignment = ChargeMember::create([
        'charge_id' => $charge->id,
        'member_id' => $member->id,
        'amount' => $charge->amount,
        'status' => ChargeStatus::Pending,
    ]);

    $assignment->markPaid(PaymentMethod::Efectivo);

    $activity = Activity::query()
        ->where('log_name', 'charge_member')
        ->where('subject_type', ChargeMember::class)
        ->where('subject_id', $assignment->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();

    $properties = $activity->properties->toArray();
    expect($properties['attributes']['status'])->toBe(ChargeStatus::Paid->value)
        ->and($properties['attributes'])->toHaveKey('paid_at')
        ->and($properties['old']['status'])->toBe(ChargeStatus::Pending->value);
});
