<?php

use App\Enums\MemberRole;
use App\Enums\ObjectiveStatus;
use App\Models\BranchPlan;
use App\Models\BranchPlanObjective;

it('un responsable crea un plan de rama para su propia rama', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);

    $response = $this->actingAs($user)->post(route('branch-plans.store'), [
        'branch' => MemberRole::Lobato->value,
        'school_year' => '2026-2027',
        'description' => 'Plan anual de la manada.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('branch_plans', [
        'branch' => MemberRole::Lobato->value,
        'school_year' => '2026-2027',
    ]);
});

it('lista los planes de rama visibles para el usuario', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    BranchPlan::factory()->create(['branch' => MemberRole::Lobato]);
    BranchPlan::factory()->create(['branch' => MemberRole::Pionero]);

    $response = $this->actingAs($user)->get(route('branch-plans.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('BranchPlans/Index')
        ->has('plans', 1));
});

it('actualiza un plan de rama y sus objetivos, calculando el % de progreso', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Lobato]);

    $this->actingAs($user)->put(route('branch-plans.update', $plan), [
        'branch' => MemberRole::Lobato->value,
        'school_year' => '2027-2028',
        'description' => 'Actualizado',
    ])->assertRedirect();

    $this->assertDatabaseHas('branch_plans', ['id' => $plan->id, 'school_year' => '2027-2028']);

    // Sin objetivos, el progreso es 0%.
    expect($plan->fresh()->completionPercentage())->toBe(0);

    // Añadimos objetivos vía el endpoint.
    $this->actingAs($user)->post(route('branch-plans.objectives.store', $plan), [
        'description' => 'Aprender el nudo llano',
        'term' => 1,
        'status' => ObjectiveStatus::Logrado->value,
    ])->assertRedirect();

    $this->actingAs($user)->post(route('branch-plans.objectives.store', $plan), [
        'description' => 'Organizar una gran acampada',
        'term' => 2,
        'status' => ObjectiveStatus::Pendiente->value,
    ])->assertRedirect();

    expect($plan->fresh()->completionPercentage())->toBe(50);

    $objective = $plan->objectives()->where('status', ObjectiveStatus::Pendiente->value)->first();

    $this->actingAs($user)->put(route('branch-plans.objectives.update', $objective), [
        'description' => $objective->description,
        'term' => $objective->term,
        'status' => ObjectiveStatus::Logrado->value,
    ])->assertRedirect();

    expect($plan->fresh()->completionPercentage())->toBe(100);
});

it('elimina un objetivo del plan de rama', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Lobato]);
    $objective = BranchPlanObjective::factory()->create(['branch_plan_id' => $plan->id]);

    $this->actingAs($user)->delete(route('branch-plans.objectives.destroy', $objective))
        ->assertRedirect();

    $this->assertDatabaseMissing('branch_plan_objectives', ['id' => $objective->id]);
});

it('elimina un plan de rama completo', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Lobato]);

    $this->actingAs($user)->delete(route('branch-plans.destroy', $plan))
        ->assertRedirect(route('branch-plans.index'));

    $this->assertDatabaseMissing('branch_plans', ['id' => $plan->id]);
});
