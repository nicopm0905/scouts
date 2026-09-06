<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder para entornos reales (Render). Idempotente: se puede lanzar en cada
 * despliegue sin duplicar nada. NO usa factories ni Faker (que van en require-dev
 * y no existen con `composer install --no-dev`).
 *
 * - Crea/actualiza roles y permisos de spatie.
 * - Garantiza una cuenta de coordinación a partir de variables de entorno:
 *   ADMIN_EMAIL y ADMIN_PASSWORD (si no están, usa valores por defecto y avisa).
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $email = env('ADMIN_EMAIL', 'admin@sanjose.local');
        $password = env('ADMIN_PASSWORD', 'cambiar-esta-clave');
        $name = env('ADMIN_NAME', 'Coordinación');

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        // Solo fija la contraseña al crear, para no pisar cambios hechos desde la web.
        if (! $user->exists) {
            $user->password = $password; // el cast 'hashed' del modelo la cifra
        }
        $user->email_verified_at ??= now();
        $user->save();

        if (! $user->hasRole(UserRole::Admin->value)) {
            $user->assignRole(UserRole::Admin->value);
        }

        $this->command->info("Cuenta de coordinación lista: {$email}");
    }
}
