<?php

use App\Enums\ChargeStatus;
use App\Enums\InvoiceCategory;
use App\Enums\InvoiceDirection;
use App\Models\Charge;
use App\Models\ChargeMember;
use App\Models\Invoice;
use App\Models\Member;

it('el informe económico exporta un CSV con ingresos, gastos y balance', function () {
    $user = userWithRole('tesoreria');

    $charge = Charge::factory()->create(['amount' => 40]);
    $member = Member::factory()->create();
    ChargeMember::create([
        'charge_id' => $charge->id,
        'member_id' => $member->id,
        'amount' => 40,
        'status' => ChargeStatus::Paid,
        'paid_at' => now(),
    ]);

    Invoice::factory()->create([
        'direction' => InvoiceDirection::Received->value,
        'category' => InvoiceCategory::Material->value,
        'amount' => 25,
        'vat' => 0,
        'date' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('finance.report.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $csv = $response->getContent();

    expect($csv)->toContain('Ingreso')
        ->and($csv)->toContain('Gasto')
        ->and($csv)->toContain('Total ingresos')
        ->and($csv)->toContain('Total gastos');
});

it('la página del informe económico calcula el balance correctamente', function () {
    $user = userWithRole('tesoreria');

    $charge = Charge::factory()->create(['amount' => 40]);
    $member = Member::factory()->create();
    ChargeMember::create([
        'charge_id' => $charge->id,
        'member_id' => $member->id,
        'amount' => 40,
        'status' => ChargeStatus::Paid,
        'paid_at' => now(),
    ]);

    Invoice::factory()->create([
        'direction' => InvoiceDirection::Received->value,
        'category' => InvoiceCategory::Material->value,
        'amount' => 25,
        'vat' => 0,
        'date' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('finance.report'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('report.total_income', 40)
        ->where('report.total_expense', 25)
        ->where('report.balance', 15)
    );
});
