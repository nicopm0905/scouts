<?php

namespace Database\Seeders;

use App\Enums\ChargeStatus;
use App\Enums\ChargeType;
use App\Enums\EventType;
use App\Enums\MemberRole;
use App\Enums\ObjectiveStatus;
use App\Models\Activity;
use App\Models\ActivityMaterial;
use App\Models\Attendance;
use App\Models\BranchPlan;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Charge;
use App\Models\Event;
use App\Models\EventChecklistItem;
use App\Models\HealthRecord;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MemberChangeRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Datos de prueba para las novedades de la Fase 3-4 (asistente de salida, cobro
 * en bloque, revisión de familias, memoria, presupuesto vs real, reuniones del
 * trimestre). Pensado para ejecutarse sobre la BD de demo ya sembrada:
 *   php artisan db:seed --class=Fase4DemoSeeder
 * Es re-ejecutable: limpia lo que crea antes de volver a crearlo.
 */
class Fase4DemoSeeder extends Seeder
{
    public function run(): void
    {
        $year = $this->schoolYear();
        $lobato = MemberRole::Lobato;

        // Las cuentas de demo no reciben correo real: se marcan como verificadas
        // para poder entrar sin pasar por el enlace de verificación.
        User::query()->where('email', 'like', '%@grupo.test')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);

        $kraal = User::where('email', 'lobatos@grupo.test')->first();
        $familia = User::where('email', 'familia@grupo.test')->first();

        $lobatos = Member::query()->where('role', $lobato->value)->where('active', true)->take(10)->get();
        if ($lobatos->count() < 6) {
            $lobatos = $lobatos->concat(Member::factory()->count(8)->branch($lobato)->create());
        }

        $this->cleanup();

        $this->censusFields($lobatos);
        $this->quarterAttendance($lobatos);
        $this->weekMeetings($lobato);
        $event = $this->outing($lobato, $lobatos, $kraal);
        $this->charge($event, $lobatos);
        $this->budget($event);
        $this->branchPlan($lobato, $year);
        $this->familyReviews($familia);

        $this->command?->info('Fase4DemoSeeder: datos de prueba creados (rama Lobato, curso '.$year.').');
    }

    private function cleanup(): void
    {
        Event::where('title', 'Acampada de prueba · Lobato')->get()->each(function (Event $e) {
            $e->budget?->items()->each(fn (BudgetItem $i) => $i->invoices()->delete());
            $e->budget?->items()->delete();
            $e->budget?->delete();
            $e->charges()->each(fn (Charge $c) => $c->assignments()->delete());
            $e->charges()->delete();
            $e->checklistItems()->delete();
            $e->activities()->detach();
            $e->enrollments()->delete();
            $e->forceDelete();
        });
        Activity::whereIn('title', ['Gran juego de rastreo (prueba)', 'Taller de cabuyería (prueba)'])
            ->get()
            ->each(function (Activity $a) {
                $a->materials()->delete();
                $a->events()->detach();
                $a->objectives()->detach();
                $a->delete();
            });
        MemberChangeRequest::whereIn('note', ['[demo] Revisión de curso', '[demo] Cambio de teléfono'])->delete();
        BranchPlan::where('description', '[demo] Plan de rama de prueba')->each(function (BranchPlan $p) {
            $p->objectives()->delete();
            $p->delete();
        });
    }

    private function schoolYear(): string
    {
        $now = Carbon::now();
        $start = $now->month >= 7 ? $now->year : $now->year - 1;

        return $start.'-'.($start + 1);
    }

    /** Épica G: DNI/sexo para el censo MSC + un alta y una baja del curso para la memoria. */
    private function censusFields($members): void
    {
        $curso = Carbon::now()->month >= 7
            ? Carbon::now()->year
            : Carbon::now()->year - 1;

        foreach ($members as $i => $m) {
            $update = [
                'dni' => str_pad((string) (10000000 + $m->id), 8, '0', STR_PAD_LEFT).'X',
                'sex' => $i % 2 === 0 ? 'F' : 'M',
                'address' => 'C. de Ejemplo '.($i + 1).', Jerez',
            ];
            if ($i === 0) {
                $update['joined_at'] = Carbon::create($curso, 10, 1); // alta de este curso
            }
            $m->update($update);
        }

        // Una baja de este curso (el modelo pone left_at solo al desactivar).
        $baja = $members->last();
        if ($baja && $baja->active) {
            $baja->forceFill(['joined_at' => Carbon::create($curso - 2, 9, 1)])->save();
            $baja->update(['active' => false]);
        }
    }

    /** Fase 5 ("Tu semana"): un par de eventos en la semana en curso. */
    private function weekMeetings(MemberRole $branch): void
    {
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        Event::where('title', 'like', '[demo semana]%')->forceDelete();

        Event::create([
            'title' => '[demo semana] Reunión de rama',
            'type' => EventType::Reunion->value,
            'start_at' => $monday->copy()->addDays(5)->setTime(10, 30), // sábado
            'end_at' => $monday->copy()->addDays(5)->setTime(13, 0),
            'location' => 'Local del grupo',
            'branches' => [$branch->value],
        ]);
        Event::create([
            'title' => '[demo semana] Reunión de kraal',
            'type' => EventType::Reunion->value,
            'start_at' => $monday->copy()->addDays(2)->setTime(19, 0), // miércoles
            'end_at' => $monday->copy()->addDays(2)->setTime(20, 30),
            'location' => 'Local del grupo',
            'branches' => [$branch->value],
        ]);
    }

    /** Épica A: ~7 reuniones del trimestre con asistencia variada. */
    private function quarterAttendance($members): void
    {
        $start = Carbon::now()->firstOfQuarter();
        for ($w = 0; $w < 7; $w++) {
            $date = $start->copy()->addWeeks($w)->next(Carbon::SATURDAY);
            if ($date->isFuture()) {
                break;
            }
            foreach ($members as $idx => $m) {
                Attendance::updateOrCreate(
                    ['member_id' => $m->id, 'event_id' => null, 'date' => $date->toDateString()],
                    ['present' => ($idx + $w) % 5 !== 0] // ~80 % presentes
                );
            }
        }
    }

    /** Épica C: acampada con inscripciones, autorizaciones, checklist con tarea y actividades con material. */
    private function outing(MemberRole $branch, $members, ?User $kraal): Event
    {
        $start = Carbon::now()->addWeeks(3)->setTime(17, 0);

        $event = Event::create([
            'title' => 'Acampada de prueba · Lobato',
            'type' => EventType::Acampada->value,
            'start_at' => $start,
            'end_at' => $start->copy()->addDays(2)->setTime(13, 0),
            'location' => 'Albergue de la Sierra',
            'location_city' => 'Grazalema',
            'description' => 'Acampada de fin de semana para probar el asistente de salida.',
            'branches' => [$branch->value],
        ]);

        foreach ($members as $i => $m) {
            $event->members()->attach($m->id, [
                'enrolled' => true,
                'public_token' => Str::random(48),
                'confirmed_at' => $i < 6 ? now() : null, // 6 con autorización, el resto sin
            ]);
        }

        foreach (['Autorización de la Junta de Andalucía', 'Seguro de accidentes', 'Reservar autobús', 'Comprar material de cocina'] as $pos => $label) {
            EventChecklistItem::create([
                'event_id' => $event->id,
                'label' => $label,
                'position' => $pos,
                'done' => $pos < 2,
                'assigned_to' => in_array($pos, [2, 3], true) ? $kraal?->id : null,
                'due_at' => in_array($pos, [2, 3], true) ? now()->addWeeks(2) : null,
            ]);
        }

        $olla = InventoryItem::query()->where('name', 'like', '%Olla%')->first()
            ?? InventoryItem::factory()->create(['name' => 'Olla grande', 'quantity' => 3]);

        $a1 = Activity::create([
            'title' => 'Gran juego de rastreo (prueba)', 'branch' => $branch->value,
            'duration_minutes' => 90, 'day_number' => 'Sábado', 'time_slot' => 'Tarde I', 'activity_number' => 1,
            'development' => "1. Explicar las señales de pista.\n2. Repartir equipos.\n3. Recorrido por el bosque.\n4. Puesta en común.",
        ]);
        $a2 = Activity::create([
            'title' => 'Taller de cabuyería (prueba)', 'branch' => $branch->value,
            'duration_minutes' => 60, 'day_number' => 'Domingo', 'time_slot' => 'Mañana', 'activity_number' => 2,
            'development' => 'Nudos: llano, ballestrinque, as de guía. Montar un pequeño trípode por equipos.',
            'materials_text' => 'Cuerdas finas de práctica (una por lobato)',
        ]);
        $event->activities()->attach([$a1->id, $a2->id]);

        ActivityMaterial::create(['activity_id' => $a1->id, 'name' => 'Cuerda de pista', 'quantity' => 4, 'inventory_item_id' => null]);
        ActivityMaterial::create(['activity_id' => $a2->id, 'name' => $olla->name, 'quantity' => 5, 'inventory_item_id' => $olla->id]);

        return $event;
    }

    /** Épica D: cobro del evento con reparto pendiente (para probar "marcar pagado en bloque"). */
    private function charge(Event $event, $members): void
    {
        $charge = Charge::create([
            'title' => 'Acampada de prueba · Lobato',
            'description' => 'Cuota de la acampada de fin de semana.',
            'amount' => 35,
            'due_date' => now()->addWeeks(2),
            'type' => ChargeType::Salida->value,
            'event_id' => $event->id,
            'target_branches' => $event->branches,
            'sibling_discount_applies' => false,
        ]);

        foreach ($members as $i => $m) {
            $charge->assignments()->create([
                'member_id' => $m->id,
                'amount' => 35,
                'status' => $i < 2 ? ChargeStatus::Paid->value : ChargeStatus::Pending->value,
                'paid_at' => $i < 2 ? now()->subDay() : null,
            ]);
        }
    }

    /** Épica G: presupuesto del evento con partidas y facturas (previsto vs real). */
    private function budget(Event $event): void
    {
        $budget = Budget::create([
            'name' => 'Presupuesto: '.$event->title,
            'event_id' => $event->id,
            'status' => 'active',
        ]);

        $ingresos = $budget->items()->create(['description' => 'Cuotas de las familias', 'type' => 'income', 'amount' => 280]);
        $autobus = $budget->items()->create(['description' => 'Autobús ida y vuelta', 'type' => 'expense', 'amount' => 180]);
        $comida = $budget->items()->create(['description' => 'Comida del finde', 'type' => 'expense', 'amount' => 120]);

        Invoice::factory()->issued()->create([
            'budget_item_id' => $ingresos->id, 'concept' => 'Cuotas cobradas', 'amount' => 245, 'vat' => 0,
            'date' => now()->subDays(3), 'supplier_or_client' => 'Familias Lobato',
        ]);
        Invoice::factory()->create([
            'budget_item_id' => $autobus->id, 'concept' => 'Autocares del Sur', 'amount' => 165.29, 'vat' => 34.71,
            'date' => now()->subDays(2), 'supplier_or_client' => 'Autocares del Sur SL',
        ]);
        Invoice::factory()->create([
            'budget_item_id' => $comida->id, 'concept' => 'Supermercado', 'amount' => 138.45, 'vat' => 0,
            'date' => now()->subDay(), 'supplier_or_client' => 'Supermercado Local',
        ]);
    }

    /** Épica F/G: plan de rama del curso con objetivos por ámbito (para la memoria). */
    private function branchPlan(MemberRole $branch, string $year): void
    {
        $plan = BranchPlan::updateOrCreate(
            ['branch' => $branch->value, 'school_year' => $year],
            ['description' => '[demo] Plan de rama de prueba']
        );
        $plan->objectives()->delete();

        $objs = [
            ['Corporal', 'Hábitos de higiene', 'Que los lobatos interioricen la rutina de aseo en la acampada.', ObjectiveStatus::Logrado],
            ['Carácter', 'Responsabilidad', 'Cada seisena se encarga de una tarea del campamento sin que se lo recuerden.', ObjectiveStatus::Logrado],
            ['Creatividad', 'Expresión', 'Preparar y representar una velada por seisenas.', ObjectiveStatus::EnCurso],
            ['País', 'Servicio', 'Participar en una limpieza del entorno natural.', ObjectiveStatus::EnCurso],
            ['Fe', 'Interioridad', 'Momento de oración sencillo al final del día.', ObjectiveStatus::Pendiente],
            ['Afectividad', 'Convivencia', 'Resolver los conflictos hablando en consejo de seisena.', ObjectiveStatus::Logrado],
        ];
        foreach ($objs as $i => [$area, $content, $desc, $status]) {
            $plan->objectives()->create([
                'development_area' => $area,
                'content' => $content,
                'description' => $desc,
                'term' => intdiv($i, 2) + 1,
                'status' => $status->value,
                'position' => $i,
            ]);
        }
    }

    /** Épica E: revisiones pendientes enviadas por la familia demo (familia@grupo.test). */
    private function familyReviews(?User $familia): void
    {
        if (! $familia) {
            return;
        }

        $children = $familia->children();
        if ($children->isEmpty()) {
            return;
        }

        $first = $children->first();
        HealthRecord::firstOrCreate(['member_id' => $first->id], ['allergies' => 'Ninguna conocida']);

        MemberChangeRequest::updateOrCreate(
            ['member_id' => $first->id, 'status' => 'pending'],
            [
                'submitted_by' => $familia->id,
                'note' => '[demo] Revisión de curso',
                'payload' => [
                    'member' => ['phone' => '655 12 34 56'],
                    'health' => ['allergies' => 'Alergia a los frutos secos (nueva)'],
                    'family' => ['contact_phone' => '600 99 88 77', 'contact_email' => 'nueva.direccion@familia.test'],
                    'consents' => ['rgpd' => true, 'imagen' => true, 'salidas_periodicas' => true],
                    'renewal' => ['continues' => true, 'school_year' => $this->schoolYear()],
                ],
            ]
        );

        if ($children->count() > 1) {
            $second = $children->get(1);
            MemberChangeRequest::updateOrCreate(
                ['member_id' => $second->id, 'status' => 'pending'],
                [
                    'submitted_by' => $familia->id,
                    'note' => '[demo] Cambio de teléfono',
                    'payload' => [
                        'member' => ['phone' => '677 00 11 22'],
                        'renewal' => ['continues' => false, 'school_year' => $this->schoolYear()],
                    ],
                ]
            );
        }
    }
}
