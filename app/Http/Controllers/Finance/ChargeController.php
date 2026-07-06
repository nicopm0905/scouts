<?php

namespace App\Http\Controllers\Finance;

use App\Enums\ChargeStatus;
use App\Enums\ChargeType;
use App\Enums\MemberRole;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreChargeRequest;
use App\Models\Charge;
use App\Models\Member;
use App\Services\Finance\ChargeAssignmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChargeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private ChargeAssignmentService $service)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Charge::class);

        $charges = Charge::query()
            ->withCount('assignments')
            ->withCount(['assignments as paid_count' => fn ($q) => $q->where('status', ChargeStatus::Paid->value)])
            ->withSum('assignments', 'amount')
            ->latest()
            ->get()
            ->map(fn (Charge $charge) => [
                'id' => $charge->id,
                'title' => $charge->title,
                'type' => $charge->type->value,
                'type_label' => $charge->type->label(),
                'amount' => (float) $charge->amount,
                'due_date' => $charge->due_date?->toDateString(),
                'assignments_count' => $charge->assignments_count,
                'paid_count' => $charge->paid_count,
                'total_expected' => (float) $charge->assignments_sum_amount,
                'sibling_discount_applies' => $charge->sibling_discount_applies,
            ]);

        return Inertia::render('Charges/Index', [
            'charges' => $charges,
            'chargeTypes' => array_map(fn (ChargeType $t) => ['value' => $t->value, 'label' => $t->label()], ChargeType::cases()),
            'branches' => array_map(fn (MemberRole $b) => ['value' => $b->value, 'label' => $b->label()], MemberRole::branches()),
            'members' => Member::query()->active()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'role'])
                ->map(fn (Member $m) => ['id' => $m->id, 'name' => $m->full_name, 'role' => $m->role->value]),
        ]);
    }

    public function store(StoreChargeRequest $request): \Illuminate\Http\RedirectResponse
    {
        $charge = $this->service->create($request->validated(), $request->user());

        return redirect()->route('charges.show', $charge)->with('success', 'Cobro creado y repartido entre los miembros seleccionados.');
    }

    public function show(Charge $charge): Response
    {
        $this->authorize('view', $charge);

        $charge->load(['assignments.member']);

        return Inertia::render('Charges/Show', [
            'charge' => [
                'id' => $charge->id,
                'title' => $charge->title,
                'description' => $charge->description,
                'amount' => (float) $charge->amount,
                'due_date' => $charge->due_date?->toDateString(),
                'type_label' => $charge->type->label(),
                'sibling_discount_applies' => $charge->sibling_discount_applies,
            ],
            'assignments' => $charge->assignments->map(fn ($a) => [
                'id' => $a->id,
                'member_id' => $a->member_id,
                'member_name' => $a->member->full_name,
                'branch' => $a->member->role->label(),
                'amount' => (float) $a->amount,
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
                'status_color' => $a->status->badgeColor(),
                'paid_at' => $a->paid_at?->toDateString(),
                'payment_method' => $a->payment_method?->value,
            ]),
            'paymentMethods' => array_map(fn (PaymentMethod $m) => ['value' => $m->value, 'label' => $m->label()], PaymentMethod::cases()),
        ]);
    }

    public function destroy(Charge $charge): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $charge);

        $charge->delete();

        return redirect()->route('charges.index')->with('success', 'Cobro eliminado.');
    }
}
