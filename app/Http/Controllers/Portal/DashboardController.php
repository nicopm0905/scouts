<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ChargeStatus;
use App\Http\Controllers\Controller;
use App\Models\ChargeMember;
use App\Models\Event;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inicio del portal de familias: un vistazo a lo que requiere acción
 * (pagos pendientes) y a lo que viene (próximos eventos de sus ramas).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $children = $user->children();
        $childIds = $children->pluck('id')->all();
        $branches = $children->pluck('role')->map(fn ($r) => $r->value)->unique()->values()->all();

        $pending = ChargeMember::query()
            ->whereIn('member_id', $childIds ?: [0])
            ->where('status', ChargeStatus::Pending->value)
            ->with(['charge:id,title,due_date', 'member:id,first_name,last_name'])
            ->get()
            ->map(fn (ChargeMember $cm) => [
                'id' => $cm->id,
                'concept' => $cm->charge?->title,
                'child' => $cm->member?->full_name,
                'amount' => (float) $cm->amount,
                'due_date' => $cm->charge?->due_date?->format('d/m/Y'),
            ]);

        $upcoming = Event::query()
            ->where('start_at', '>=', now()->startOfDay())
            ->where(function ($q) use ($branches) {
                foreach ($branches ?: ['__none__'] as $b) {
                    $q->orWhereJsonContains('branches', $b);
                }
            })
            ->orderBy('start_at')
            ->limit(5)
            ->get(['id', 'title', 'type', 'start_at', 'location'])
            ->map(fn (Event $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'type' => $e->type->label(),
                'start_at' => $e->start_at?->format('d/m/Y H:i'),
                'location' => $e->location,
            ]);

        return Inertia::render('Portal/Dashboard', [
            'children' => $children->map(fn (Member $m) => [
                'id' => $m->id,
                'name' => $m->full_name,
                'branch' => $m->role->label(),
            ])->values(),
            'pendingPayments' => $pending->values(),
            'pendingTotal' => round($pending->sum('amount'), 2),
            'upcomingEvents' => $upcoming->values(),
        ]);
    }
}
