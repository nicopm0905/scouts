<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ChangeRequestStatus;
use App\Enums\ConsentType;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/** Ficha básica (no sensible) de un scout a cargo de la cuenta + revisión de datos. */
class ChildController extends Controller
{
    public function show(Request $request, Member $member): Response
    {
        $this->authorizeChild($request, $member);

        $member->load(['families.users:id', 'healthRecord', 'consents']);

        $termStart = now()->month >= 9 ? now()->startOfYear()->setMonth(9) : now()->startOfYear();
        $attendances = $member->attendances()->where('date', '>=', $termStart)->get();

        $pending = MemberChangeRequest::query()
            ->where('member_id', $member->id)
            ->pending()
            ->latest()
            ->first();

        // Familia tutora vinculada a esta cuenta (para editar sus datos de contacto).
        $family = $member->families
            ->first(fn ($f) => $f->users->contains('id', $request->user()->id))
            ?? $member->families->first();

        $consentByType = $member->consents->keyBy(fn ($c) => $c->type->value);

        return Inertia::render('Portal/Child', [
            'child' => [
                'id' => $member->id,
                'name' => $member->full_name,
                'branch' => $member->role->label(),
                'joined_at' => $member->joined_at?->format('d/m/Y'),
                'age' => $member->age,
            ],
            'attendance' => [
                'present' => $attendances->where('present', true)->count(),
                'total' => $attendances->count(),
            ],
            'guardians' => $member->families
                ->map(fn ($f) => [
                    'family' => $f->name,
                    'phone' => $f->contact_phone,
                    'email' => $f->contact_email,
                ])
                ->values(),
            // Datos que la familia puede revisar y proponer cambios.
            'editable' => [
                'phone' => $member->phone,
                'email' => $member->email,
                'allergies' => $member->healthRecord?->allergies,
                'intolerances' => $member->healthRecord?->intolerances,
                'medication' => $member->healthRecord?->medication,
                'observations' => $member->healthRecord?->observations,
                'family_contact_phone' => $family?->contact_phone,
                'family_contact_email' => $family?->contact_email,
            ],
            'consents' => collect(ConsentType::cases())->map(fn (ConsentType $t) => [
                'type' => $t->value,
                'label' => $t->label(),
                'legal_text' => $t->legalText(),
                'granted' => (bool) ($consentByType[$t->value]->granted ?? false),
            ])->values(),
            'nextSchoolYear' => $this->nextSchoolYear(),
            'pendingReview' => $pending ? [
                'payload' => $pending->payload,
                'note' => $pending->note,
                'submitted_at' => $pending->created_at?->format('d/m/Y'),
            ] : null,
        ]);
    }

    /** La familia envía una propuesta de cambios; secretaría la revisa y aplica. */
    public function submitReview(Request $request, Member $member): RedirectResponse
    {
        $this->authorizeChild($request, $member);

        $data = $request->validate([
            'member.phone' => ['nullable', 'string', 'max:30'],
            'member.email' => ['nullable', 'email', 'max:255'],
            'health.allergies' => ['nullable', 'string', 'max:2000'],
            'health.intolerances' => ['nullable', 'string', 'max:2000'],
            'health.medication' => ['nullable', 'string', 'max:2000'],
            'health.observations' => ['nullable', 'string', 'max:2000'],
            'family.contact_phone' => ['nullable', 'string', 'max:30'],
            'family.contact_email' => ['nullable', 'email', 'max:255'],
            'consents' => ['nullable', 'array'],
            'consents.*' => ['boolean'],
            'renewal_continues' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = collect([
            'member' => array_filter($data['member'] ?? [], fn ($v) => $v !== null),
            'health' => array_filter($data['health'] ?? [], fn ($v) => $v !== null),
            'family' => array_filter($data['family'] ?? [], fn ($v) => $v !== null),
        ])->filter(fn ($group) => ! empty($group))->all();

        if (! empty($data['consents'])) {
            $payload['consents'] = collect($data['consents'])
                ->only(ConsentType::values())
                ->map(fn ($v) => (bool) $v)
                ->all();
        }

        if (array_key_exists('renewal_continues', $data) && $data['renewal_continues'] !== null) {
            $payload['renewal'] = [
                'continues' => (bool) $data['renewal_continues'],
                'school_year' => $this->nextSchoolYear(),
            ];
        }

        if (empty($payload)) {
            return back()->with('info', 'No has indicado ningún cambio.');
        }

        MemberChangeRequest::updateOrCreate(
            ['member_id' => $member->id, 'status' => ChangeRequestStatus::Pending->value],
            [
                'submitted_by' => $request->user()->id,
                'payload' => $payload,
                'note' => $data['note'] ?? null,
            ]
        );

        return back()->with('success', 'Cambios enviados a secretaría para su revisión.');
    }

    /**
     * Curso al que se refiere la renovación de plaza: el que está en marcha o a
     * punto de empezar. El corte es el 1 de julio (fin del curso anterior), no
     * septiembre, para no adelantarse mientras el curso aún no ha arrancado.
     */
    private function nextSchoolYear(): string
    {
        $now = Carbon::now();
        $start = $now->month >= 7 ? $now->year : $now->year - 1;

        return $start.'-'.($start + 1);
    }

    private function authorizeChild(Request $request, Member $member): void
    {
        abort_unless(
            Member::query()->visibleTo($request->user())->whereKey($member->id)->exists(),
            HttpResponse::HTTP_NOT_FOUND
        );
    }
}
