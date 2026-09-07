<?php

namespace App\Services\Plans;

use App\Enums\MscScope;
use App\Models\Activity;
use App\Models\BranchPlan;
use App\Models\BranchPlanObjective;
use App\Models\Event;
use App\Support\MscPlanCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Arma la hoja de programación trimestral en el formato de la delegación:
 * la tabla Ámbitos → Líneas → Contenido → ¿Cómo estamos? → ¿Qué queremos
 * conseguir? → Actividades, el calendario del trimestre, la evaluación y la
 * ficha de cada actividad.
 *
 * Es el mismo documento que el kraal rellenaba a mano en la hoja de cálculo.
 */
class TermPlanSheet
{
    private const TERM_LABELS = [1 => 'PRIMERO', 2 => 'SEGUNDO', 3 => 'TERCERO'];

    /** @return array<string, mixed> */
    public function for(BranchPlan $plan, int $term): array
    {
        [$from, $to] = $this->range($plan->school_year, $term);

        $plan->loadMissing(['objectives.activities.materials']);

        $objectives = $plan->objectives
            ->where('term', $term)
            ->sortBy(fn (BranchPlanObjective $o) => [$this->scopeOrder($o), $o->position]);

        return [
            'group_name' => config('group.name'),
            'branch_label' => $plan->branch->label(),
            'school_year' => $plan->school_year,
            'term' => $term,
            'term_label' => self::TERM_LABELS[$term] ?? (string) $term,
            'from' => $from,
            'to' => $to,
            'rows' => $objectives->map(fn (BranchPlanObjective $o) => $this->row($o))->values()->all(),
            'calendar' => $this->calendar($plan, $from, $to),
            'activity_sheets' => $this->activitySheets($objectives),
        ];
    }

    /** Los ámbitos se imprimen siempre en el orden del impreso oficial. */
    private function scopeOrder(BranchPlanObjective $objective): int
    {
        return match ($objective->scope) {
            MscScope::Responsabilidad => 1,
            MscScope::Pais => 2,
            MscScope::Fe => 3,
            default => 4,
        };
    }

    /** @return array<string, mixed> */
    private function row(BranchPlanObjective $objective): array
    {
        return [
            'scope_label' => $objective->scope?->label() ?? ($objective->development_area ?: '—'),
            'scope_color' => $objective->scope?->printColor() ?? '#475569',
            'line' => $objective->line,
            'content' => $objective->content,
            'current_situation' => $objective->current_situation,
            'goal_verb' => $objective->goal_verb,
            'goal_complement' => $objective->goal_complement,
            'goal_text' => $objective->goalText(),
            'status_label' => $objective->status->label(),
            'evaluation' => $objective->evaluation,
            'activities' => $objective->activities->map(fn (Activity $a) => [
                'type' => $a->activity_type?->label() ?? 'Actividad',
                'title' => $a->title,
                'owner' => $a->owner,
                'date' => $a->scheduled_date?->format('d/m/y'),
            ])->all(),
        ];
    }

    /**
     * Calendario del trimestre: una columna por reunión o salida de la rama,
     * con el día, el mes y lo que se hace ese día.
     *
     * @return array<string, mixed>
     */
    private function calendar(BranchPlan $plan, Carbon $from, Carbon $to): array
    {
        $events = Event::query()
            ->whereBetween('start_at', [$from, $to])
            ->whereJsonContains('branches', $plan->branch->value)
            ->with(['activities' => fn ($q) => $q->orderBy('activity_number')])
            ->orderBy('start_at')
            ->get();

        return [
            'year' => $to->year,
            'columns' => $events->map(fn (Event $event) => [
                'day' => $event->start_at?->format('j'),
                'month' => mb_strtoupper($event->start_at?->translatedFormat('F') ?? ''),
                'activities' => $event->activities->isEmpty()
                    ? $event->title
                    : $event->activities->map(fn (Activity $a) => $a->title)->implode('. '),
            ])->all(),
        ];
    }

    /**
     * Ficha completa de cada actividad del trimestre (nombre, encargado, fecha,
     * sitio, área, subárea, objetivo, duración, materiales, desarrollo y
     * evaluación), tal y como aparece al final de la hoja de la delegación.
     *
     * @param  Collection<int, BranchPlanObjective>  $objectives
     * @return list<array<string, mixed>>
     */
    private function activitySheets(Collection $objectives): array
    {
        $sheets = [];

        foreach ($objectives as $objective) {
            foreach ($objective->activities as $activity) {
                $sheets[$activity->id] = [
                    'title' => $activity->title,
                    'owner' => $activity->owner,
                    'date' => $activity->scheduled_date?->format('d/m/y'),
                    'place' => $activity->place,
                    'scope_label' => $objective->scope?->label() ?? $objective->development_area,
                    'line' => $objective->line,
                    'goal_verb' => $objective->goal_verb,
                    'goal_complement' => $objective->goal_complement ?: $objective->description,
                    'duration' => $activity->duration_minutes ? $activity->duration_minutes.' min' : null,
                    'materials' => $this->materials($activity),
                    'development' => $activity->development,
                    'evaluation' => $activity->evaluation,
                ];
            }
        }

        return array_values($sheets);
    }

    private function materials(Activity $activity): ?string
    {
        if (filled($activity->materials_text)) {
            return $activity->materials_text;
        }

        $listed = $activity->materials->map(fn ($m) => $m->name.' (x'.$m->quantity.')')->implode(', ');

        return $listed !== '' ? $listed : null;
    }

    /**
     * Tramo de fechas del trimestre dentro del curso escolar: el primero va de
     * septiembre a diciembre, el segundo de enero a marzo y el tercero de abril
     * al final del curso.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(string $schoolYear, int $term): array
    {
        [$startYear] = explode('-', $schoolYear);
        $startYear = (int) $startYear;

        return match ($term) {
            1 => [Carbon::create($startYear, 9, 1)->startOfDay(), Carbon::create($startYear, 12, 31)->endOfDay()],
            2 => [Carbon::create($startYear + 1, 1, 1)->startOfDay(), Carbon::create($startYear + 1, 3, 31)->endOfDay()],
            default => [Carbon::create($startYear + 1, 4, 1)->startOfDay(), Carbon::create($startYear + 1, 8, 31)->endOfDay()],
        };
    }

    /** Franjas horarias del impreso, para quien monte la rejilla de estructura. */
    public function timeSlots(): array
    {
        return MscPlanCatalog::timeSlots();
    }
}
