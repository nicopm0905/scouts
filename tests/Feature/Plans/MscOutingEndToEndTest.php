<?php

use App\Enums\ActivityType;
use App\Enums\EventType;
use App\Enums\MemberRole;
use App\Enums\MscScope;
use App\Models\Activity;
use App\Models\BranchPlan;
use App\Models\BranchPlanObjective;
use App\Models\Event;
use App\Services\Events\MscOutingSheet;

/**
 * Recorre el camino real que hace el kraal: crear el plan y sus objetivos, crear
 * actividades, vincularlas a un objetivo y al evento, y descargar la ficha. Si
 * algo de esa cadena se rompe, la ficha sale en blanco y aquí se ve.
 */
it('rellena la ficha de salida con lo que se enlaza desde la web', function () {
    $user = userWithRole('responsable', [MemberRole::Ranger->value]);

    $plan = BranchPlan::factory()->create([
        'branch' => MemberRole::Ranger,
        'school_year' => '2025-2026',
    ]);

    // 1. El objetivo se crea desde la pantalla del plan de rama.
    $this->actingAs($user)->post(route('branch-plans.objectives.store', $plan), [
        'scope' => MscScope::Responsabilidad->value,
        'line' => 'Destreza',
        'content' => 'Imaginación, ingenio y creatividad',
        'goal_verb' => 'Desarrollar',
        'goal_complement' => 'la creatividad en nuevas actividades',
        'term' => 1,
    ])->assertRedirect();

    $objective = BranchPlanObjective::firstOrFail();

    // 2. La actividad se crea desde la biblioteca de actividades.
    $this->actingAs($user)->post(route('activities.store'), [
        'title' => 'Plan de Rama',
        'branch' => MemberRole::Ranger->value,
        'activity_type' => ActivityType::Dinamica->value,
        'owner' => 'Patrullas',
        'day_number' => 'Sábado',
        'time_slot' => 'manana',
        'activity_number' => 1,
        'development' => 'Por patrullas recibirán una línea a trabajar.',
    ])->assertRedirect();

    $activity = Activity::firstOrFail();

    $event = Event::factory()->create([
        'type' => EventType::Acampada,
        'branches' => [MemberRole::Ranger->value],
        'start_at' => '2025-09-26 10:00:00',
        'end_at' => '2025-09-28 14:00:00',
    ]);

    // 3. Se vincula la actividad al objetivo y al evento.
    $this->actingAs($user)
        ->post(route('activities.objectives.attach', [$activity, $objective]))
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('activities.events.attach', [$activity, $event]))
        ->assertRedirect();

    // 4. La ficha tiene que salir con los datos, no en blanco.
    $sheet = app(MscOutingSheet::class)->for($event->fresh());

    $responsabilidad = collect($sheet['scopes'])->firstWhere('key', 'responsabilidad');

    expect($responsabilidad['rows'])->toHaveCount(1)
        ->and($responsabilidad['rows'][0])->toMatchArray([
            'content' => 'Imaginación, ingenio y creatividad',
            'goal' => 'Desarrollar la creatividad en nuevas actividades',
            'activity' => 'Plan de Rama',
            'number' => 1,
        ]);

    // El sábado es la segunda columna de la rejilla (viernes, sábado, domingo).
    $manana = collect($sheet['structure']['rows'])->firstWhere('label', 'MAÑANA');

    expect($manana['cells'][1])->toHaveCount(1)
        ->and($manana['cells'][1][0]['title'])->toBe('Plan de Rama');
});

it('coloca la actividad aunque el día se escriba de otra forma', function () {
    $event = Event::factory()->create([
        'type' => EventType::Acampada,
        'branches' => [MemberRole::Ranger->value],
        'start_at' => '2025-09-26 10:00:00',
        'end_at' => '2025-09-28 14:00:00',
    ]);

    // Cuatro maneras de apuntar el sábado, todas válidas en el papel.
    foreach (['Sabado', 'SÁBADO', 'Día 2', '27/09/2025'] as $i => $written) {
        $activity = Activity::factory()->create([
            'title' => 'Actividad '.$i,
            'day_number' => $written,
            'time_slot' => 'manana',
            'activity_number' => $i + 1,
        ]);
        $event->activities()->attach($activity->id);
    }

    $sheet = app(MscOutingSheet::class)->for($event->fresh());
    $manana = collect($sheet['structure']['rows'])->firstWhere('label', 'MAÑANA');

    // Las cuatro caen en la columna del sábado, que es la segunda.
    expect($manana['cells'][1])->toHaveCount(4)
        ->and($manana['cells'][0])->toBeEmpty();
});

it('avisa de las actividades que no pueden colocarse en la rejilla', function () {
    $event = Event::factory()->create([
        'type' => EventType::Acampada,
        'branches' => [MemberRole::Ranger->value],
        'start_at' => '2025-09-26 10:00:00',
        'end_at' => '2025-09-28 14:00:00',
    ]);

    $sinFranja = Activity::factory()->create(['title' => 'Velada sorpresa', 'day_number' => 'Sábado', 'time_slot' => null]);
    $event->activities()->attach($sinFranja->id);

    $sheet = app(MscOutingSheet::class)->for($event->fresh());

    // No se pierde: se lista aparte para que el kraal sepa que le falta la franja.
    expect($sheet['unplaced'])->toHaveCount(1)
        ->and($sheet['unplaced'][0]['title'])->toBe('Velada sorpresa');
});

it('la ficha del evento lista las actividades enlazadas y avisa de lo que falta', function () {
    $user = userWithRole('responsable', [MemberRole::Ranger->value]);

    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Ranger, 'school_year' => '2025-2026']);
    $objective = BranchPlanObjective::factory()->for($plan)->create([
        'scope' => MscScope::Fe,
        'line' => 'Oración',
        'content' => 'Fe en Dios, Palabra, Experiencia de Dios',
        'goal_verb' => 'Aprender',
        'goal_complement' => 'diferentes oraciones',
        'term' => 1,
    ]);

    $event = Event::factory()->create([
        'type' => EventType::Acampada,
        'branches' => [MemberRole::Ranger->value],
        'start_at' => '2025-09-26 10:00:00',
        'end_at' => '2025-09-28 14:00:00',
    ]);

    $completa = Activity::factory()->create([
        'title' => 'Dinámica de Fe',
        'branch' => MemberRole::Ranger->value,
        'day_number' => 'Sábado',
        'time_slot' => 'noche',
        'activity_number' => 4,
    ]);
    $completa->objectives()->attach($objective->id);

    $incompleta = Activity::factory()->create([
        'title' => 'Velada sorpresa',
        'branch' => MemberRole::Ranger->value,
        'day_number' => null,
        'time_slot' => null,
        'activity_number' => null,
    ]);

    $event->activities()->attach([$completa->id, $incompleta->id]);

    // Una actividad de la biblioteca que todavía no está en el evento.
    Activity::factory()->create(['title' => 'Trivial Scout', 'branch' => MemberRole::Ranger->value]);

    $response = $this->actingAs($user)->get(route('events.show', $event))->assertOk();

    $props = $response->viewData('page')['props'];
    $rows = collect($props['activities'])->keyBy('title');

    // El ámbito y el objetivo del plan se ven junto a la actividad.
    expect($rows['Dinámica de Fe']['objectives'][0])->toMatchArray([
        'scope_label' => 'Fe',
        'goal' => 'Aprender diferentes oraciones',
        'content' => 'Fe en Dios, Palabra, Experiencia de Dios',
    ]);
    expect($rows['Dinámica de Fe']['missing'])->toBe([]);

    // La incompleta dice exactamente qué le falta para salir en la ficha.
    expect($rows['Velada sorpresa']['missing'])
        ->toBe(['objetivo del plan de rama', 'franja horaria', 'día', 'número']);

    expect($props['mscReadiness'])->toMatchArray([
        'activities' => 2,
        'with_objective' => 1,
        'without_slot' => 1,
        'scopes_covered' => 1,
    ]);

    // Y hay algo que enlazar desde el propio evento, sin ir a la biblioteca.
    expect($props['availableActivities'])->toHaveCount(1)
        ->and($props['availableActivities'][0]['title'])->toBe('Trivial Scout');
});
