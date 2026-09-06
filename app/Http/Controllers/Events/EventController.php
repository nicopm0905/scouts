<?php

namespace App\Http\Controllers\Events;

use App\Enums\EventType;
use App\Enums\MemberRole;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\Member;
use App\Models\User;
use App\Services\CampRatio\CampRatioValidator;
use App\Services\Drive\DriveServiceInterface;
use App\Services\Drive\DriveStructureService;
use App\Services\Inventory\InventoryAvailabilityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Event::class);

        $query = Event::query()->orderBy('start_at');

        if ($request->filled('branch')) {
            $query->whereJsonContains('branches', $request->string('branch')->toString());
        }

        $events = $query->get()->map(fn (Event $event) => $this->eventSummary($event));

        $user = $request->user();
        $user->ensureIcalToken();

        return Inertia::render('Events/Index', [
            'events' => $events,
            'filters' => ['branch' => $request->input('branch')],
            'branchOptions' => $this->branchOptions(),
            'typeOptions' => $this->typeOptions(),
            'icalUrl' => URL::route('public.ical.show', array_filter([
                'token' => $user->ical_token,
                'branch' => $request->input('branch'),
            ])),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Event::class);

        return Inertia::render('Events/Create', [
            'branchOptions' => $this->branchOptions(),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function store(StoreEventRequest $request, DriveStructureService $driveStructure, DriveServiceInterface $drive): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $folderId = null;
        try {
            $secretaryFolderId = $driveStructure->getOrCreateSecretaryFolder();
            $folderId = $drive->createFolder($request->input('title'), $secretaryFolderId);
        } catch (\Exception $e) {
            // Ignoramos si falla (ej. DRIVE_DRIVER=fake o falta config)
            report($e);
        }

        $event = Event::create([
            ...$request->safe()->except(['city', 'theme', 'coordinator', 'eucharist', 'hike', 'branches']),
            'location_city' => $request->input('city'),
            'theme_description' => $request->input('theme'),
            'coordinator_id' => $request->input('coordinator'),
            'has_eucharist' => $request->boolean('eucharist'),
            'has_hike' => $request->boolean('hike'),
            'drive_folder_id' => $folderId,
            'branches' => $request->input('branches', []),
            'created_by' => $request->user()->id,
        ]);

        if ($event->requiresEnrollment()) {
            $this->createDefaultChecklist($event);
            $this->autoEnrollMembers($event);
        }

        return redirect()->route('events.show', $event)->with('success', 'Evento creado correctamente.');
    }

    public function show(Request $request, Event $event): Response
    {
        if ($event->requiresEnrollment()) {
            $this->autoEnrollMembers($event);
        }

        $event->load([
            'checklistItems' => fn ($q) => $q->orderBy('position')->with('assignee:id,name'),
            'activities' => fn ($q) => $q->orderBy('day_number')->orderBy('time_slot')->orderBy('activity_number'),
        ]);

        // El enlace público de inscripción (token por familia) solo se expone
        // a quien puede gestionar el evento; para el resto se omite el campo.
        $canUpdate = $request->user()->can('update', $event);

        $enrollments = $event->enrollments()
            ->with('member.families', 'member.leaderProfile', 'member.healthRecord')
            ->get()
            ->sortBy(fn (EventMember $em) => $em->member?->full_name)
            ->values()
            ->map(fn (EventMember $em) => [
                'id' => $em->id,
                'member_id' => $em->member_id,
                'member_name' => $em->member?->full_name,
                'role' => $em->member?->role?->value,
                'role_label' => $em->member?->role?->label(),
                'phone' => $em->member?->phone,
                'family_phone' => $em->member?->families->first()?->contact_phone,
                'family_name' => $em->member?->families->first()?->name,
                'enrolled' => $em->enrolled,
                'confirmed_at' => $em->confirmed_at?->toIso8601String(),
                'has_authorization' => $em->hasAuthorization(),
                'notes' => $em->notes,
                ...($canUpdate
                    ? ['public_url' => URL::route('public.enrollment.show', ['token' => $em->public_token])]
                    : []),
            ]);

        $campRatio = $event->requiresEnrollment()
            ? app(CampRatioValidator::class)->validate($event)->toArray()
            : null;

        $allMembers = Member::query()->active()->orderBy('first_name')->get()->map(fn (Member $m) => [
            'id' => $m->id,
            'full_name' => $m->full_name,
            'role' => $m->role->value,
            'role_label' => $m->role->label(),
        ]);

        return Inertia::render('Events/Show', [
            'event' => $this->eventDetail($event),
            'enrollments' => $enrollments,
            'allMembers' => $allMembers,
            'checklistItems' => $event->checklistItems->map(fn ($item) => [
                'id' => $item->id,
                'label' => $item->label,
                'done' => $item->done,
                'notes' => $item->notes,
                'assigned_to' => $item->assigned_to,
                'assignee_name' => $item->assignee?->name,
                'due_at' => $item->due_at?->toDateString(),
            ]),
            'staffUsers' => $canUpdate
                ? User::role(UserRole::staffValues())
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
                    ->values()
                : [],
            'charges' => $event->charges()->latest()->get(['id', 'title', 'amount', 'due_date', 'type']),
            'activities' => $event->activities->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'day_number' => $a->day_number,
                'time_slot' => $a->time_slot,
                'activity_number' => $a->activity_number,
                'duration_minutes' => $a->duration_minutes,
            ]),
            'campRatio' => $campRatio,
            'preparation' => ($canUpdate && $event->requiresEnrollment())
                ? $this->preparationSteps($event, $campRatio, $enrollments)
                : null,
            'can' => [
                'update' => $request->user()->can('update', $event),
                'delete' => $request->user()->can('delete', $event),
            ],
        ]);
    }

    /**
     * "Preparar salida": estado de cada paso para montar la actividad, con la acción
     * para completarlo sin salir de la pantalla. Solo para eventos con inscripción.
     *
     * @param  array<string,mixed>|null  $campRatio
     * @param  Collection<int,array<string,mixed>>  $enrollments
     * @return array{ready:int,total:int,steps:array<int,array<string,mixed>>}
     */
    private function preparationSteps(Event $event, ?array $campRatio, $enrollments): array
    {
        $enrolled = $enrollments->where('enrolled', true);
        $enrolledCount = $enrolled->count();
        $withAuth = $enrolled->where('has_authorization', true)->count();

        $checklist = $event->checklistItems;
        $checklistDone = $checklist->where('done', true)->count();

        $missingData = collect([
            'ramas' => empty($event->branches),
            'lugar' => blank($event->location),
            'fecha de fin' => blank($event->end_at),
            'coordinador/a' => blank($event->coordinator_id),
        ])->filter()->keys();

        // Material: solo si hay actividades con material de inventario asignado.
        $materials = $event->activities->isNotEmpty()
            ? app(InventoryAvailabilityService::class)->checkEventMaterials($event)
            : [];
        $materialShort = collect($materials)->where('is_available', false)->count();

        $firstChargeId = $event->charges()->min('id');

        $ratioStatus = match ($campRatio['status'] ?? null) {
            'ok' => 'ok',
            'warning' => 'warn',
            'fail' => 'todo',
            default => 'warn',
        };

        $steps = [
            [
                'key' => 'datos',
                'label' => 'Datos y ramas del evento',
                'status' => $missingData->isEmpty() ? 'ok' : 'warn',
                'detail' => $missingData->isEmpty()
                    ? 'Fechas, lugar, coordinador/a y ramas completos'
                    : 'Falta: '.$missingData->implode(', '),
                'action_label' => 'Editar evento',
                'href' => route('events.edit', $event->id),
            ],
            [
                'key' => 'ratio',
                'label' => 'Validación legal de ratios',
                'status' => $ratioStatus,
                'detail' => $campRatio
                    ? $campRatio['leaders'].'/'.$campRatio['required_leaders'].' responsables (1:'.$campRatio['ratio'].')'
                    : 'Sin datos de ratio',
                'action_label' => 'Ver semáforo',
                'anchor' => 'semaforo',
            ],
            [
                'key' => 'inscripciones',
                'label' => 'Inscripciones',
                'status' => $enrolledCount > 0 ? 'ok' : 'todo',
                'detail' => $enrolledCount.' inscritos de '.$enrollments->count().' del censo',
                'action_label' => 'Gestionar inscripciones',
                'anchor' => 'inscripciones',
            ],
            [
                'key' => 'autorizaciones',
                'label' => 'Autorizaciones recibidas',
                'status' => $enrolledCount > 0 && $withAuth === $enrolledCount
                    ? 'ok'
                    : ($withAuth > 0 ? 'warn' : 'todo'),
                'detail' => $withAuth.'/'.max($enrolledCount, 1).' con autorización'
                    .($enrolledCount === 0 ? ' (sin inscritos todavía)' : ''),
                'action_label' => 'Enviar / revisar',
                'anchor' => 'inscripciones',
            ],
            [
                'key' => 'material',
                'label' => 'Material reservado',
                'status' => $materialShort > 0 ? 'warn' : 'ok',
                'detail' => empty($materials)
                    ? 'Sin material de inventario asignado a las actividades'
                    : ($materialShort > 0
                        ? $materialShort.' material(es) sin unidades suficientes en esas fechas'
                        : 'Todo el material de las actividades está disponible'),
                'action_label' => empty($materials) ? 'Ir a inventario' : 'Ver lista de material',
                'href' => empty($materials) ? route('inventory.index') : route('events.pdf.materials', $event->id),
            ],
            [
                'key' => 'cobro',
                'label' => 'Cobro del evento',
                'status' => $firstChargeId ? 'ok' : 'todo',
                'detail' => $firstChargeId
                    ? 'Cobro creado y repartido entre los inscritos'
                    : 'Aún no se ha generado el cobro',
                'action_label' => $firstChargeId ? 'Ver cobro' : 'Generar cobro',
                'href' => $firstChargeId ? route('charges.show', $firstChargeId) : null,
                'anchor' => $firstChargeId ? null : 'cobros',
            ],
            [
                'key' => 'hoja_medica',
                'label' => 'Hoja médica de asistentes',
                'status' => $enrolledCount > 0 ? 'ok' : 'todo',
                'detail' => $enrolledCount > 0
                    ? 'Listado con alergias, medicación y contacto listo para descargar'
                    : 'Se genera cuando haya inscritos',
                'action_label' => 'Descargar PDF',
                'href' => $enrolledCount > 0 ? route('events.pdf.attendees', $event->id) : null,
            ],
            [
                'key' => 'checklist',
                'label' => 'Checklist de documentación',
                'status' => $checklist->isNotEmpty() && $checklistDone === $checklist->count()
                    ? 'ok'
                    : ($checklistDone > 0 ? 'warn' : 'todo'),
                'detail' => $checklistDone.'/'.$checklist->count().' puntos completados',
                'action_label' => 'Revisar checklist',
                'anchor' => 'checklist',
            ],
        ];

        return [
            'ready' => collect($steps)->where('status', 'ok')->count(),
            'total' => count($steps),
            'steps' => $steps,
        ];
    }

    public function edit(Event $event): Response
    {
        $this->authorize('update', $event);

        return Inertia::render('Events/Edit', [
            'event' => $this->eventDetail($event),
            'branchOptions' => $this->branchOptions(),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $event->update([
            ...$request->safe()->except(['city', 'theme', 'coordinator', 'eucharist', 'hike', 'branches']),
            'location_city' => $request->input('city'),
            'theme_description' => $request->input('theme'),
            'coordinator_id' => $request->input('coordinator'),
            'has_eucharist' => $request->boolean('eucharist'),
            'has_hike' => $request->boolean('hike'),
            'branches' => $request->input('branches', []),
        ]);

        if ($event->requiresEnrollment()) {
            $this->autoEnrollMembers($event);
        }

        return redirect()->route('events.show', $event)->with('success', 'Evento actualizado correctamente.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('events.index')->with('success', 'Evento eliminado correctamente.');
    }

    /** Genera (o regenera) el token personal de suscripción iCal del usuario. */
    public function regenerateIcalToken(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['ical_token' => Str::random(48)])->save();

        return redirect()->route('events.index')->with('success', 'Enlace de suscripción regenerado.');
    }

    private function eventSummary(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'type' => $event->type->value,
            'type_label' => $event->type->label(),
            'start_at' => $event->start_at->toIso8601String(),
            'end_at' => $event->end_at?->toIso8601String(),
            'location' => $event->location,
            'branches' => $event->branches ?? [],
            'requires_enrollment' => $event->requiresEnrollment(),
        ];
    }

    private function eventDetail(Event $event): array
    {
        return [
            ...$this->eventSummary($event),
            'city' => $event->location_city,
            'theme' => $event->theme_description,
            'coordinator_id' => $event->coordinator_id,
            'coordinator_name' => (clone $event)->load('coordinator')->coordinator?->full_name,
            'eucharist' => (bool) $event->has_eucharist,
            'hike' => (bool) $event->has_hike,
            'description' => $event->description,
            'budget_id' => $event->budget()->value('id'),
        ];
    }

    private function branchOptions(): array
    {
        return collect(MemberRole::branches())
            ->map(fn (MemberRole $b) => ['value' => $b->value, 'label' => $b->label()])
            ->values()
            ->all();
    }

    private function typeOptions(): array
    {
        return collect(EventType::cases())
            ->map(fn (EventType $t) => ['value' => $t->value, 'label' => $t->label(), 'requires_enrollment' => $t->requiresEnrollment()])
            ->values()
            ->all();
    }

    private function createDefaultChecklist(Event $event): void
    {
        if ($event->checklistItems()->exists()) {
            return;
        }

        $defaults = [
            'Autorización de la Junta de Andalucía',
            'Seguro de responsabilidad civil / accidentes',
            'Listado de asistentes',
            'Comunicación a familias (circular)',
        ];

        foreach ($defaults as $i => $label) {
            $event->checklistItems()->create(['label' => $label, 'position' => $i]);
        }
    }

    /** Crea (si no existen) las inscripciones de los miembros y responsables afectados por las ramas del evento. */
    private function autoEnrollMembers(Event $event): void
    {
        $branches = $event->branches ?? [];

        if (empty($branches)) {
            return;
        }

        $participants = Member::query()->active()->whereIn('role', $branches)->get();

        $leaders = Member::query()->active()
            ->where('role', MemberRole::Responsable->value)
            ->with('leaderProfile')
            ->get()
            ->filter(function (Member $m) use ($branches) {
                $assigned = $m->leaderProfile?->branches ?? [];

                // Include if leader has explicitly selected this branch OR if no branch restriction configured yet
                return empty($assigned) || (bool) array_intersect($assigned, $branches);
            });

        foreach ($participants->merge($leaders) as $member) {
            EventMember::firstOrCreate(
                ['event_id' => $event->id, 'member_id' => $member->id],
                ['enrolled' => false, 'public_token' => Str::random(48)]
            );
        }
    }
}
