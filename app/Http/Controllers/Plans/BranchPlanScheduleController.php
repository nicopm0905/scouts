<?php

namespace App\Http\Controllers\Plans;

use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plans\ScheduleTermMeetingsRequest;
use App\Models\BranchPlan;
use App\Models\Event;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

/**
 * Genera el calendario del trimestre desde el plan de rama: crea en bloque las
 * reuniones semanales de un tramo de fechas, en un día y hora fijos, para la
 * rama del plan. Los eventos se pueden editar/detallar después uno a uno.
 */
class BranchPlanScheduleController extends Controller
{
    use AuthorizesRequests;

    public function store(ScheduleTermMeetingsRequest $request, BranchPlan $branchPlan): RedirectResponse
    {
        $this->authorize('update', $branchPlan);

        $data = $request->validated();
        $branch = $branchPlan->branch->value;
        [$hour, $minute] = array_map('intval', explode(':', $data['time']));

        $skip = collect($data['skip_dates'] ?? [])
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        $cursor = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->endOfDay();

        // Avanza hasta el primer día de la semana pedido (ISO 1..7).
        while ($cursor->isoWeekday() !== (int) $data['weekday']) {
            $cursor->addDay();
        }

        $created = 0;
        $skipped = 0;

        for (; $cursor->lte($end); $cursor->addWeek()) {
            $date = $cursor->toDateString();

            if (in_array($date, $skip, true)) {
                $skipped++;

                continue;
            }

            // No duplicar si ya hay una reunión de esa rama ese día.
            $exists = Event::query()
                ->where('type', EventType::Reunion->value)
                ->whereDate('start_at', $date)
                ->whereJsonContains('branches', $branch)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            $startAt = $cursor->copy()->setTime($hour, $minute);

            Event::create([
                'title' => 'Reunión '.$branchPlan->branch->label().' · '.$cursor->format('d/m'),
                'type' => EventType::Reunion->value,
                'start_at' => $startAt,
                'end_at' => $startAt->copy()->addMinutes((int) $data['duration_minutes']),
                'location' => $data['location'] ?? null,
                'branches' => [$branch],
                'created_by' => $request->user()->id,
            ]);

            $created++;
        }

        $message = $created === 0
            ? 'No se creó ninguna reunión (ya existían o no había fechas en ese tramo).'
            : "{$created} reuniones creadas".($skipped > 0 ? " · {$skipped} omitidas (ya existían o excluidas)" : '').'.';

        return redirect()
            ->route('events.index', ['branch' => $branch])
            ->with($created === 0 ? 'info' : 'success', $message);
    }
}
