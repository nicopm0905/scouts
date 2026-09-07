<?php

namespace App\Http\Controllers;

use App\Enums\ChargeStatus;
use App\Enums\MemberRole;
use App\Models\ChargeMember;
use App\Models\Checkout;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventChecklistItem;
use App\Models\EventMember;
use App\Models\InventoryItem;
use App\Models\LeaderProfile;
use App\Models\LeaderTraining;
use App\Models\Member;
use App\Models\MemberChangeRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel de inicio. Prioriza lo accionable: primero lo que requiere atención
 * (con enlace directo a donde se resuelve), luego la agenda, el dinero y el
 * material. Los responsables solo ven lo relativo a sus ramas.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $branches = $user->canSeeAllBranches() ? null : ($user->branches ?? []);

        return Inertia::render('Dashboard', [
            'attention' => $this->attention($user, $branches),
            'week' => $this->thisWeek($user, $branches),
            'agenda' => $this->agenda($branches),
            'tasks' => $this->myTasks($user),
            'finance' => $this->finance($user, $branches),
            'inventory' => $this->inventoryAlerts($user),
            'members' => $this->memberBreakdown($user),
            'today' => ucfirst(now()->locale('es')->isoFormat('dddd, D [de] MMMM')),
        ]);
    }

    /**
     * "Tu semana": los 7 días de la semana en curso con los eventos de las ramas
     * del usuario y cuántas tareas de kraal le vencen antes del domingo.
     *
     * @param  array<int,string>|null  $branches  null = todas las ramas
     * @return array<string, mixed>
     */
    private function thisWeek($user, ?array $branches): array
    {
        $start = now()->startOfWeek(Carbon::MONDAY);
        $end = now()->endOfWeek(Carbon::SUNDAY);

        $events = Event::query()
            ->whereBetween('start_at', [$start, $end])
            ->when($branches, fn ($q) => $this->filterEventBranches($q, $branches))
            ->orderBy('start_at')
            ->get(['id', 'title', 'type', 'start_at', 'branches'])
            ->map(fn (Event $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'type' => $e->type->label(),
                'type_key' => $e->type->value,
                'weekday' => (int) $e->start_at->isoWeekday(), // 1 = lunes … 7 = domingo
                'time' => $e->start_at->format('H:i'),
                'href' => route('events.show', $e->id),
            ])
            ->all();

        $tasksDue = $user->can('events.view')
            ? EventChecklistItem::query()
                ->where('assigned_to', $user->id)
                ->where('done', false)
                ->whereNotNull('due_at')
                ->where('due_at', '<=', $end)
                ->count()
            : 0;

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'range_label' => $start->isoFormat('D')
                .($start->month === $end->month ? '' : ' '.$start->isoFormat('MMM'))
                .' – '.$end->isoFormat('D MMM'),
            'today_weekday' => (int) now()->isoWeekday(),
            'events' => $events,
            'tasks_due' => $tasksDue,
        ];
    }

    /**
     * Tareas de preparación de salidas asignadas a esta persona y aún sin cerrar
     * (reparto del kraal desde la checklist del evento). Las más urgentes primero.
     *
     * @return array<int, array<string, mixed>>
     */
    private function myTasks($user): array
    {
        return EventChecklistItem::query()
            ->where('assigned_to', $user->id)
            ->where('done', false)
            ->with('event:id,title,start_at')
            ->orderByRaw('due_at is null, due_at')
            ->limit(8)
            ->get()
            ->map(fn (EventChecklistItem $item) => [
                'id' => $item->id,
                'label' => $item->label,
                'due_at' => $item->due_at?->toIso8601String(),
                'event_title' => $item->event?->title,
                'href' => $item->event
                    ? route('events.show', $item->event_id).'#checklist'
                    : null,
            ])
            ->all();
    }

    /**
     * Avisos accionables, ordenados por gravedad. Cada uno lleva a la pantalla
     * donde se resuelve, para que no haya que buscarlo por el menú.
     *
     * @param  array<int,string>|null  $branches  null = todas las ramas
     * @return array<int, array<string, mixed>>
     */
    private function attention($user, ?array $branches): array
    {
        $avisos = [];

        // 1. Certificados de delitos sexuales (bloquean legalmente una actividad).
        if ($user->can('members.view')) {
            $certificados = LeaderProfile::query()
                ->whereNotNull('sexual_offenses_certificate_expires_at')
                ->where('sexual_offenses_certificate_expires_at', '<=', now()->addDays(30))
                ->when($branches, fn ($q) => $this->filterProfileBranches($q, $branches))
                ->count();

            $avisos[] = [
                'key' => 'certificates',
                'label' => 'Certificados por caducar',
                'value' => $certificados,
                'severity' => 'alta',
                'hint' => 'Delitos sexuales, en 30 días o menos',
                'cta' => 'Revisar responsables',
                'href' => route('members.index'),
            ];
        }

        // 2. Autorizaciones que faltan para el próximo evento con inscripciones.
        if ($user->can('events.view')) {
            $proximo = Event::query()
                ->where('start_at', '>=', now())
                ->whereIn('type', ['salida', 'acampada', 'campamento'])
                ->when($branches, fn ($q) => $this->filterEventBranches($q, $branches))
                ->orderBy('start_at')
                ->first();

            $faltan = $proximo
                ? EventMember::query()
                    ->where('event_id', $proximo->id)
                    ->where('enrolled', true)
                    ->whereNull('authorization_file_id')
                    ->count()
                : 0;

            $avisos[] = [
                'key' => 'authorizations',
                'label' => 'Autorizaciones sin entregar',
                'value' => $faltan,
                'severity' => 'alta',
                'hint' => $proximo ? $proximo->title : 'Sin salidas próximas',
                'cta' => 'Abrir el evento',
                'href' => $proximo ? route('events.show', $proximo->id) : route('events.index'),
            ];
        }

        // 3. Titulaciones de monitores.
        if ($user->can('members.view')) {
            $titulaciones = LeaderTraining::query()
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addDays(60))
                ->when($branches, fn ($q) => $q->whereHas(
                    'leaderProfile',
                    fn ($p) => $this->filterProfileBranches($p, $branches)
                ))
                ->count();

            $avisos[] = [
                'key' => 'trainings',
                'label' => 'Titulaciones por caducar',
                'value' => $titulaciones,
                'severity' => 'media',
                'hint' => 'Monitores, en 60 días o menos',
                'cta' => 'Revisar responsables',
                'href' => route('members.index'),
            ];
        }

        // 3b. Revisiones de datos que han enviado las familias desde el portal.
        if ($user->can('members.manage')) {
            $revisiones = MemberChangeRequest::query()
                ->pending()
                ->when($branches, fn ($q) => $q->whereHas('member', fn ($m) => $m->whereIn('role', $branches)))
                ->count();

            $avisos[] = [
                'key' => 'family-reviews',
                'label' => 'Revisiones de familias',
                'value' => $revisiones,
                'severity' => 'media',
                'hint' => 'Cambios de datos propuestos desde el portal',
                'cta' => 'Revisar cambios',
                'href' => route('member-change-requests.index'),
            ];
        }

        // 4. Cuotas pendientes de cobro.
        if ($user->can('charges.view')) {
            $cuotas = ChargeMember::query()
                ->where('status', ChargeStatus::Pending->value)
                ->when($branches, fn ($q) => $q->whereHas('member', fn ($m) => $m->whereIn('role', $branches)))
                ->count();

            $avisos[] = [
                'key' => 'charges',
                'label' => 'Cuotas pendientes',
                'value' => $cuotas,
                'severity' => 'media',
                'hint' => 'Recibos sin cobrar',
                'cta' => 'Ir a cobros',
                'href' => route('charges.index'),
            ];
        }

        // 5. Documentos del grupo (seguros, censo…): no tienen rama.
        if ($branches === null && $user->can('documents.view')) {
            $avisos[] = [
                'key' => 'documents',
                'label' => 'Documentos por caducar',
                'value' => Document::query()->expiringWithin(30)->count(),
                'severity' => 'media',
                'hint' => 'Seguros, censo, permisos…',
                'cta' => 'Ir a documentos',
                'href' => route('documents.index'),
            ];
        }

        // 6. Material prestado que debería estar de vuelta.
        if ($user->can('inventory.view')) {
            $avisos[] = [
                'key' => 'checkouts',
                'label' => 'Préstamos fuera de plazo',
                'value' => Checkout::outstanding()
                    ->whereNotNull('expected_return_at')
                    ->where('expected_return_at', '<', now())
                    ->count(),
                'severity' => 'media',
                'hint' => 'Material sin devolver',
                'cta' => 'Ir a inventario',
                'href' => route('inventory.index'),
            ];
        }

        return $avisos;
    }

    /**
     * Próximos eventos con fecha en crudo, para que el front pueda decir
     * "hoy", "mañana" o el día de la semana.
     *
     * @param  array<int,string>|null  $branches
     * @return array<int, array<string, mixed>>
     */
    private function agenda(?array $branches): array
    {
        return Event::query()
            ->where('start_at', '>=', now()->startOfDay())
            ->when($branches, fn ($q) => $this->filterEventBranches($q, $branches))
            ->orderBy('start_at')
            ->limit(6)
            ->get(['id', 'title', 'type', 'start_at', 'end_at', 'location', 'branches'])
            ->map(fn (Event $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'type' => $e->type->label(),
                'type_key' => $e->type->value,
                'start_at' => $e->start_at?->toIso8601String(),
                'time' => $e->start_at?->format('H:i'),
                'location' => $e->location,
                'branches' => $e->branches ?? [],
                'href' => route('events.show', $e->id),
            ])->all();
    }

    /**
     * Situación de cobros: cuánto falta por cobrar, cuánto entró y los últimos movimientos.
     *
     * @param  array<int,string>|null  $branches
     * @return array<string, mixed>|null
     */
    private function finance($user, ?array $branches): ?array
    {
        if (! $user->can('charges.view')) {
            return null;
        }

        $visibles = fn () => ChargeMember::query()
            ->when($branches, fn ($q) => $q->whereHas('member', fn ($m) => $m->whereIn('role', $branches)));

        $pendiente = (float) $visibles()->where('status', ChargeStatus::Pending->value)->sum('amount');
        $cobrado = (float) $visibles()->where('status', ChargeStatus::Paid->value)->sum('amount');
        $total = $pendiente + $cobrado;

        return [
            'pending_amount' => round($pendiente, 2),
            'paid_amount' => round($cobrado, 2),
            'paid_ratio' => $total > 0 ? (int) round($cobrado / $total * 100) : 100,
            'pending_count' => $visibles()->where('status', ChargeStatus::Pending->value)->count(),
            'recent' => ChargeMember::query()
                ->with(['charge:id,title', 'member:id,first_name,last_name'])
                ->when($branches, fn ($q) => $q->whereHas('member', fn ($m) => $m->whereIn('role', $branches)))
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (ChargeMember $cm) => [
                    'id' => $cm->id,
                    'charge' => $cm->charge?->title,
                    'member' => $cm->member?->full_name,
                    'amount' => $cm->amount,
                    'status' => $cm->status->label(),
                    'status_color' => $cm->status->badgeColor(),
                ])->all(),
            'href' => route('charges.index'),
        ];
    }

    /** @return array<string, mixed>|null */
    private function inventoryAlerts($user): ?array
    {
        if (! $user->can('inventory.view')) {
            return null;
        }

        return [
            'review_due' => InventoryItem::needingReview(30)->count(),
            'overdue_checkouts' => Checkout::outstanding()
                ->whereNotNull('expected_return_at')
                ->where('expected_return_at', '<', now())
                ->count(),
            'href' => route('inventory.index'),
        ];
    }

    /**
     * Reparto de miembros por rama (solo las que el usuario puede ver).
     *
     * @return array<string, mixed>|null
     */
    private function memberBreakdown($user): ?array
    {
        if (! $user->can('members.view')) {
            return null;
        }

        $conteos = Member::query()
            ->visibleTo($user)
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $ramas = collect(MemberRole::cases())
            ->map(fn (MemberRole $rol) => [
                'key' => $rol->value,
                'label' => $rol->label(),
                'count' => (int) ($conteos[$rol->value] ?? 0),
            ])
            ->filter(fn (array $rama) => $rama['count'] > 0)
            ->values()
            ->all();

        return [
            'total' => (int) $conteos->sum(),
            'branches' => $ramas,
            'href' => route('members.index'),
        ];
    }

    /**
     * Filtra perfiles de monitor por las ramas dadas (columna JSON LeaderProfile.branches).
     *
     * @param  array<int,string>  $branches
     */
    private function filterProfileBranches($query, array $branches)
    {
        return $query->where(function ($sub) use ($branches) {
            foreach ($branches as $b) {
                $sub->orWhereJsonContains('branches', $b);
            }
        });
    }

    /**
     * Filtra eventos por las ramas dadas (columna JSON Event.branches).
     *
     * @param  array<int,string>  $branches
     */
    private function filterEventBranches($query, array $branches)
    {
        return $query->where(function ($sub) use ($branches) {
            foreach ($branches as $b) {
                $sub->orWhereJsonContains('branches', $b);
            }
        });
    }
}
