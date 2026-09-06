<?php

use App\Enums\ChargeStatus;
use App\Enums\FamilyRelationship;
use App\Enums\MemberRole;
use App\Models\Charge;
use App\Models\Family;
use App\Models\Member;
use App\Models\User;

/**
 * Crea una cuenta "familia" ligada a una familia con un scout en la rama dada.
 *
 * @return array{0: User, 1: Member}
 */
function familiaConHijo(MemberRole $branch = MemberRole::Castor): array
{
    $user = userWithRole('familia');
    $family = Family::factory()->create();
    $child = Member::factory()->branch($branch)->create();
    $family->members()->attach($child->id, ['relationship' => FamilyRelationship::Hermano->value]);
    $family->users()->attach($user->id);

    return [$user, $child];
}

it('una familia entra a su portal', function () {
    [$user] = familiaConHijo();

    $this->actingAs($user)->get('/portal')->assertOk();
});

it('el personal de gestión no entra al portal', function () {
    $user = userWithRole('secretaria');

    $this->actingAs($user)->get('/portal')->assertRedirect('/dashboard');
});

it('una familia que va al panel de gestión es redirigida al portal', function () {
    [$user] = familiaConHijo();

    $this->actingAs($user)->get('/dashboard')->assertRedirect('/portal');
});

it('una familia solo ve los pagos de sus hijos', function () {
    [$user, $child] = familiaConHijo();
    $otro = Member::factory()->branch(MemberRole::Lobato)->create();

    $charge = Charge::factory()->create(['title' => 'Cuota anual', 'amount' => 60]);
    $charge->members()->attach($child->id, ['amount' => 60, 'status' => ChargeStatus::Pending->value]);
    $charge->members()->attach($otro->id, ['amount' => 60, 'status' => ChargeStatus::Pending->value]);

    $this->actingAs($user)->get('/portal/pagos')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Portal/Payments')
            ->where('pendingTotal', 60)
            ->has('pending', 1));
});

it('una familia no puede ver la ficha de un scout ajeno', function () {
    [$user] = familiaConHijo();
    $ajeno = Member::factory()->branch(MemberRole::Pionero)->create();

    $this->actingAs($user)->get("/portal/scouts/{$ajeno->id}")->assertNotFound();
});

it('una familia no puede acceder a datos sensibles de miembros', function () {
    [$user, $child] = familiaConHijo();

    expect($user->can('viewSensitive', $child))->toBeFalse()
        ->and($user->can('update', $child))->toBeFalse();
});
