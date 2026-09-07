<?php

use App\Enums\EventType;
use App\Enums\MemberRole;
use App\Enums\ObjectiveStatus;
use App\Models\Attendance;
use App\Models\BranchPlan;
use App\Models\BranchPlanObjective;
use App\Models\Event;
use App\Models\Member;
use App\Services\Reports\AnnualReportService;

it('la memoria resume objetivos, eventos, asistencia y censo por curso', function () {
    $this->travelTo('2027-06-15'); // a final del curso 2026-2027

    $plan = BranchPlan::factory()->create(['branch' => MemberRole::Lobato, 'school_year' => '2026-2027']);
    BranchPlanObjective::factory()->create(['branch_plan_id' => $plan->id, 'status' => ObjectiveStatus::Logrado, 'development_area' => 'Fe']);
    BranchPlanObjective::factory()->create(['branch_plan_id' => $plan->id, 'status' => ObjectiveStatus::Logrado, 'development_area' => 'Fe']);
    BranchPlanObjective::factory()->create(['branch_plan_id' => $plan->id, 'status' => ObjectiveStatus::EnCurso, 'development_area' => 'País']);
    BranchPlanObjective::factory()->create(['branch_plan_id' => $plan->id, 'status' => ObjectiveStatus::Pendiente, 'development_area' => 'País']);

    // Evento ya realizado, dentro del curso 26-27 y de la rama.
    Event::factory()->create(['type' => EventType::Salida, 'start_at' => '2026-11-15 10:00:00', 'branches' => ['lobato']]);
    // Evento del curso anterior: no cuenta.
    Event::factory()->create(['type' => EventType::Salida, 'start_at' => '2025-11-15 10:00:00', 'branches' => ['lobato']]);
    // Evento futuro (aún no realizado): no cuenta.
    Event::factory()->create(['type' => EventType::Salida, 'start_at' => '2027-07-20 10:00:00', 'branches' => ['lobato']]);

    $members = Member::factory()->count(2)->branch(MemberRole::Lobato)->create();
    foreach ($members as $i => $m) {
        Attendance::factory()->create(['member_id' => $m->id, 'event_id' => null, 'date' => '2026-10-04', 'present' => $i === 0]);
    }

    $report = app(AnnualReportService::class)->build('2026-2027');

    expect($report['from'])->toBe('2026-09-01')
        ->and($report['to'])->toBe('2027-08-31')
        ->and($report['events_total'])->toBe(1)
        ->and($report['plans'])->toHaveCount(1);

    $block = $report['plans'][0];
    expect($block['objectives']['total'])->toBe(4)
        ->and($block['objectives']['logrado'])->toBe(2)
        ->and($block['objectives']['percentage'])->toBe(50)
        ->and($block['events_count'])->toBe(1)
        ->and($block['attendance']['sessions'])->toBe(1)
        ->and($block['attendance']['avg_present'])->toBe(50);
});

it('la página de la memoria carga para un responsable, acotada a sus ramas', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    BranchPlan::factory()->create(['branch' => MemberRole::Lobato, 'school_year' => '2026-2027']);
    BranchPlan::factory()->create(['branch' => MemberRole::Pionero, 'school_year' => '2026-2027']);

    $this->actingAs($user)->get(route('reports.annual', ['year' => '2026-2027']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Reports/AnnualReport')->has('report.plans', 1));
});

it('genera el PDF de la memoria', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    BranchPlan::factory()->create(['branch' => MemberRole::Lobato, 'school_year' => '2026-2027']);

    $this->actingAs($user)->get(route('reports.annual.pdf', ['year' => '2026-2027']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('una familia no accede a la memoria', function () {
    $user = userWithRole('familia');

    $this->actingAs($user)->get(route('reports.annual'))->assertForbidden();
});

it('un año con formato inválido cae al curso más reciente disponible', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    BranchPlan::factory()->create(['branch' => MemberRole::Lobato, 'school_year' => '2025-2026']);

    $this->actingAs($user)->get(route('reports.annual', ['year' => 'basura']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('year', fn ($y) => preg_match('/^\d{4}-\d{4}$/', $y) === 1));
});

it('la memoria cuenta las altas y bajas del curso', function () {
    $this->travelTo('2027-06-15');

    // Alta dentro del curso 26-27.
    Member::factory()->branch(MemberRole::Lobato)->create(['joined_at' => '2026-10-01']);
    // Alta de un curso anterior: no cuenta.
    Member::factory()->branch(MemberRole::Lobato)->create(['joined_at' => '2025-10-01']);
    // Baja dentro del curso: se desactiva -> el modelo pone left_at.
    $m = Member::factory()->branch(MemberRole::Lobato)->create(['joined_at' => '2024-09-01', 'active' => true]);
    $m->update(['active' => false]);

    $report = app(AnnualReportService::class)->build('2026-2027');

    expect($report['retention']['altas'])->toBe(1)
        ->and($report['retention']['bajas'])->toBe(1)
        ->and($report['retention']['net'])->toBe(0)
        ->and($m->fresh()->left_at->toDateString())->toBe('2027-06-15');
});

it('reactivar un miembro limpia su fecha de baja', function () {
    $m = Member::factory()->create(['active' => true]);
    $m->update(['active' => false]);
    expect($m->fresh()->left_at)->not->toBeNull();

    $m->update(['active' => true]);
    expect($m->fresh()->left_at)->toBeNull();
});
