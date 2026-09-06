<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

it('coordinación ve la gestión de usuarios', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Admin/Users/Index'));
});

it('el resto de roles no accede a la gestión de usuarios', function () {
    foreach (['secretaria', 'tesoreria', 'intendencia', 'responsable'] as $role) {
        $this->actingAs(userWithRole($role))->get(route('users.index'))->assertForbidden();
    }
});

it('crea una cuenta y genera el enlace de alta sin enviar correo', function () {
    Notification::fake();
    $admin = userWithRole('admin');

    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Nueva Tesorería',
        'email' => 'tesoreria2@grupo.test',
        'role' => 'tesoreria',
    ]);

    $response->assertRedirect()->assertSessionHas('invite_url');

    $user = User::whereEmail('tesoreria2@grupo.test')->firstOrFail();
    expect($user->hasRole('tesoreria'))->toBeTrue()
        ->and($user->active)->toBeTrue();

    // Las invitaciones de acceso no se envían por correo (se entregan a mano).
    Notification::assertNothingSent();
    expect(Str::contains(session('invite_url'), '/reset-password/'))->toBeTrue();
});

it('guarda las ramas solo para responsables', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Resp Castores',
        'email' => 'castores@grupo.test',
        'role' => 'responsable',
        'branches' => ['castor'],
    ]);
    expect(User::whereEmail('castores@grupo.test')->first()->branches)->toBe(['castor']);

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Otra Secretaría',
        'email' => 'secre2@grupo.test',
        'role' => 'secretaria',
        'branches' => ['castor'],
    ]);
    expect(User::whereEmail('secre2@grupo.test')->first()->branches)->toBeNull();
});

it('no permite quedarse sin ningún administrador', function () {
    $admin = userWithRole('admin');

    // Único admin: no puede degradarse ni eliminarse.
    $this->actingAs($admin)->patch(route('users.update', $admin), [
        'name' => $admin->name,
        'role' => 'secretaria',
    ])->assertSessionHas('error');

    $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertSessionHas('error');

    expect($admin->fresh()->hasRole('admin'))->toBeTrue()
        ->and(User::whereKey($admin->id)->exists())->toBeTrue();
});
