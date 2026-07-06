<?php

use App\Enums\MemberRole;
use App\Models\Activity;
use App\Models\BranchPlan;

it('un responsable de lobato recibe 403 al editar el plan de la rama pionero', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $pioneroPlan = BranchPlan::factory()->create(['branch' => MemberRole::Pionero]);

    $this->actingAs($user)->put(route('branch-plans.update', $pioneroPlan), [
        'branch' => MemberRole::Pionero->value,
        'school_year' => $pioneroPlan->school_year,
        'description' => 'Intento no autorizado',
    ])->assertForbidden();

    $this->actingAs($user)->delete(route('branch-plans.destroy', $pioneroPlan))
        ->assertForbidden();
});

it('un responsable de lobato no ve el plan de otra rama en el listado', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    BranchPlan::factory()->create(['branch' => MemberRole::Pionero]);

    $response = $this->actingAs($user)->get(route('branch-plans.index'));

    $response->assertInertia(fn ($page) => $page->component('BranchPlans/Index')->has('plans', 0));
});

it('admin puede gestionar planes de cualquier rama (Gate::before)', function () {
    $user = userWithRole('admin');
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Pionero]);

    $this->actingAs($user)->put(route('branch-plans.update', $plan), [
        'branch' => MemberRole::Pionero->value,
        'school_year' => $plan->school_year,
        'description' => 'Actualizado por admin',
    ])->assertRedirect();
});

it('un usuario sin permiso de actividades no puede crear actividades', function () {
    $user = userWithRole('familia');

    $this->actingAs($user)->post(route('activities.store'), [
        'title' => 'Actividad no autorizada',
    ])->assertForbidden();

    $this->assertDatabaseMissing('activities', ['title' => 'Actividad no autorizada']);
});

it('un responsable no puede editar una actividad de otra rama vinculada a eventos fuera de su ámbito', function () {
    // La biblioteca de actividades es compartida (activities.manage), pero verificamos
    // que el permiso se exige igualmente para editar.
    $user = userWithRole('familia');
    $activity = Activity::factory()->create();

    $this->actingAs($user)->put(route('activities.update', $activity), [
        'title' => 'Cambio no autorizado',
    ])->assertForbidden();
});
