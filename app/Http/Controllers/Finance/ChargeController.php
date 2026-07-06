<?php

namespace App\Http\Controllers\Finance;

use App\Enums\ChargeStatus;
use App\Enums\ChargeType;
use App\Enums\MemberRole;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreChargeRequest;
use App\Jobs\SendPaymentReminderJob;
use App\Models\Charge;
use App\Models\ChargeMember;
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
            ->withSum(['assignments as collected_sum' => fn ($q) => $q->where('status', ChargeStatus::Paid->value)], 'amount')
            ->withSum(['assignments as pending_sum' => fn ($q) => $q->where('status', ChargeStatus::Pending->value)], 'amount')
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
                'total_collected' => (float) $charge->collected_sum,
                'total_pending' => (float) $charge->pending_sum,
                'sibling_discount_applies' => $charge->sibling_discount_applies,
            ]);

        return Inertia::render('Charges/Index', [
            'charges' => $charges,
            'summary' => $this->summary(),
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

    public function show(Request $request, Charge $charge): Response
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
                'reminded' => $a->reminder_sent_at !== null,
            ]),
            'summary' => [
                'expected' => (float) $charge->assignments->sum('amount'),
                'collected' => (float) $charge->assignments->where('status', ChargeStatus::Paid)->sum('amount'),
                'pending' => (float) $charge->assignments->where('status', ChargeStatus::Pending)->sum('amount'),
                'paid_count' => $charge->assignments->where('status', ChargeStatus::Paid)->count(),
                'pending_count' => $charge->assignments->where('status', ChargeStatus::Pending)->count(),
                'total_count' => $charge->assignments->count(),
            ],
            'paymentMethods' => array_map(fn (PaymentMethod $m) => ['value' => $m->value, 'label' => $m->label()], PaymentMethod::cases()),
            'can' => ['manage' => $request->user()->can('charges.manage')],
        ]);
    }

    /** Envía recordatorio de pago a los miembros con el cobro pendiente (aún no avisados). */
    public function remind(Request $request, Charge $charge): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $charge);

        $pending = $charge->assignments()
            ->where('status', ChargeStatus::Pending->value)
            ->whereNull('reminder_sent_at')
            ->pluck('id');

        foreach ($pending as $id) {
            SendPaymentReminderJob::dispatch($id);
        }

        $message = $pending->isEmpty()
            ? 'No hay pendientes sin avisar.'
            : "Recordatorio programado para {$pending->count()} miembro(s).";

        return back()->with('success', $message);
    }

    /** KPIs globales de tesorería (todos los cobros). */
    private function summary(): array
    {
        $expected = (float) ChargeMember::query()->sum('amount');
        $collected = (float) ChargeMember::query()->where('status', ChargeStatus::Paid->value)->sum('amount');
        $pending = (float) ChargeMember::query()->where('status', ChargeStatus::Pending->value)->sum('amount');

        return [
            'expected' => $expected,
            'collected' => $collected,
            'pending' => $pending,
            'pending_count' => ChargeMember::query()->where('status', ChargeStatus::Pending->value)->count(),
            'collection_rate' => $expected > 0 ? round($collected / $expected * 100) : 0,
            'charges_count' => Charge::query()->count(),
        ];
    }

    public function destroy(Charge $charge): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $charge);

        $charge->delete();

        return redirect()->route('charges.index')->with('success', 'Cobro eliminado.');
    }
}
