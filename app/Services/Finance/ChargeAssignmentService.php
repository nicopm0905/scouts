<?php

namespace App\Services\Finance;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\ChargeMember;
use App\Models\Member;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Crea cobros y reparte el importe entre los miembros destinatarios,
 * aplicando el descuento por hermanos cuando corresponda.
 */
class ChargeAssignmentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $creator): Charge
    {
        return DB::transaction(function () use ($data, $creator) {
            $charge = Charge::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'],
                'due_date' => $data['due_date'] ?? null,
                'type' => $data['type'],
                'event_id' => $data['event_id'] ?? null,
                'target_branches' => $data['target_branches'] ?? null,
                'sibling_discount_applies' => $data['sibling_discount_applies'] ?? false,
                'created_by' => $creator->id,
            ]);

            $members = $this->resolveMembers($data['target_branches'] ?? [], $data['member_ids'] ?? []);
            $this->assignMembers($charge, $members);

            return $charge->fresh(['assignments.member']);
        });
    }

    /**
     * @param  array<int, string>  $branches
     * @param  array<int, int>  $memberIds
     */
    private function resolveMembers(array $branches, array $memberIds): Collection
    {
        $query = Member::query()->active();

        if (empty($branches) && empty($memberIds)) {
            return new Collection();
        }

        $query->where(function ($q) use ($branches, $memberIds) {
            if (! empty($branches)) {
                $q->whereIn('role', $branches);
            }
            if (! empty($memberIds)) {
                $q->orWhereIn('id', $memberIds);
            }
        });

        return $query->get();
    }

    public function assignMembers(Charge $charge, Collection $members): void
    {
        $amounts = $this->computeAmounts($charge, $members);

        foreach ($members as $member) {
            ChargeMember::updateOrCreate(
                ['charge_id' => $charge->id, 'member_id' => $member->id],
                ['amount' => $amounts[$member->id], 'status' => ChargeStatus::Pending]
            );
        }
    }

    /**
     * Calcula el importe efectivo por miembro. Si el cobro aplica descuento por
     * hermanos, dentro de cada familia se ordena por fecha de nacimiento (el mayor
     * paga el importe completo) y a partir del segundo hermano se aplica el
     * porcentaje configurado en Setting::finance.sibling_discount_percent.
     *
     * @return array<int, float>
     */
    private function computeAmounts(Charge $charge, Collection $members): array
    {
        $base = (float) $charge->amount;
        $amounts = [];

        if (! $charge->sibling_discount_applies || ! $charge->type->isQuota()) {
            foreach ($members as $member) {
                $amounts[$member->id] = $base;
            }

            return $amounts;
        }

        $discountPercent = (float) Setting::get('finance.sibling_discount_percent', 0);

        /** @var array<int, array<int, Member>> $byFamily */
        $byFamily = [];

        foreach ($members as $member) {
            $family = $member->families()->first();

            if (! $family) {
                $amounts[$member->id] = $base;

                continue;
            }

            $byFamily[$family->id][] = $member;
        }

        foreach ($byFamily as $familyMembers) {
            usort($familyMembers, fn (Member $a, Member $b) => ($a->birth_date <=> $b->birth_date));

            foreach ($familyMembers as $index => $member) {
                $amounts[$member->id] = $index === 0
                    ? $base
                    : round($base * (1 - $discountPercent / 100), 2);
            }
        }

        return $amounts;
    }
}
