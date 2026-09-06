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
        'settings.finance', // ajustes de tesorería (datos fiscales, descuentos, recordatorios)
        'portal.access',    // puerta única del portal de familias
        'users.manage',     // gestión de cuentas de acceso (solo coordinación)
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
            'inventory.view',
            'settings.finance',
        ],
        'intendencia' => [
            'inventory.view', 'inventory.manage', 'inventory.reserve',
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
        // La familia solo tiene la puerta del portal. Todo lo que ve dentro se
        // acota por parentesco en los controladores de App\Http\Controllers\Portal,
        // no con permisos de módulo (que abrirían las pantallas de gestión).
        'familia' => [
            'portal.access',
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
