<?php

use App\Enums\ChargeStatus;
use App\Enums\MemberRole;
use App\Models\Charge;
use App\Models\Event;
use App\Models\LeaderProfile;
use App\Models\LeaderTraining;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Testing\TestResponse;

/**
 * Devuelve un aviso del panel por su clave ('charges', 'certificates'…).
 *
 * @return array<string, mixed>|null
 */
function aviso(TestResponse $response, string $key): ?array
{
    $avisos = $response->viewData('page')['props']['attention'] ?? [];

    return collect($avisos)->firstWhere('key', $key);
}

it('el panel carga para un admin con los avisos accionables', function () {
    $admin = userWithRole('admin');

    $charge = Charge::factory()->create();
    $member = Member::factory()->create();
    $charge->members()->attach($member->id, ['amount' => 30, 'status' => ChargeStatus::Pending->value]);
    Event::factory()->create(['start_at' => now()->addWeek()]);

    $response = $this->actingAs($admin)->get(route('dashboard'))->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('attention')
        ->has('agenda')
        ->has('finance')
        ->has('members')
        ->has('today')
    );

    expect(aviso($response, 'charges'))
        ->not->toBeNull()
        ->and(aviso($response, 'charges')['value'])->toBe(1)
        // Cada aviso lleva a la pantalla donde se resuelve.
        ->and(aviso($response, 'charges')['href'])->toBe(route('charges.index'));
});

it('un responsable sin permiso de cobros no recibe datos de tesorería', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);

    $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $response->assertInertia(fn ($page) => $page->component('Dashboard')->where('finance', null));

    expect(aviso($response, 'charges'))->toBeNull();
});

it('el comando de alertas de caducidad se ejecuta', function () {
    $this->artisan('alerts:expiry')->assertSuccessful();
});

it('los avisos incluyen las titulaciones caducadas o por caducar (≤60 días)', function () {
    $admin = userWithRole('admin');

    $profile = LeaderProfile::factory()->create([
        'sexual_offenses_certificate_expires_at' => now()->addYears(2),
    ]);
    LeaderTraining::factory()->create(['leader_profile_id' => $profile->id, 'expires_at' => now()->subDay()]); // caducada
    LeaderTraining::factory()->create(['leader_profile_id' => $profile->id, 'expires_at' => now()->addDays(30)]); // por caducar
    LeaderTraining::factory()->create(['leader_profile_id' => $profile->id, 'expires_at' => now()->addDays(200)]); // fuera de ventana
    LeaderTraining::factory()->create(['leader_profile_id' => $profile->id, 'expires_at' => null]); // sin caducidad

    $response = $this->actingAs($admin)->get(route('dashboard'))->assertOk();

    expect(aviso($response, 'trainings')['value'])->toBe(2);
});

it('un responsable de rama ve las titulaciones por caducar de los monitores de sus ramas', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);

    $inBranch = LeaderProfile::factory()->create([
        'branches' => [MemberRole::Lobato->value],
        'sexual_offenses_certificate_expires_at' => now()->addYears(2),
    ]);
    $otherBranch = LeaderProfile::factory()->create([
        'branches' => [MemberRole::Castor->value],
        'sexual_offenses_certificate_expires_at' => now()->addYears(2),
    ]);
    LeaderTraining::factory()->create(['leader_profile_id' => $inBranch->id, 'expires_at' => now()->addDays(10)]);
    LeaderTraining::factory()->create(['leader_profile_id' => $otherBranch->id, 'expires_at' => now()->addDays(10)]);

    $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect(aviso($response, 'trainings')['value'])->toBe(1);
});

it('un responsable de rama ve los certificados por caducar de los monitores de sus ramas', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);

    LeaderProfile::factory()->create([
        'branches' => [MemberRole::Lobato->value],
        'sexual_offenses_certificate_expires_at' => now()->addDays(10),
    ]);
    LeaderProfile::factory()->create([
        'branches' => [MemberRole::Castor->value],
        'sexual_offenses_certificate_expires_at' => now()->addDays(10),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect(aviso($response, 'certificates')['value'])->toBe(1);
});

it('el censo del panel solo cuenta los miembros que el usuario puede ver', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);

    Member::factory()->count(3)->create(['role' => MemberRole::Lobato->value]);
    Member::factory()->count(2)->create(['role' => MemberRole::Castor->value]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('members.total', 3)
            ->has('members.branches', 1)
            ->where('members.branches.0.key', MemberRole::Lobato->value)
        );
});

it('la tesorería del panel resume lo pendiente y lo cobrado', function () {
    $admin = userWithRole('admin');

    $charge = Charge::factory()->create();
    $pendiente = Member::factory()->create();
    $pagado = Member::factory()->create();
    $charge->members()->attach($pendiente->id, ['amount' => 40, 'status' => ChargeStatus::Pending->value]);
    $charge->members()->attach($pagado->id, ['amount' => 60, 'status' => ChargeStatus::Paid->value]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('finance.pending_amount', 40)
            ->where('finance.paid_amount', 60)
            ->where('finance.paid_ratio', 60)
            ->where('finance.pending_count', 1)
        );
});

it('"Tu semana" trae los eventos de la semana en curso de las ramas del usuario', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);

    $estaSemana = Event::factory()->create([
        'title' => 'Reunión de esta semana',
        'start_at' => now()->startOfWeek(Carbon::MONDAY)->addDays(2)->setTime(17, 30),
        'branches' => [MemberRole::Lobato->value],
    ]);
    // Fuera de la semana o de otra rama: no deben salir.
    Event::factory()->create(['start_at' => now()->addWeeks(2), 'branches' => [MemberRole::Lobato->value]]);
    Event::factory()->create(['start_at' => now()->addDay(), 'branches' => [MemberRole::Pionero->value]]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('week.events', 1)
            ->where('week.events.0.id', $estaSemana->id)
            ->where('week.events.0.weekday', 3));
});
