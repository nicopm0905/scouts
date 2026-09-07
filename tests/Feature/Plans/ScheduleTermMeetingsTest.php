<?php

use App\Enums\EventType;
use App\Enums\MemberRole;
use App\Models\BranchPlan;
use App\Models\Event;

it('genera las reuniones semanales del tramo para la rama del plan', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Lobato]);

    // Del lunes 2026-09-07 al domingo 2026-10-04 → 4 sábados (12, 19, 26 sep, 3 oct).
    $this->actingAs($user)->post(route('branch-plans.meetings.store', $plan), [
        'weekday' => 6, // sábado (ISO)
        'start_date' => '2026-09-07',
        'end_date' => '2026-10-04',
        'time' => '17:30',
        'duration_minutes' => 90,
        'location' => 'Local del grupo',
    ])->assertRedirect(route('events.index', ['branch' => 'lobato']));

    $reuniones = Event::where('type', EventType::Reunion->value)->get();
    expect($reuniones)->toHaveCount(4);

    $first = $reuniones->sortBy('start_at')->first();
    expect($first->start_at->toDateString())->toBe('2026-09-12')
        ->and($first->start_at->format('H:i'))->toBe('17:30')
        ->and($first->end_at->format('H:i'))->toBe('19:00')
        ->and($first->branches)->toBe(['lobato'])
        ->and($first->location)->toBe('Local del grupo');
});

it('no duplica reuniones que ya existen ese día para la rama y respeta skip_dates', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Lobato]);

    Event::factory()->create([
        'type' => EventType::Reunion,
        'start_at' => '2026-09-12 10:00:00',
        'branches' => ['lobato'],
    ]);

    $this->actingAs($user)->post(route('branch-plans.meetings.store', $plan), [
        'weekday' => 6,
        'start_date' => '2026-09-07',
        'end_date' => '2026-10-04',
        'time' => '17:30',
        'duration_minutes' => 90,
        'skip_dates' => ['2026-09-19'],
    ])->assertRedirect();

    // 4 sábados − 1 ya existente − 1 excluido = 2 nuevas (26 sep, 3 oct). +1 preexistente = 3.
    expect(Event::where('type', EventType::Reunion->value)->count())->toBe(3);
});

it('un responsable de otra rama no puede programar reuniones de este plan', function () {
    $user = userWithRole('responsable', [MemberRole::Pionero->value]);
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Lobato]);

    $this->actingAs($user)->post(route('branch-plans.meetings.store', $plan), [
        'weekday' => 6,
        'start_date' => '2026-09-07',
        'end_date' => '2026-10-04',
        'time' => '17:30',
        'duration_minutes' => 90,
    ])->assertForbidden();

    expect(Event::count())->toBe(0);
});

it('valida el rango de fechas y la hora', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Lobato]);

    $this->actingAs($user)->post(route('branch-plans.meetings.store', $plan), [
        'weekday' => 6,
        'start_date' => '2026-10-04',
        'end_date' => '2026-09-07',
        'time' => '25:99',
        'duration_minutes' => 90,
    ])->assertSessionHasErrors(['end_date', 'time']);
});
