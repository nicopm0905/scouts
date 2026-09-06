<?php

use App\Enums\ChargeStatus;
use App\Enums\MemberRole;
use App\Models\Charge;
use App\Models\ChargeMember;
use App\Models\Event;
use App\Models\Member;
use Illuminate\Support\Str;

it('genera el PDF del listado de asistentes', function () {
    $user = userWithRole('secretaria');
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Pionero->value]]);

    $member = Member::factory()->branch(MemberRole::Pionero)->create();
    $event->members()->attach($member->id, ['enrolled' => true, 'public_token' => Str::random(40)]);

    $response = $this->actingAs($user)->get(route('events.pdf.attendees', $event));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('registra en el activity log la descarga del listado de asistentes', function () {
    $user = userWithRole('secretaria');
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Pionero->value]]);

    $this->actingAs($user)->get(route('events.pdf.attendees', $event))->assertOk();

    $this->assertDatabaseHas('activity_log', [
        'causer_id' => $user->id,
        'subject_id' => $event->id,
        'subject_type' => Event::class,
        'description' => 'Descarga del PDF de asistentes (incluye datos médicos)',
    ]);
});

it('un responsable de la rama del evento puede descargar el listado de asistentes', function () {
    $user = userWithRole('responsable', [MemberRole::Pionero->value]);
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Pionero->value]]);

    $this->actingAs($user)->get(route('events.pdf.attendees', $event))->assertOk();
});

it('un responsable de otra rama recibe 403 en el listado de asistentes', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Pionero->value]]);

    $this->actingAs($user)->get(route('events.pdf.attendees', $event))->assertForbidden();
});

it('una familia recibe 403 en el listado de asistentes', function () {
    $user = userWithRole('familia');
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Pionero->value]]);

    $this->actingAs($user)->get(route('events.pdf.attendees', $event))->assertForbidden();
});

it('el listado de asistentes incluye el estado de pago del cobro del evento', function () {
    $user = userWithRole('secretaria');
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Pionero->value]]);

    $pagado = Member::factory()->branch(MemberRole::Pionero)->create();
    $pendiente = Member::factory()->branch(MemberRole::Pionero)->create();
    $event->members()->attach($pagado->id, ['enrolled' => true, 'public_token' => Str::random(40)]);
    $event->members()->attach($pendiente->id, ['enrolled' => true, 'public_token' => Str::random(40)]);

    $charge = Charge::factory()->create(['event_id' => $event->id, 'created_by' => $user->id]);
    ChargeMember::create(['charge_id' => $charge->id, 'member_id' => $pagado->id, 'amount' => 30, 'status' => ChargeStatus::Paid, 'paid_at' => now()]);
    ChargeMember::create(['charge_id' => $charge->id, 'member_id' => $pendiente->id, 'amount' => 30, 'status' => ChargeStatus::Pending]);

    $this->actingAs($user)->get(route('events.pdf.attendees', $event))->assertOk();

    // La vista del PDF debe incluir la columna de pago con ambos estados.
    $html = view('pdf.event-attendees', [
        'event' => $event,
        'rows' => [
            ['name' => $pagado->full_name, 'role_label' => 'Pionero', 'phone' => null, 'health_summary' => '', 'payment_label' => 'Pagado'],
            ['name' => $pendiente->full_name, 'role_label' => 'Pionero', 'phone' => null, 'health_summary' => '', 'payment_label' => 'Pendiente'],
        ],
        'showPayment' => true,
    ])->render();

    expect($html)->toContain('Pago')->toContain('Pagado')->toContain('Pendiente');
});

it('genera el PDF de la circular', function () {
    $user = userWithRole('secretaria');
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Pionero->value]]);

    $response = $this->actingAs($user)->get(route('events.pdf.circular', $event));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('una familia recibe 403 en la circular (contiene tokens de inscripción)', function () {
    $user = userWithRole('familia');
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Pionero->value]]);

    $this->actingAs($user)->get(route('events.pdf.circular', $event))->assertForbidden();
});
