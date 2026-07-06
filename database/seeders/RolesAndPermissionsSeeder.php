<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permisos por módulo. Convención: "<recurso>.<accion>".
     * acciones: view (ver/listar), manage (crear/editar/borrar).
     */
    private array $permissions = [
        'members.view', 'members.manage', 'members.sensitive', // sensitive = fichas médicas/consentimientos
        'attendance.manage',
        'charges.view', 'charges.manage',
        'invoices.view', 'invoices.manage',
        'finance.reports',
        'events.view', 'events.manage',
        'documents.view', 'documents.manage',
        'minutes.view', 'minutes.manage',
        'plans.view', 'plans.manage',
        'activities.view', 'activities.manage',
        'inventory.view', 'inventory.manage', 'inventory.reserve',
        'photos.view', 'photos.manage',
        'history.manage',
        'settings.manage',
    ];

    /** Permisos asignados a cada rol (admin recibe todos vía Gate::before). */
    private array $rolePermissions = [
        'secretaria' => [
            'members.view', 'members.manage', 'members.sensitive', 'attendance.manage',
            'events.view', 'events.manage',
            'documents.view', 'documents.manage',
            'minutes.view', 'minutes.manage',
            'photos.view', 'photos.manage', 'history.manage',
            'inventory.view',
        ],
        'tesoreria' => [
            'members.view',
            'charges.view', 'charges.manage',
            'invoices.view', 'invoices.manage',
            'finance.reports',
            'events.view',
        ],
        'responsable' => [
            'members.view', 'attendance.manage',
            'events.view', 'events.manage',
            'plans.view', 'plans.manage',
            'activities.view', 'activities.manage',
            'inventory.view', 'inventory.reserve',
            'photos.view', 'photos.manage',
        ],
        'familia' => [
            'charges.view', 'events.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (UserRole::cases() as $roleEnum) {
            $role = Role::findOrCreate($roleEnum->value, 'web');

            if ($roleEnum === UserRole::Admin) {
                continue; // admin lo resuelve Gate::before
            }

            $role->syncPermissions($this->rolePermissions[$roleEnum->value] ?? []);
        }
    }
}
