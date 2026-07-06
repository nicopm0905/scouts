<?php

use App\Enums\MemberRole;
use App\Models\Activity;
use App\Models\BranchPlan;
use App\Models\BranchPlanObjective;
use App\Models\Event;

it('vincula una actividad a un objetivo del plan de rama', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $activity = Activity::factory()->create();
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Lobato]);
    $objective = BranchPlanObjective::factory()->create(['branch_plan_id' => $plan->id]);

    $this->actingAs($user)->post(route('activities.objectives.attach', [$activity, $objective]))
        ->assertRedirect();

    expect($activity->objectives()->pluck('branch_plan_objective_id'))->toContain($objective->id);

    $this->actingAs($user)->delete(route('activities.objectives.detach', [$activity, $objective]))
        ->assertRedirect();

    expect($activity->objectives()->pluck('branch_plan_objective_id'))->not->toContain($objective->id);
});

it('programa una actividad en un evento del calendario', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $activity = Activity::factory()->create();
    $event = Event::factory()->create(['branches' => [MemberRole::Lobato->value]]);

    $this->actingAs($user)->post(route('activities.events.attach', [$activity, $event]))
        ->assertRedirect();

    expect($activity->events()->pluck('events.id'))->toContain($event->id);

    $this->actingAs($user)->delete(route('activities.events.detach', [$activity, $event]))
        ->assertRedirect();

    expect($activity->events()->pluck('events.id'))->not->toContain($event->id);
});
