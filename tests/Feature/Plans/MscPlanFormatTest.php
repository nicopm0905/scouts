<?php

use App\Enums\ActivityType;
use App\Enums\EventType;
use App\Enums\LeaderQualification;
use App\Enums\MemberRole;
use App\Enums\MscScope;
use App\Models\Activity;
use App\Models\BranchPlan;
use App\Models\BranchPlanObjective;
use App\Models\Event;
use App\Models\LeaderProfile;
use App\Models\Member;
use App\Services\Events\MscOutingSheet;
use App\Services\Plans\TermPlanSheet;
use Illuminate\Support\Str;

it('compone el objetivo con el verbo y el complemento del impreso MSC', function () {
    $user = userWithRole('responsable', [MemberRole::Ranger->value]);
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Ranger, 'school_year' => '2025-2026']);

    $this->actingAs($user)->post(route('branch-plans.objectives.store', $plan), [
        'scope' => MscScope::Responsabilidad->value,
        'line' => 'Habilidades sociales',
        'content' => 'Convivencia, cooperación, confianza, trabajo en grupo',
        'current_situation' => 'Necesitamos más confianza entre todos los miembros de la unidad.',
        'goal_verb' => 'Mejorar',
        'goal_complement' => 'la confianza entre los miembros de la unidad',
        'term' => 3,
    ])->assertRedirect();

    $objective = BranchPlanObjective::firstOrFail();

    expect($objective->scope)->toBe(MscScope::Responsabilidad)
        ->and($objective->line)->toBe('Habilidades sociales')
        ->and($objective->description)->toBe('Mejorar la confianza entre los miembros de la unidad')
        ->and($objective->goalText())->toBe('Mejorar la confianza entre los miembros de la unidad');
});

it('arma la hoja del trimestre con la tabla de ámbitos, el calendario y las fichas', function () {
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Ranger, 'school_year' => '2025-2026']);

    // Se crean en orden inverso al del impreso para comprobar que se reordenan.
    $fe = BranchPlanObjective::factory()->for($plan)->create([
        'scope' => MscScope::Fe,
        'line' => 'Oración',
        'content' => 'Fe en Dios, Palabra, Experiencia de Dios',
        'goal_verb' => 'Aprender',
        'goal_complement' => 'diferentes oraciones',
        'term' => 3,
        'position' => 0,
    ]);

    $responsabilidad = BranchPlanObjective::factory()->for($plan)->create([
        'scope' => MscScope::Responsabilidad,
        'line' => 'Habilidades sociales',
        'content' => 'Convivencia, cooperación, confianza, trabajo en grupo',
        'current_situation' => 'Falta confianza en la unidad.',
        'goal_verb' => 'Mejorar',
        'goal_complement' => 'la confianza entre los miembros de la unidad',
        'evaluation' => 'Creamos el saludo, pero no se llevó a cabo.',
        'term' => 3,
        'position' => 0,
    ]);

    $activity = Activity::factory()->create([
        'title' => 'Creamos nuestro saludo secreto',
        'activity_type' => ActivityType::Dinamica,
        'owner' => 'Patrullas',
        'scheduled_date' => '2026-05-23',
        'place' => 'Colegio San José',
        'duration_minutes' => 40,
        'materials_text' => 'No se necesitan',
    ]);
    $activity->objectives()->attach($responsabilidad->id);

    // Una reunión de la rama dentro del tercer trimestre alimenta el calendario.
    Event::factory()->create([
        'title' => 'Reunión de tropa',
        'type' => EventType::Reunion,
        'branches' => [MemberRole::Ranger->value],
        'start_at' => '2026-05-23 17:30:00',
        'end_at' => '2026-05-23 19:00:00',
    ]);

    $sheet = app(TermPlanSheet::class)->for($plan->fresh(), 3);

    expect($sheet['term_label'])->toBe('TERCERO')
        ->and($sheet['school_year'])->toBe('2025-2026')
        ->and($sheet['rows'])->toHaveCount(2)
        // Responsabilidad va siempre antes que Fe, como en el impreso.
        ->and($sheet['rows'][0]['scope_label'])->toBe('Responsabilidad')
        ->and($sheet['rows'][1]['scope_label'])->toBe('Fe')
        ->and($sheet['rows'][0]['goal_verb'])->toBe('Mejorar')
        ->and($sheet['rows'][0]['evaluation'])->toBe('Creamos el saludo, pero no se llevó a cabo.')
        ->and($sheet['rows'][0]['activities'][0])->toMatchArray([
            'type' => 'Dinámica',
            'title' => 'Creamos nuestro saludo secreto',
            'owner' => 'Patrullas',
            'date' => '23/05/26',
        ])
        ->and($sheet['calendar']['columns'])->toHaveCount(1)
        ->and($sheet['calendar']['columns'][0]['day'])->toBe('23')
        ->and($sheet['activity_sheets'])->toHaveCount(1)
        ->and($sheet['activity_sheets'][0])->toMatchArray([
            'title' => 'Creamos nuestro saludo secreto',
            'place' => 'Colegio San José',
            'scope_label' => 'Responsabilidad',
            'line' => 'Habilidades sociales',
            'duration' => '40 min',
            'materials' => 'No se necesitan',
        ]);

    expect($fe->fresh()->goalText())->toBe('Aprender diferentes oraciones');
});

it('descarga la hoja del trimestre en PDF', function () {
    $user = userWithRole('responsable', [MemberRole::Ranger->value]);
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Ranger, 'school_year' => '2025-2026']);

    $response = $this->actingAs($user)->get(route('branch-plans.pdf.term', [$plan, 1]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('rechaza un trimestre que no existe', function () {
    $user = userWithRole('responsable', [MemberRole::Ranger->value]);
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Ranger]);

    $this->actingAs($user)->get(route('branch-plans.pdf.term', [$plan, 7]))->assertNotFound();
});

it('reparte las actividades de la salida por ámbito y por franja horaria', function () {
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Ranger, 'school_year' => '2025-2026']);

    $event = Event::factory()->create([
        'title' => 'Acampada de inauguración',
        'type' => EventType::Acampada,
        'branches' => [MemberRole::Ranger->value],
        'location' => 'Cáritas el Portal',
        'start_at' => '2025-09-26 10:00:00',
        'end_at' => '2025-09-28 14:00:00',
    ]);

    $objectives = collect([
        [MscScope::Responsabilidad, 'Imaginación, ingenio y creatividad', 'Desarrollar', 'la creatividad en nuevas actividades'],
        [MscScope::Pais, 'Hermandad scout', 'Crear', 'nuevos lazos de amistad en la unidad'],
        [MscScope::Fe, 'Evangelio, vida de Jesús y otros personajes bíblicos', 'Conocer', 'parte de la vida de Jesús'],
    ])->map(fn (array $row) => BranchPlanObjective::factory()->for($plan)->create([
        'scope' => $row[0],
        'content' => $row[1],
        'goal_verb' => $row[2],
        'goal_complement' => $row[3],
        'term' => 1,
    ]));

    $planDeRama = Activity::factory()->create([
        'title' => 'Plan de Rama',
        'activity_number' => 1,
        'day_number' => 'SÁBADO',
        'time_slot' => 'manana',
    ]);
    $bingo = Activity::factory()->create([
        'title' => '¡Bingo!',
        'activity_number' => 3,
        'day_number' => 'SÁBADO',
        'time_slot' => 'tarde_2',
    ]);
    $dinamicaFe = Activity::factory()->create([
        'title' => 'Dinámica de Fe',
        'activity_number' => 4,
        'day_number' => 'SÁBADO',
        'time_slot' => 'noche',
    ]);

    $planDeRama->objectives()->attach($objectives[0]->id);
    $bingo->objectives()->attach($objectives[1]->id);
    $dinamicaFe->objectives()->attach($objectives[2]->id);
    $event->activities()->attach([$planDeRama->id, $bingo->id, $dinamicaFe->id]);

    $sheet = app(MscOutingSheet::class)->for($event->fresh());

    expect($sheet['branches'])->toBe(['Ranger'])
        ->and($sheet['school_year'])->toBe('2025-2026')
        ->and($sheet['dates'])->toBe('26/09/25 — 28/09/25')
        ->and($sheet['scopes'])->toHaveCount(3);

    // Cada ámbito lleva su fila y se completa hasta las cuatro del impreso.
    $responsabilidad = collect($sheet['scopes'])->firstWhere('key', 'responsabilidad');
    expect($responsabilidad['label'])->toBe('RESPONSABILIDAD')
        ->and($responsabilidad['rows'])->toHaveCount(1)
        ->and($responsabilidad['blank_rows'])->toBe(3)
        ->and($responsabilidad['rows'][0])->toMatchArray([
            'content' => 'Imaginación, ingenio y creatividad',
            'goal' => 'Desarrollar la creatividad en nuevas actividades',
            'activity' => 'Plan de Rama',
            'number' => 1,
        ]);

    // Tres días de salida: viernes, sábado y domingo.
    expect($sheet['structure']['days'])->toHaveCount(3)
        ->and($sheet['structure']['days'][1]['label'])->toBe('SÁBADO');

    $manana = collect($sheet['structure']['rows'])->firstWhere('label', 'MAÑANA');
    $noche = collect($sheet['structure']['rows'])->firstWhere('label', 'NOCHE');

    expect($manana['from'])->toBe('10')
        ->and($manana['to'])->toBe('14')
        // Columna 0 = viernes (vacía), columna 1 = sábado.
        ->and($manana['cells'][0])->toBeEmpty()
        ->and($manana['cells'][1][0]['title'])->toBe('Plan de Rama')
        ->and($noche['cells'][1][0]['title'])->toBe('Dinámica de Fe');
});

it('cuenta niños y responsables con y sin titulación en la ficha de salida', function () {
    $event = Event::factory()->create([
        'type' => EventType::Acampada,
        'branches' => [MemberRole::Ranger->value],
        'start_at' => now()->addWeek(),
        'end_at' => now()->addWeek()->addDays(2),
    ]);

    $children = Member::factory()->count(3)->create(['role' => MemberRole::Ranger]);
    $director = Member::factory()->create(['role' => MemberRole::Responsable]);
    $sinTitulo = Member::factory()->create(['role' => MemberRole::Responsable]);

    LeaderProfile::factory()->create([
        'member_id' => $director->id,
        'qualification' => LeaderQualification::Director,
    ]);
    LeaderProfile::factory()->create([
        'member_id' => $sinTitulo->id,
        'qualification' => LeaderQualification::None,
    ]);

    foreach ($children->push($director)->push($sinTitulo) as $member) {
        $event->members()->attach($member->id, [
            'enrolled' => true,
            'public_token' => Str::random(32),
        ]);
    }

    $sheet = app(MscOutingSheet::class)->for($event->fresh());

    expect($sheet['children'])->toBe(3)
        ->and($sheet['leaders_trained'])->toBe(1)
        ->and($sheet['leaders_untrained'])->toBe(1);
});

it('descarga la ficha de salida MSC en PDF', function () {
    $user = userWithRole('responsable', [MemberRole::Ranger->value]);
    $event = Event::factory()->create([
        'type' => EventType::Acampada,
        'branches' => [MemberRole::Ranger->value],
        'start_at' => now()->addWeek(),
        'end_at' => now()->addWeek()->addDays(2),
    ]);

    $response = $this->actingAs($user)->get(route('events.pdf.msc-outing', $event));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('la ficha de salida lleva el membrete de la delegación y cuatro filas por ámbito', function () {
    $event = Event::factory()->create([
        'title' => 'Acampada de inauguración',
        'type' => EventType::Acampada,
        'branches' => [MemberRole::Ranger->value],
        'start_at' => '2025-09-26 10:00:00',
        'end_at' => '2025-09-28 14:00:00',
    ]);

    $html = view('pdf.event-msc-outing', [
        'event' => $event,
        'sheet' => app(MscOutingSheet::class)->for($event),
    ])->render();

    expect($html)
        ->toContain('DELEGACIÓN DIOCESANA DEL MOVIMIENTO SCOUT CATÓLICO')
        ->toContain('Asociación inscrita en el registro de Entidades Religiosas')
        ->toContain('DATOS SALIDA')
        ->toContain('PLAN DE RAMA: ÁMBITO RESPONSABILIDAD')
        ->toContain('PLAN DE RAMA: ÁMBITO PAÍS')
        ->toContain('PLAN DE RAMA: ÁMBITO FE')
        ->toContain('ESTRUCTURA')
        // Las cuatro franjas del impreso, con sus horas por defecto.
        ->toContain('MAÑANA')->toContain('TARDE I')->toContain('TARDE II')->toContain('NOCHE');

    // Sin objetivos vinculados, los tres ámbitos salen con sus cuatro filas en blanco.
    expect(substr_count($html, 'Elige uno...'))->toBe(12);
});

it('la hoja del trimestre lleva el membrete y la cabecera del impreso', function () {
    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Ranger, 'school_year' => '2025-2026']);

    $html = view('pdf.branch-plan-term', [
        'sheet' => app(TermPlanSheet::class)->for($plan, 3),
    ])->render();

    expect($html)
        ->toContain('DELEGACIÓN DIOCESANA DEL MOVIMIENTO SCOUT CATÓLICO')
        ->toContain('GRUPO SCOUT')
        ->toContain('TRIMESTRE')
        ->toContain('TERCERO')
        ->toContain('¿Cómo estamos?')
        ->toContain('¿Qué queremos conseguir?')
        ->toContain('¿CÓMO HA SALIDO?')
        // En apaisado el texto legal vertical no se pinta.
        ->not->toContain('Asociación inscrita en el registro de Entidades Religiosas');
});
