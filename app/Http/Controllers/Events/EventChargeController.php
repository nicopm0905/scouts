<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreChargeFromEventRequest;
use App\Models\Charge;
use App\Models\Event;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

/** Crea un cobro ligado a un evento y lo reparte entre los miembros inscritos. */
class EventChargeController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreChargeFromEventRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);
        abort_unless($request->user()->can('charges.manage'), 403);

        $charge = Charge::create([
            ...$request->validated(),
            'event_id' => $event->id,
            'target_branches' => $event->branches,
            'sibling_discount_applies' => false,
            'created_by' => $request->user()->id,
        ]);

        $enrolledMembers = $event->enrollments()->where('enrolled', true)->pluck('member_id');

        foreach ($enrolledMembers as $memberId) {
            $charge->assignments()->firstOrCreate(
                ['member_id' => $memberId],
                ['amount' => $charge->amount, 'status' => 'pending']
            );
        }

        return back()->with('success', 'Cobro creado y repartido entre los inscritos.');
    }
}
