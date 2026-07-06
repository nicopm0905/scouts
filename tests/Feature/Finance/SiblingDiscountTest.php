<?php

use App\Enums\ChargeType;
use App\Enums\MemberRole;
use App\Models\Charge;
use App\Models\Family;
use App\Models\Member;
use App\Models\Setting;

it('aplica el descuento por hermanos a partir del segundo hermano en cuotas', function () {
    Setting::set('finance.sibling_discount_percent', 20);

    $user = userWithRole('tesoreria');
    $family = Family::factory()->create();

    $older = Member::factory()->branch(MemberRole::Pionero)->create(['birth_date' => now()->subYears(15)]);
    $younger = Member::factory()->branch(MemberRole::Ranger)->create(['birth_date' => now()->subYears(12)]);

    $family->members()->attach([$older->id => ['relationship' => 'hijo'], $younger->id => ['relationship' => 'hijo']]);

    $this->actingAs($user)->post(route('charges.store'), [
        'title' => 'Cuota anual',
        'amount' => 100,
        'type' => ChargeType::CuotaAnual->value,
        'sibling_discount_applies' => true,
        'member_ids' => [$older->id, $younger->id],
    ])->assertRedirect();

    $charge = Charge::firstWhere('title', 'Cuota anual');

    $olderAmount = (float) $charge->assignments()->where('member_id', $older->id)->first()->amount;
    $youngerAmount = (float) $charge->assignments()->where('member_id', $younger->id)->first()->amount;

    expect($olderAmount)->toBe(100.0)
        ->and($youngerAmount)->toBe(80.0);
});

it('no aplica descuento si el cobro no lo tiene activado', function () {
    $user = userWithRole('tesoreria');
    $family = Family::factory()->create();

    $older = Member::factory()->branch(MemberRole::Pionero)->create(['birth_date' => now()->subYears(15)]);
    $younger = Member::factory()->branch(MemberRole::Ranger)->create(['birth_date' => now()->subYears(12)]);
    $family->members()->attach([$older->id => ['relationship' => 'hijo'], $younger->id => ['relationship' => 'hijo']]);

    $this->actingAs($user)->post(route('charges.store'), [
        'title' => 'Cuota sin descuento',
        'amount' => 100,
        'type' => ChargeType::CuotaAnual->value,
        'sibling_discount_applies' => false,
        'member_ids' => [$older->id, $younger->id],
    ])->assertRedirect();

    $charge = Charge::firstWhere('title', 'Cuota sin descuento');
    $youngerAmount = (float) $charge->assignments()->where('member_id', $younger->id)->first()->amount;

    expect($youngerAmount)->toBe(100.0);
});
