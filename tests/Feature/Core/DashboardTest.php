<?php

use App\Enums\ChargeStatus;
use App\Enums\MemberRole;
use App\Models\Charge;
use App\Models\Event;
use App\Models\Member;

it('el dashboard carga para un admin con el semáforo', function () {
    $admin = userWithRole('admin');

    $charge = Charge::factory()->create();
    $member = Member::factory()->create();
    $charge->members()->attach($member->id, ['amount' => 30, 'status' => ChargeStatus::Pending->value]);
    Event::factory()->create(['start_at' => now()->addWeek()]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('semaphore')
            ->has('upcomingEvents')
            ->where('semaphore.pending_charges', 1)
        );
});

it('un responsable no ve cobros en el dashboard (sin permiso charges.view)', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('recentCharges', [])
        );
});

it('el comando de alertas de caducidad se ejecuta', function () {
    $this->artisan('alerts:expiry')->assertSuccessful();
});
