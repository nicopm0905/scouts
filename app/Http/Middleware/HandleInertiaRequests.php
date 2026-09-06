<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'branches' => $user->branches ?? [],
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'is_familia' => $user->isFamilia(),
                    'is_intendente' => $user->isIntendente(),
                ] : null,
            ],
            // Mensajes flash -> toasts en el frontend.
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'app' => [
                'name' => config('app.name'),
            ],
            // Datos del grupo usados por el layout público (cabecera y pie).
            'group' => [
                'name' => config('group.name'),
                'short_name' => config('group.short_name'),
                'tagline' => config('group.tagline'),
                'federation' => config('group.federation'),
                'contact' => config('group.contact'),
                'social' => config('group.social'),
            ],
        ];
    }
}
