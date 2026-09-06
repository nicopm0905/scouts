<?php

namespace App\Http\Controllers\Members;

use App\Enums\ConsentType;
use App\Enums\FamilyRelationship;
use App\Enums\LeaderQualification;
use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Members\StoreMemberRequest;
use App\Http\Requests\Members\UpdateMemberRequest;
use App\Models\Family;
use App\Models\Member;
use App\Services\Drive\DriveServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(private readonly DriveServiceInterface $drive) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Member::class);

        $user = $request->user();

        $members = Member::query()
            ->visibleTo($user)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Member $member) => $this->memberSummary($member));

        return Inertia::render('Members/Index', [
            'members' => $members,
            'branches' => $this->branchOptions(),
            'canManage' => $user->can('create', Member::class),
            'canImport' => $user->can('create', Member::class),
        ]);
    }

    /**
     * Exporta el censo visible para el usuario en CSV (sin datos médicos ni sensibles).
     */
    public function export(Request $request): HttpResponse
    {
        Gate::authorize('viewAny', Member::class);

        $members = Member::query()
            ->visibleTo($request->user())
            ->with('families')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['nombre', 'apellidos', 'rama', 'fecha_nacimiento', 'familia', 'telefono_familia', 'email_familia', 'activo', 'fecha_alta']);

        foreach ($members as $member) {
            $family = $member->families->first();

            fputcsv($handle, [
                $member->first_name,
                $member->last_name,
                $member->role->label(),
                $member->birth_date?->toDateString(),
                $family?->name,
                $family?->contact_phone,
                $family?->contact_email,
                $member->active ? 'si' : 'no',
                $member->joined_at?->toDateString(),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        // BOM UTF-8 para que Excel muestre bien los acentos.
        return response("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="censo_miembros_'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Member::class);

        return Inertia::render('Members/Create', [
            'branches' => $this->branchOptions(),
        ]);
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $member = Member::create($request->validated());

        return redirect()->route('members.show', $member)->with('success', 'Miembro creado correctamente.');
    }

    public function show(Request $request, Member $member): Response
    {
        Gate::authorize('view', $member);

        $user = $request->user();
        $canSeeSensitive = $user->can('viewSensitive', $member);

        if ($canSeeSensitive) {
            activity()
                ->causedBy($user)
                ->performedOn($member)
                ->log('Consulta de ficha sensible (salud/consentimientos)');
        }

        $member->load(['families', 'consents.signature']);

        $data = [
            'member' => $this->memberDetail($member),
            'canManage' => $user->can('update', $member),
            'canSeeSensitive' => $canSeeSensitive,
            'families' => $member->families->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'relationship' => $f->pivot->relationship,
                'relationship_label' => FamilyRelationship::from($f->pivot->relationship)->label(),
            ]),
            'allFamilies' => Family::orderBy('name')->get(['id', 'name']),
            'familyRelationships' => collect(FamilyRelationship::cases())->map(fn ($r) => [
                'value' => $r->value, 'label' => $r->label(),
            ]),
        ];

        if ($canSeeSensitive) {
            $member->load('healthRecord');

            $data['healthRecord'] = $member->healthRecord ? [
                'allergies' => $member->healthRecord->allergies,
                'intolerances' => $member->healthRecord->intolerances,
                'medication' => $member->healthRecord->medication,
                'observations' => $member->healthRecord->observations,
                'health_card_number' => $member->healthRecord->health_card_number,
                'drive_file_id' => $member->healthRecord->drive_file_id,
            ] : null;

            $data['consents'] = collect(ConsentType::cases())->map(function ($type) use ($member) {
                $consent = $member->consents->firstWhere('type', $type);
                $signature = $consent?->signature;

                return [
                    'type' => $type->value,
                    'label' => $type->label(),
                    'granted' => $consent?->granted ?? false,
                    'signed_at' => $consent?->signed_at?->toDateString(),
                    'signature_status' => $signature?->status->value,
                    'signature_status_label' => $signature?->status->label(),
                    'signed_document_url' => $signature?->signed_drive_file_id
                        ? $this->drive->webViewLink($signature->signed_drive_file_id)
                        : null,
                ];
            });
        }

        if ($member->isLeader()) {
            $member->load('leaderProfile.trainings');

            $data['leaderProfile'] = $member->leaderProfile ? [
                'qualification' => $member->leaderProfile->qualification->value,
                'qualification_label' => $member->leaderProfile->qualification->label(),
                'sexual_offenses_certificate_date' => $member->leaderProfile->sexual_offenses_certificate_date?->toDateString(),
                'sexual_offenses_certificate_expires_at' => $member->leaderProfile->sexual_offenses_certificate_expires_at?->toDateString(),
                'certificate_valid' => $member->leaderProfile->hasValidSexualOffensesCertificate(),
                'branches' => $member->leaderProfile->branches ?? [],
                'trainings' => $member->leaderProfile->trainings->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'obtained_at' => $t->obtained_at?->toDateString(),
                    'expires_at' => $t->expires_at?->toDateString(),
                    'expired' => $t->isExpired(),
                ]),
            ] : null;
            $data['qualifications'] = collect(LeaderQualification::cases())->map(fn ($q) => [
                'value' => $q->value, 'label' => $q->label(),
            ]);
        }

        return Inertia::render('Members/Show', $data);
    }

    public function edit(Request $request, Member $member): Response
    {
        Gate::authorize('update', $member);

        $user = $request->user();
        $canSeeSensitive = $user->can('viewSensitive', $member);
        $member->load(['families', 'consents.signature']);

        $data = [
            'member' => $this->memberDetail($member),
            'branches' => $this->branchOptions(),
            'canSeeSensitive' => $canSeeSensitive,
            'families' => $member->families->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'relationship' => $f->pivot->relationship,
                'relationship_label' => FamilyRelationship::from($f->pivot->relationship)->label(),
                'contact_phone' => $f->contact_phone,
            ]),
            'allFamilies' => Family::orderBy('name')->get(['id', 'name']),
            'familyRelationships' => collect(FamilyRelationship::cases())->map(fn ($r) => [
                'value' => $r->value, 'label' => $r->label(),
            ]),
        ];

        if ($canSeeSensitive) {
            $member->load('healthRecord');

            $data['healthRecord'] = $member->healthRecord ? [
                'allergies' => $member->healthRecord->allergies,
                'intolerances' => $member->healthRecord->intolerances,
                'medication' => $member->healthRecord->medication,
                'observations' => $member->healthRecord->observations,
                'health_card_number' => $member->healthRecord->health_card_number,
                'drive_file_id' => $member->healthRecord->drive_file_id,
            ] : null;

            $data['consents'] = collect(ConsentType::cases())->map(function ($type) use ($member) {
                $consent = $member->consents->firstWhere('type', $type);
                $signature = $consent?->signature;

                return [
                    'type' => $type->value,
                    'label' => $type->label(),
                    'granted' => $consent?->granted ?? false,
                    'signed_at' => $consent?->signed_at?->toDateString(),
                    'signature_status' => $signature?->status->value,
                    'signature_status_label' => $signature?->status->label(),
                    'signed_document_url' => $signature?->signed_drive_file_id
                        ? $this->drive->webViewLink($signature->signed_drive_file_id)
                        : null,
                ];
            });
        }

        if ($member->isLeader()) {
            $member->load('leaderProfile.trainings');

            $data['leaderProfile'] = $member->leaderProfile ? [
                'qualification' => $member->leaderProfile->qualification->value,
                'qualification_label' => $member->leaderProfile->qualification->label(),
                'sexual_offenses_certificate_date' => $member->leaderProfile->sexual_offenses_certificate_date?->toDateString(),
                'sexual_offenses_certificate_expires_at' => $member->leaderProfile->sexual_offenses_certificate_expires_at?->toDateString(),
                'certificate_valid' => $member->leaderProfile->hasValidSexualOffensesCertificate(),
                'branches' => $member->leaderProfile->branches ?? [],
                'trainings' => $member->leaderProfile->trainings->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'obtained_at' => $t->obtained_at?->toDateString(),
                    'expires_at' => $t->expires_at?->toDateString(),
                    'expired' => $t->isExpired(),
                ]),
            ] : null;
            $data['qualifications'] = collect(LeaderQualification::cases())->map(fn ($q) => [
                'value' => $q->value, 'label' => $q->label(),
            ]);
        }

        return Inertia::render('Members/Edit', $data);
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $member->update($request->validated());

        return redirect()->route('members.show', $member)->with('success', 'Miembro actualizado correctamente.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        Gate::authorize('delete', $member);

        $member->delete();

        return redirect()->route('members.index')->with('success', 'Miembro eliminado.');
    }

    private function memberSummary(Member $member): array
    {
        return [
            'id' => $member->id,
            'full_name' => $member->full_name,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'role' => $member->role->value,
            'role_label' => $member->role->label(),
            'phone' => $member->phone,
            'email' => $member->email,
            'active' => $member->active,
            'age' => $member->age,
            'birth_date' => $member->birth_date?->format('d/m/Y'),
        ];
    }

    private function memberDetail(Member $member): array
    {
        return [
            'id' => $member->id,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'full_name' => $member->full_name,
            'phone' => $member->phone,
            'email' => $member->email,
            'role' => $member->role->value,
            'role_label' => $member->role->label(),
            'birth_date' => $member->birth_date?->toDateString(),
            'age' => $member->age,
            'active' => $member->active,
            'joined_at' => $member->joined_at?->toDateString(),
            'notes' => $member->notes,
            'is_leader' => $member->isLeader(),
        ];
    }

    private function branchOptions(): array
    {
        return collect(MemberRole::cases())->map(fn (MemberRole $b) => [
            'value' => $b->value,
            'label' => $b->label(),
        ])->all();
    }
}
