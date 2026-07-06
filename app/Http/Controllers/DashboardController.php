<?php

namespace App\Http\Controllers;

use App\Enums\ChargeStatus;
use App\Enums\MemberRole;
use App\Models\ChargeMember;
use App\Models\Checkout;
use App\Models\Document;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\InventoryItem;
use App\Models\LeaderProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel de inicio (Agente H). Agrega datos de todos los módulos en un "semáforo
 * documental" y listados de próximos eventos, últimos cobros y alertas.
 * Los responsables solo ven lo relativo a sus ramas.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $branches = $user->canSeeAllBranches() ? null : ($user->branches ?? []);

        return Inertia::render('Dashboard', [
            'semaphore' => $this->semaphore($branches),
            'upcomingEvents' => $this->upcomingEvents($branches),
            'recentCharges' => $this->recentCharges($user),
            'inventoryAlerts' => $this->inventoryAlerts($user),
        ]);
    }

    /** @param array<int,string>|null $branches null = todas las ramas */
    private function semaphore(?array $branches): array
    {
        // Cuotas/cobros pendientes (opcionalmente filtrados por rama del miembro).
        $pendingCharges = ChargeMember::query()
            ->where('status', ChargeStatus::Pending->value)
            ->when($branches, fn ($q) => $q->whereHas('member', fn ($m) => $m->whereIn('role', $branches)))
            ->count();

        // Autorizaciones que faltan para el próximo evento con inscripciones.
        $nextEnrollmentEvent = Event::query()
            ->where('start_at', '>=', now())
            ->whereIn('type', ['salida', 'acampada', 'campamento'])
            ->when($branches, fn ($q) => $q->where(function ($sub) use ($branches) {
                foreach ($branches as $b) {
                    $sub->orWhereJsonContains('branches', $b);
                }
            }))
            ->orderBy('start_at')
            ->first();

        $missingAuthorizations = 0;
        $nextEventTitle = null;
        if ($nextEnrollmentEvent) {
            $nextEventTitle = $nextEnrollmentEvent->title;
            $missingAuthorizations = EventMember::query()
                ->where('event_id', $nextEnrollmentEvent->id)
                ->where('enrolled', true)
                ->whereNull('authorization_file_id')
                ->count();
        }

        // Certificados de delitos sexuales próximos a caducar o caducados (solo global; ramas del responsable).
        $expiringCertificates = LeaderProfile::query()
            ->whereNotNull('sexual_offenses_certificate_expires_at')
            ->where('sexual_offenses_certificate_expires_at', '<=', now()->addDays(30))
            ->when($branches, fn ($q) => $q->whereHas('member', fn ($m) => $m->whereIn('role', $branches)))
            ->count();

        // Documentos (seguros, censo...) próximos a caducar — solo roles con visibilidad global.
        $expiringDocuments = $branches === null
            ? Document::query()->expiringWithin(30)->count()
            : 0;

        return [
            'pending_charges' => $pendingCharges,
            'missing_authorizations' => $missingAuthorizations,
            'next_event_title' => $nextEventTitle,
            'expiring_certificates' => $expiringCertificates,
            'expiring_documents' => $expiringDocuments,
        ];
    }

    private function upcomingEvents(?array $branches): array
    {
        return Event::query()
            ->where('start_at', '>=', now())
            ->when($branches, fn ($q) => $q->where(function ($sub) use ($branches) {
                foreach ($branches as $b) {
                    $sub->orWhereJsonContains('branches', $b);
                }
            }))
            ->orderBy('start_at')
            ->limit(5)
            ->get(['id', 'title', 'type', 'start_at', 'location'])
            ->map(fn (Event $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'type' => $e->type->label(),
                'start_at' => $e->start_at?->format('d/m/Y H:i'),
                'location' => $e->location,
            ])->all();
    }

    private function recentCharges($user): array
    {
        if (! $user->can('charges.view')) {
            return [];
        }

        return ChargeMember::query()
            ->with(['charge:id,title', 'member:id,first_name,last_name'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (ChargeMember $cm) => [
                'id' => $cm->id,
                'charge' => $cm->charge?->title,
                'member' => $cm->member?->full_name,
                'amount' => $cm->amount,
                'status' => $cm->status->label(),
                'status_color' => $cm->status->badgeColor(),
            ])->all();
    }

    private function inventoryAlerts($user): array
    {
        if (! $user->can('inventory.view')) {
            return ['review_due' => 0, 'overdue_checkouts' => 0];
        }

        return [
            'review_due' => InventoryItem::needingReview(30)->count(),
            'overdue_checkouts' => Checkout::outstanding()
                ->whereNotNull('expected_return_at')
                ->where('expected_return_at', '<', now())
                ->count(),
        ];
    }
}
