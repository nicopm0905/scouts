<?php

use App\Enums\MemberRole;
use App\Models\Member;

it('un responsable puede ver miembros de su rama', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $lobato = Member::factory()->branch(MemberRole::Lobato)->create();

    expect($user->can('view', $lobato))->toBeTrue();
});

it('un responsable NO puede ver miembros de otra rama', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $pionero = Member::factory()->branch(MemberRole::Pionero)->create();

    expect($user->can('view', $pionero))->toBeFalse()
        ->and($user->can('viewSensitive', $pionero))->toBeFalse()
        ->and($user->can('update', $pionero))->toBeFalse();
});

it('secretaría ve miembros de cualquier rama', function () {
    $user = userWithRole('secretaria');
    $pionero = Member::factory()->branch(MemberRole::Pionero)->create();

    expect($user->can('view', $pionero))->toBeTrue()
        ->and($user->can('viewSensitive', $pionero))->toBeTrue();
});

it('admin puede todo (Gate::before)', function () {
    $user = userWithRole('admin');
    $member = Member::factory()->create();

    expect($user->can('view', $member))->toBeTrue()
        ->and($user->can('delete', $member))->toBeTrue();
});

it('tesorería no puede gestionar miembros', function () {
    $user = userWithRole('tesoreria');
    $member = Member::factory()->create();

    expect($user->can('update', $member))->toBeFalse();
});
