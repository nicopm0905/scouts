<?php

namespace App\Http\Controllers\Members;

use App\Enums\ChangeRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberChangeRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bandeja de secretaría: revisiones de datos que envían las familias desde el
 * portal. Aprobar aplica los cambios al miembro / ficha sanitaria.
 */
class ChangeRequestController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Member::class);

        $visibleMemberIds = Member::query()->visibleTo($request->user())->pluck('id');

        $requests = MemberChangeRequest::query()
            ->whereIn('member_id', $visibleMemberIds)
            ->with(['member:id,first_name,last_name,role', 'submitter:id,name', 'reviewer:id,name'])
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (MemberChangeRequest $r) => [
                'id' => $r->id,
                'member_id' => $r->member_id,
                'member_name' => $r->member?->full_name,
                'branch' => $r->member?->role?->label(),
                'submitted_by' => $r->submitter?->name,
                'submitted_at' => $r->created_at?->toIso8601String(),
                'note' => $r->note,
                'changes' => $this->diff($r),
                'status' => $r->status->value,
                'status_label' => $r->status->label(),
                'status_color' => $r->status->badgeColor(),
                'reviewed_by' => $r->reviewer?->name,
                'review_note' => $r->review_note,
            ]);

        return Inertia::render('Members/ChangeRequests/Index', [
            'requests' => $requests,
            'canSensitive' => $request->user()->can('members.sensitive'),
        ]);
    }

    public function approve(Request $request, MemberChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('update', $changeRequest->member);
        abort_unless($changeRequest->status === ChangeRequestStatus::Pending, 409, 'Esta solicitud ya está resuelta.');

        $changeRequest->apply($request->user(), $request->user()->can('members.sensitive'));

        return back()->with('success', 'Cambios aplicados a la ficha.');
    }

    public function reject(Request $request, MemberChangeRequest $changeRequest): RedirectResponse
    {
        $this->authorize('update', $changeRequest->member);
        abort_unless($changeRequest->status === ChangeRequestStatus::Pending, 409, 'Esta solicitud ya está resuelta.');

        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);

        $changeRequest->reject($request->user(), $data['review_note'] ?? null);

        return back()->with('success', 'Solicitud rechazada.');
    }

    /**
     * Prepara el "antes → después" campo a campo para la vista, sin exponer
     * valores médicos a quien no tiene members.sensitive.
     *
     * @return array<int, array{group:string, field:string, label:string, current:?string, proposed:?string, hidden:bool}>
     */
    private function diff(MemberChangeRequest $r): array
    {
        $labels = [
            'member.phone' => 'Teléfono del scout',
            'member.email' => 'Email del scout',
            'health.allergies' => 'Alergias',
            'health.intolerances' => 'Intolerancias',
            'health.medication' => 'Medicación',
            'health.observations' => 'Observaciones médicas',
        ];

        $canSensitive = auth()->user()->can('members.sensitive');
        $member = $r->member;
        $health = $member?->healthRecord;
        $rows = [];

        foreach (MemberChangeRequest::editableFields() as $group => $fields) {
            foreach ($fields as $field) {
                if (! array_key_exists($field, $r->payload[$group] ?? [])) {
                    continue;
                }

                $hidden = $group === 'health' && ! $canSensitive;
                $current = $group === 'member' ? $member?->{$field} : $health?->{$field};

                $rows[] = [
                    'group' => $group,
                    'field' => $field,
                    'label' => $labels["$group.$field"] ?? $field,
                    'current' => $hidden ? null : $current,
                    'proposed' => $hidden ? null : $r->payload[$group][$field],
                    'hidden' => $hidden,
                ];
            }
        }

        return $rows;
    }
}
