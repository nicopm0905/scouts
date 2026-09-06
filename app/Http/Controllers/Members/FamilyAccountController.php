<?php

namespace App\Http\Controllers\Members;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Alta y baja de cuentas de acceso para las familias. El registro público está
 * desactivado: una familia solo entra al portal si secretaría/coordinación la
 * invita desde aquí. La invitación crea el usuario y le envía un enlace para
 * establecer su contraseña (reutiliza el flujo de "contraseña olvidada").
 */
class FamilyAccountController extends Controller
{
    public function invite(Request $request, Family $family): RedirectResponse
    {
        Gate::authorize('create', Member::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::firstOrNew(['email' => $data['email']]);

        if (! $user->exists) {
            $user->fill([
                'name' => $data['name'],
                'password' => bcrypt(Str::random(40)),
                'email_verified_at' => now(), // la crea un administrador: cuenta de confianza
                'active' => true,
            ])->save();
        }

        if (! $user->hasRole(UserRole::Familia->value)) {
            $user->assignRole(UserRole::Familia->value);
        }

        $family->users()->syncWithoutDetaching([$user->id]);

        // Las invitaciones de acceso NO se envían por correo (decisión del grupo):
        // se genera el enlace y se entrega a mano. El correo sí se usa luego para
        // recordatorios de pago, avisos y firma de autorizaciones.
        $token = Password::broker()->createToken($user);
        $url = route('password.reset', ['token' => $token, 'email' => $user->email]);

        return back()
            ->with('success', "Cuenta lista para {$user->email}. Copia el enlace y entrégaselo a la familia.")
            ->with('invite_url', $url);
    }

    public function revoke(Family $family, User $user): RedirectResponse
    {
        Gate::authorize('create', Member::class);

        $family->users()->detach($user->id);

        // Si la cuenta se queda sin ninguna familia, se desactiva.
        if ($user->families()->count() === 0) {
            $user->update(['active' => false]);
        }

        return back()->with('success', 'Acceso de la familia retirado.');
    }
}
