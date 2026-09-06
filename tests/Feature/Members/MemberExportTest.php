<?php

use App\Enums\FamilyRelationship;
use App\Enums\MemberRole;
use App\Models\Family;
use App\Models\Member;

it('exporta el censo en CSV con datos de familia y sin datos médicos', function () {
    $user = userWithRole('secretaria');

    $member = Member::factory()->branch(MemberRole::Lobato)->create([
        'first_name' => 'Ana',
        'last_name' => 'García Pérez',
        'birth_date' => '2016-04-01',
    ]);

    $family = Family::create(['name' => 'Familia García', 'contact_phone' => '600999888', 'contact_email' => 'garcia@example.com']);
    $member->families()->attach($family->id, ['relationship' => FamilyRelationship::Madre->value]);

    $response = $this->actingAs($user)->get(route('members.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $csv = $response->getContent();
    expect($csv)->toContain('nombre,apellidos,rama,fecha_nacimiento,familia,telefono_familia,email_familia,activo,fecha_alta')
        ->toContain('Ana')
        ->toContain('"García Pérez"')
        ->toContain('"Familia García"')
        ->toContain('600999888')
        ->not->toContain('alergia')
        ->not->toContain('medication');
});

it('el export CSV respeta la visibilidad por rama del responsable', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);

    Member::factory()->branch(MemberRole::Lobato)->create(['first_name' => 'VisibleLobato']);
    Member::factory()->branch(MemberRole::Pionero)->create(['first_name' => 'OcultoPionero']);

    $csv = $this->actingAs($user)->get(route('members.export'))->assertOk()->getContent();

    expect($csv)->toContain('VisibleLobato')->not->toContain('OcultoPionero');
});

it('familia no puede exportar el censo', function () {
    $user = userWithRole('familia');

    $this->actingAs($user)->get(route('members.export'))->assertForbidden();
});
