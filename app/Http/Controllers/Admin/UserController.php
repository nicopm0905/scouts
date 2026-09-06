<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MemberRole;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/** Gestión de cuentas de acceso a la plataforma. Solo coordinación (users.manage). */
class UserController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', User::class);

        $currentId = request()->user()->id;

        $users = User::query()
            ->with('roles:id,name')
            ->withCount('families')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->roles->first()?->name,
                'branches' => $u->branches ?? [],
                'active' => (bool) $u->active,
                'families_count' => $u->families_count,
                'last_login_at' => $u->last_login_at?->format('d/m/Y H:i'),
                'is_self' => $u->id === $currentId,
                'protected' => $u->id === $currentId || $this->isLastAdmin($u),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roleOptions' => collect(UserRole::cases())
                ->map(fn (UserRole $r) => ['value' => $r->value, 'label' => $r->label()]),
            'branchOptions' => collect(MemberRole::branches())
                ->map(fn (MemberRole $b) => ['value' => $b->value, 'label' => $b->label()]),
            'inviteUrl' => session('invite_url'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt(Str::random(40)),
            'email_verified_at' => now(),
            'active' => true,
            'branches' => $data['role'] === UserRole::Responsable->value ? ($data['branches'] ?? []) : null,
        ]);

        $user->syncRoles([$data['role']]);

        // Sin correo: se genera el enlace de alta y se entrega a mano.
        $token = Password::broker()->createToken($user);

        return back()
            ->with('success', "Cuenta creada para {$user->email}. Copia el enlace y entrégaselo.")
            ->with('invite_url', route('password.reset', ['token' => $token, 'email' => $user->email]));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roleChanged = $user->roles->first()?->name !== ($data['role'] ?? null);
        $deactivating = array_key_exists('active', $data) && ! $data['active'] && $user->active;
        $isSelf = $request->user()->id === $user->id;

        // Salvaguardas (no pasan por Gate: el admin lo saltaría todo vía Gate::before).
        if (($roleChanged || $deactivating) && $isSelf) {
            return back()->with('error', 'No puedes cambiar tu propio rol ni desactivar tu cuenta.');
        }

        if (($roleChanged || $deactivating) && $this->isLastAdmin($user)) {
            return back()->with('error', 'No puedes dejar el sistema sin ningún administrador.');
        }

        $user->update([
            'name' => $data['name'],
            'active' => $data['active'] ?? $user->active,
            'branches' => $data['role'] === UserRole::Responsable->value ? ($data['branches'] ?? []) : null,
        ]);

        if ($roleChanged) {
            $user->syncRoles([$data['role']]);
        }

        return back()->with('success', 'Cuenta actualizada.');
    }

    public function resendInvite(User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        // Genera un enlace nuevo de alta/recuperación (el anterior deja de valer).
        $token = Password::broker()->createToken($user);

        return back()
            ->with('success', "Enlace nuevo para {$user->email}. Cópialo y entrégaselo.")
            ->with('invite_url', route('password.reset', ['token' => $token, 'email' => $user->email]));
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('viewAny', User::class);

        if ($user->id === request()->user()->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        if ($this->isLastAdmin($user)) {
            return back()->with('error', 'No puedes eliminar al último administrador.');
        }

        try {
            $user->delete();
        } catch (\Throwable $e) {
            return back()->with('error', 'Esta cuenta tiene datos vinculados. Desactívala en lugar de borrarla.');
        }

        return back()->with('success', 'Cuenta eliminada.');
    }

    private function isLastAdmin(User $user): bool
    {
        return $user->hasRole(UserRole::Admin->value)
            && User::role(UserRole::Admin->value)->count() <= 1;
    }
}
