<?php

namespace App\Http\Controllers\Events;

use App\Enums\EventType;
use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\Member;
use App\Services\CampRatio\CampRatioValidator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'icalUrl' => URL::route('public.ical.show', ['token' => $user->ical_token]),
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

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $event = Event::create([
            ...$request->validated(),
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
        $this->authorize('view', $event);

        $event->load(['checklistItems' => fn ($q) => $q->orderBy('position')]);

        $enrollments = $event->enrollments()
            ->with('member.leaderProfile', 'member.healthRecord')
            ->get()
            ->sortBy(fn (EventMember $em) => $em->member?->full_name)
            ->values()
            ->map(fn (EventMember $em) => [
                'id' => $em->id,
                'member_id' => $em->member_id,
                'member_name' => $em->member?->full_name,
                'role' => $em->member?->role?->value,
                'role_label' => $em->member?->role?->label(),
                'enrolled' => $em->enrolled,
                'confirmed_at' => $em->confirmed_at?->toIso8601String(),
                'has_authorization' => $em->hasAuthorization(),
                'public_url' => URL::route('public.enrollment.show', ['token' => $em->public_token]),
                'notes' => $em->notes,
            ]);

        $campRatio = $event->requiresEnrollment()
            ? app(CampRatioValidator::class)->validate($event)->toArray()
            : null;

        return Inertia::render('Events/Show', [
            'event' => $this->eventDetail($event),
            'enrollments' => $enrollments,
            'checklistItems' => $event->checklistItems->map(fn ($item) => [
                'id' => $item->id,
                'label' => $item->label,
                'done' => $item->done,
                'notes' => $item->notes,
            ]),
            'charges' => $event->charges()->latest()->get(['id', 'title', 'amount', 'due_date', 'type']),
            'campRatio' => $campRatio,
            'can' => [
                'update' => $request->user()->can('update', $event),
                'delete' => $request->user()->can('delete', $event),
            ],
        ]);
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
            ...$request->validated(),
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
            'description' => $event->description,
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
            ->filter(fn (Member $m) => $m->leaderProfile && array_intersect($m->leaderProfile->branches ?? [], $branches));

        foreach ($participants->merge($leaders) as $member) {
            EventMember::firstOrCreate(
                ['event_id' => $event->id, 'member_id' => $member->id],
                ['enrolled' => false, 'public_token' => Str::random(48)]
            );
        }
    }
}
