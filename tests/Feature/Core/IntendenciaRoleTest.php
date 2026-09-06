<?php

use App\Models\InventoryItem;
use App\Models\Member;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

it('intendencia gestiona inventario', function () {
    $user = userWithRole('intendencia');
    $item = InventoryItem::factory()->create();

    expect($user->can('create', InventoryItem::class))->toBeTrue()
        ->and($user->can('update', $item))->toBeTrue()
        ->and($user->can('reserve', $item))->toBeTrue();
});

it('intendencia no gestiona miembros ni cobros', function () {
    $user = userWithRole('intendencia');
    $member = Member::factory()->create();

    expect($user->can('viewAny', Member::class))->toBeFalse()
        ->and($user->can('update', $member))->toBeFalse()
        ->and($user->can('charges.view'))->toBeFalse();
});

it('el seeder de roles y permisos es idempotente', function () {
    (new RolesAndPermissionsSeeder)->run();
    (new RolesAndPermissionsSeeder)->run();

    expect(Role::where('name', 'intendencia')->count())->toBe(1);
});
