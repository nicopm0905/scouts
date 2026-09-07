<?php

namespace App\Services\Events;

use App\Enums\LeaderQualification;
use App\Enums\MemberRole;
use App\Enums\MscScope;
use App\Models\Activity;
use App\Models\BranchPlanObjective;
use App\Models\Event;
use App\Models\Member;
use App\Support\MscPlanCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ficha de salida de la Delegación Diocesana del MSC: los DATOS SALIDA, las tres
 * tablas de ámbito (Responsabilidad, País y Fe) y la rejilla ESTRUCTURA con las
 * actividades repartidas por día y franja horaria.
 *
 * Sustituye al impreso que el kraal rellenaba a mano antes de cada campada.
 */
class MscOutingSheet
{
    /** El impreso reserva cuatro filas por ámbito. */
    private const ROWS_PER_SCOPE = 4;

    /** @return array<string, mixed> */
    public function for(Event $event): array
    {
        $event->loadMissing([
            'coordinator',
            'activities.objectives',
        ]);

        $counts = $this->headcount($event);

        return [
            'coordinator' => $event->coordinator?->name,
            'school_year' => $this->schoolYear($event->start_at),
            'group_name' => config('group.name'),
            'branches' => $this->branchLabels($event),
            'children' => $counts['children'],
            'leaders_untrained' => $counts['leaders_untrained'],
            'leaders_trained' => $counts['leaders_trained'],
            'dates' => $this->dateLabel($event),
            'location' => $event->location_city
                ? trim($event->location.' — '.$event->location_city, ' —')
                : $event->location,
            'scopes' => $this->scopeTables($event),
            'structure' => $this->structure($event),
            'unplaced' => $this->unplaced,
        ];
    }

    /**
     * Actividades del evento que no caben en la rejilla porque les falta la
     * franja horaria. No se tiran: se listan al pie para que el kraal las vea.
     *
     * @var list<array{title: string, reason: string}>
     */
    private array $unplaced = [];

    /**
     * Las tres tablas del impreso: una fila por objetivo con su contenido, el
     * objetivo redactado, la actividad que lo trabaja y su número.
     *
     * @return list<array<string, mixed>>
     */
    private function scopeTables(Event $event): array
    {
        $byScope = $this->objectivesByScope($event);

        return collect(MscScope::cases())->map(function (MscScope $scope) use ($byScope) {
            $rows = ($byScope[$scope->value] ?? collect())
                ->map(fn (array $entry) => [
                    'content' => $entry['objective']->content,
                    'goal' => $entry['objective']->goalText(),
                    'activity' => $entry['activity']?->title,
                    'number' => $entry['activity']?->activity_number,
                ])
                ->values()
                ->all();

            // El impreso siempre lleva cuatro filas: se dejan en blanco las que sobran.
            $blanks = max(0, self::ROWS_PER_SCOPE - count($rows));

            return [
                'key' => $scope->value,
                'label' => mb_strtoupper($scope->label()),
                'color' => $scope->printColor(),
                'rows' => $rows,
                'blank_rows' => $blanks,
            ];
        })->all();
    }

    /**
     * Objetivos del plan de rama que trabajan las actividades del evento,
     * agrupados por ámbito y emparejados con la actividad que los desarrolla.
     *
     * @return array<string, Collection<int, array{objective: BranchPlanObjective, activity: ?Activity}>>
     */
    private function objectivesByScope(Event $event): array
    {
        $pairs = collect();

        foreach ($event->activities as $activity) {
            foreach ($activity->objectives as $objective) {
                $pairs->push(['objective' => $objective, 'activity' => $activity]);
            }
        }

        return $pairs
            ->filter(fn (array $pair) => $pair['objective']->scope !== null)
            ->sortBy(fn (array $pair) => $pair['activity']?->activity_number ?? PHP_INT_MAX)
            ->groupBy(fn (array $pair) => $pair['objective']->scope->value)
            ->all();
    }

    /**
     * Rejilla ESTRUCTURA: una columna por día de la salida y una fila por franja
     * (mañana, tarde I, tarde II y noche), con el número y el desarrollo de la
     * actividad que toca en cada hueco.
     *
     * @return array<string, mixed>
     */
    private function structure(Event $event): array
    {
        $days = $this->days($event);

        // Cada actividad va a una sola casilla: primero se decide su columna.
        $placed = [];
        foreach ($event->activities as $activity) {
            $slotKey = $this->slotKey($activity);

            if ($slotKey === null) {
                $this->unplaced[] = [
                    'title' => $activity->title,
                    'reason' => 'Sin franja horaria',
                ];

                continue;
            }

            $placed[$slotKey][$this->dayIndex($activity, $days)][] = [
                'number' => $activity->activity_number,
                'title' => $activity->title,
                'development' => $activity->development,
            ];
        }

        $rows = [];
        foreach (MscPlanCatalog::timeSlots() as $slotKey => $slot) {
            $cells = [];
            foreach (array_keys($days) as $index) {
                $cells[] = $placed[$slotKey][$index] ?? [];
            }

            $rows[] = [
                'label' => mb_strtoupper($slot['label']),
                'from' => $slot['from'],
                'to' => $slot['to'],
                'cells' => $cells,
            ];
        }

        return ['days' => $days, 'rows' => $rows];
    }

    /**
     * Los días de la salida, etiquetados con el día de la semana como en el
     * impreso (VIERNES, SÁBADO, DOMINGO).
     *
     * @return list<array{label: string, date: string}>
     */
    private function days(Event $event): array
    {
        $start = $event->start_at?->copy()->startOfDay();

        if (! $start) {
            return [];
        }

        $end = ($event->end_at?->copy() ?? $start)->startOfDay();
        $days = [];

        for ($day = $start; $day->lte($end) && count($days) < 7; $day = $day->copy()->addDay()) {
            $days[] = [
                'label' => mb_strtoupper($day->translatedFormat('l')),
                'date' => $day->format('d/m/Y'),
            ];
        }

        return $days;
    }

    /**
     * Franja de la actividad. Acepta la clave del catálogo (`tarde_1`) y también
     * la etiqueta escrita a mano de las fichas antiguas ("Tarde I").
     */
    private function slotKey(Activity $activity): ?string
    {
        $written = trim((string) $activity->time_slot);

        if ($written === '') {
            return null;
        }

        foreach (array_keys(MscPlanCatalog::timeSlots()) as $key) {
            if ($written === $key) {
                return $key;
            }

            if ($this->normalize($written) === $this->normalize(MscPlanCatalog::timeSlotLabel($key) ?? '')) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Columna del día. El día se apunta a mano, así que se admite el nombre del
     * día ("Sábado", "sabado", "SABADO"), la fecha (27/09/2025 o 2025-09-27) y
     * la forma "Día 2". Lo que no se reconozca cae en la primera columna, para
     * que ninguna actividad desaparezca del impreso.
     *
     * @param  list<array{label: string, date: string}>  $days
     */
    private function dayIndex(Activity $activity, array $days): int
    {
        $written = trim((string) $activity->day_number);

        if ($written === '' || $days === []) {
            return 0;
        }

        $normalized = $this->normalize($written);

        foreach ($days as $index => $day) {
            if ($normalized === $this->normalize($day['label']) || $written === $day['date']) {
                return $index;
            }
        }

        // "Día 2", "Dia 2" o simplemente "2": el número es el orden del día.
        if (preg_match('/^(?:dia\s*)?(\d{1,2})$/', $normalized, $matches)) {
            $ordinal = (int) $matches[1] - 1;

            if ($ordinal >= 0 && $ordinal < count($days)) {
                return $ordinal;
            }
        }

        // Una fecha en cualquier formato reconocible.
        try {
            $date = Carbon::parse($written)->format('d/m/Y');

            foreach ($days as $index => $day) {
                if ($date === $day['date']) {
                    return $index;
                }
            }
        } catch (\Throwable) {
            // No era una fecha: se queda en la primera columna.
        }

        return 0;
    }

    /** Minúsculas y sin tildes, para comparar lo que se escribe a mano. */
    private function normalize(string $value): string
    {
        $lower = mb_strtolower(trim($value));

        return strtr($lower, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        ]);
    }

    /** @return array{children: int, leaders_untrained: int, leaders_trained: int} */
    private function headcount(Event $event): array
    {
        $enrolled = $event->members()
            ->wherePivot('enrolled', true)
            ->with('leaderProfile')
            ->get();

        $leaders = $enrolled->filter(fn (Member $m) => $m->role === MemberRole::Responsable);

        $trained = $leaders->filter(fn (Member $m) => in_array(
            $m->leaderProfile?->qualification,
            [LeaderQualification::Monitor, LeaderQualification::Director],
            true
        ))->count();

        return [
            'children' => $enrolled->count() - $leaders->count(),
            'leaders_trained' => $trained,
            'leaders_untrained' => $leaders->count() - $trained,
        ];
    }

    /** @return list<string> */
    private function branchLabels(Event $event): array
    {
        return collect($event->branches ?? [])
            ->map(fn (string $branch) => MemberRole::tryFrom($branch)?->label() ?? $branch)
            ->all();
    }

    private function dateLabel(Event $event): ?string
    {
        if (! $event->start_at) {
            return null;
        }

        if (! $event->end_at || $event->end_at->isSameDay($event->start_at)) {
            return $event->start_at->format('d/m/y');
        }

        return $event->start_at->format('d/m/y').' — '.$event->end_at->format('d/m/y');
    }

    /** Curso escolar al que pertenece la salida: de septiembre a agosto. */
    private function schoolYear(?Carbon $date): ?string
    {
        if (! $date) {
            return null;
        }

        $start = $date->month >= 9 ? $date->year : $date->year - 1;

        return $start.'-'.($start + 1);
    }
}
