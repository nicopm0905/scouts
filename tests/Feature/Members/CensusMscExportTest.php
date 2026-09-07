<?php

use App\Enums\LeaderQualification;
use App\Enums\MemberRole;
use App\Models\LeaderProfile;
use App\Models\Member;

it('exporta el censo MSC con sección, cargo y datos identificativos', function () {
    $user = userWithRole('secretaria');

    Member::factory()->branch(MemberRole::Lobato)->create([
        'first_name' => 'Ana', 'last_name' => 'García Ruiz',
        'dni' => '12345678Z', 'sex' => 'F', 'address' => 'C. Larga 1',
    ]);

    $leader = Member::factory()->leader()->create(['first_name' => 'Luis', 'last_name' => 'Pérez']);
    LeaderProfile::factory()->create([
        'member_id' => $leader->id,
        'qualification' => LeaderQualification::Director,
        'sexual_offenses_certificate_date' => now()->subMonths(3),
        'sexual_offenses_certificate_expires_at' => now()->addYear(),
    ]);

    $res = $this->actingAs($user)->get(route('members.census-msc'));

    $res->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $csv = $res->getContent();

    expect($csv)->toContain('seccion,cargo,apellidos,nombre,dni,sexo')
        ->and($csv)->toContain('Lobato,Educando,"García Ruiz",Ana,12345678Z,Mujer')
        ->and($csv)->toContain('"Scouter / Responsable",Pérez,Luis')
        ->and($csv)->toContain('Director,si,si');
});

it('una familia no puede descargar el censo MSC', function () {
    $user = userWithRole('familia');

    $this->actingAs($user)->get(route('members.census-msc'))->assertForbidden();
});

it('un responsable solo exporta el censo MSC de sus ramas', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    Member::factory()->branch(MemberRole::Lobato)->create(['last_name' => 'DeLobatos']);
    Member::factory()->branch(MemberRole::Pionero)->create(['last_name' => 'DePioneros']);

    $res = $this->actingAs($user)->get(route('members.census-msc'));
    $csv = $res->getContent();

    expect($csv)->toContain('DeLobatos')
        ->and($csv)->not->toContain('DePioneros');
});

it('guarda los campos de censo al editar un miembro', function () {
    $user = userWithRole('secretaria');
    $member = Member::factory()->branch(MemberRole::Lobato)->create();

    $this->actingAs($user)->put(route('members.update', $member), [
        'first_name' => $member->first_name,
        'last_name' => $member->last_name,
        'role' => $member->role->value,
        'dni' => '99999999R',
        'sex' => 'M',
        'address' => 'Av. Principal 5',
        'active' => true,
    ])->assertSessionHasNoErrors();

    expect($member->fresh())
        ->dni->toBe('99999999R')
        ->sex->toBe('M')
        ->address->toBe('Av. Principal 5');
});
