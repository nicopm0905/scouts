<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\MarkChargeMemberPaidRequest;
use App\Models\ChargeMember;
use App\Models\Member;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChargeMemberController extends Controller
{
    use AuthorizesRequests;

    public function markPaid(MarkChargeMemberPaidRequest $request, ChargeMember $chargeMember): RedirectResponse
    {
        $chargeMember->update([
            'notes' => $request->input('notes', $chargeMember->notes),
        ]);
        $chargeMember->markPaid(\App\Enums\PaymentMethod::from($request->validated('payment_method')));

        return back()->with('success', 'Pago registrado.');
    }

    /** Historial de cobros de un miembro concreto (para la vista de control). */
    public function history(Member $member): Response
    {
        $this->authorize('view', \App\Models\Charge::class);

        $assignments = \App\Models\ChargeMember::query()
            ->with('charge')
            ->where('member_id', $member->id)
            ->get()
            ->sortByDesc(fn ($a) => $a->charge->due_date)
            ->values();

        return Inertia::render('Charges/MemberHistory', [
            'member' => [
                'id' => $member->id,
                'name' => $member->full_name,
                'branch' => $member->role->label(),
            ],
            'history' => $assignments->map(fn ($a) => [
                'charge_id' => $a->charge->id,
                'title' => $a->charge->title,
                'type_label' => $a->charge->type->label(),
                'due_date' => $a->charge->due_date?->toDateString(),
                'amount' => (float) $a->amount,
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
                'status_color' => $a->status->badgeColor(),
                'paid_at' => $a->paid_at?->toDateString(),
            ]),
        ]);
    }
}
