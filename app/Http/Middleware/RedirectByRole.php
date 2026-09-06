<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Red de seguridad de navegación entre el panel de gestión y el portal:
 *  - una cuenta "familia" que abre el panel de inicio → al portal.
 *  - una cuenta de gestión que abre el portal → a su panel.
 *
 * No pretende autorizar: el resto de rutas de gestión ya devuelven 403 a las
 * familias (no tienen ningún permiso de módulo) y las del portal están tras
 * el middleware 'role:familia'. Aquí solo se evita el "callejón sin salida"
 * de aterrizar en una pantalla que no corresponde.
 */
class RedirectByRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $request->isMethod('GET')) {
            if ($user->isFamilia() && $request->routeIs('dashboard')) {
                return redirect()->route('portal.dashboard');
            }

            if (! $user->isFamilia() && $request->routeIs('portal.*')) {
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}
