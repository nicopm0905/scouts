<?php

use App\Enums\MemberRole;
use App\Models\Event;

it('el feed ical de un responsable solo incluye eventos de sus ramas y de grupo', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $user->ensureIcalToken();

    Event::factory()->create(['title' => 'Reunion Lobatos XYZ', 'branches' => [MemberRole::Lobato->value]]);
    Event::factory()->create(['title' => 'Acampada Pioneros XYZ', 'branches' => [MemberRole::Pionero->value]]);
    Event::factory()->create(['title' => 'Evento De Grupo XYZ', 'branches' => []]);

    $response = $this->get(route('public.ical.show', ['token' => $user->ical_token]));

    $response->assertOk();
    $ics = $response->getContent();

    expect($ics)
        ->toContain('Reunion Lobatos XYZ')
        ->toContain('Evento De Grupo XYZ')
        ->not->toContain('Acampada Pioneros XYZ');
});

it('el feed ical de un usuario global incluye todos los eventos', function () {
    $user = userWithRole('secretaria');
    $user->ensureIcalToken();

    Event::factory()->create(['title' => 'Reunion Lobatos XYZ', 'branches' => [MemberRole::Lobato->value]]);
    Event::factory()->create(['title' => 'Acampada Pioneros XYZ', 'branches' => [MemberRole::Pionero->value]]);

    $response = $this->get(route('public.ical.show', ['token' => $user->ical_token]));

    $response->assertOk();
    expect($response->getContent())
        ->toContain('Reunion Lobatos XYZ')
        ->toContain('Acampada Pioneros XYZ');
});

it('el feed ical de un usuario sin ramas asignadas (visibilidad global) incluye todos los eventos', function () {
    $user = userWithRole('familia'); // branches = null
    $user->ensureIcalToken();

    Event::factory()->create(['title' => 'Reunion Lobatos XYZ', 'branches' => [MemberRole::Lobato->value]]);
    Event::factory()->create(['title' => 'Acampada Pioneros XYZ', 'branches' => [MemberRole::Pionero->value]]);

    $response = $this->get(route('public.ical.show', ['token' => $user->ical_token]));

    $response->assertOk();
    expect($response->getContent())
        ->toContain('Reunion Lobatos XYZ')
        ->toContain('Acampada Pioneros XYZ');
});

it('un token inexistente devuelve 404', function () {
    $this->get(route('public.ical.show', ['token' => 'no-existe']))->assertNotFound();
});
