<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ChargeStatus;
use App\Http\Controllers\Controller;
use App\Models\ChargeMember;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Pagos de los scouts a cargo de la cuenta: pendientes e histórico. Solo lectura. */
class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $childIds = $request->user()->childIds() ?: [0];

        $rows = ChargeMember::query()
            ->whereIn('member_id', $childIds)
            ->with(['charge:id,title,type,due_date', 'member:id,first_name,last_name'])
            ->get()
            ->sortByDesc(fn (ChargeMember $cm) => $cm->charge?->due_date ?? $cm->created_at)
            ->map(fn (ChargeMember $cm) => [
                'id' => $cm->id,
                'concept' => $cm->charge?->title,
                'type' => $cm->charge?->type?->label(),
                'child' => $cm->member?->full_name,
                'amount' => (float) $cm->amount,
                'status' => $cm->status->value,
                'status_label' => $cm->status->label(),
                'due_date' => $cm->charge?->due_date?->format('d/m/Y'),
                'paid_at' => $cm->paid_at?->format('d/m/Y'),
            ])
            ->values();

        return Inertia::render('Portal/Payments', [
            'pending' => $rows->where('status', ChargeStatus::Pending->value)->values(),
            'settled' => $rows->where('status', '!=', ChargeStatus::Pending->value)->values(),
            'pendingTotal' => round($rows->where('status', ChargeStatus::Pending->value)->sum('amount'), 2),
            'paymentInfo' => [
                'name' => Setting::get('finance.group_name') ?? config('group.name'),
                'email' => Setting::get('finance.group_email') ?? config('group.contact.email'),
            ],
        ]);
    }
}
