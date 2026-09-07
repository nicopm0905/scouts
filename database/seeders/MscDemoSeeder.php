<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\EventType;
use App\Enums\LeaderQualification;
use App\Enums\MemberRole;
use App\Enums\MscScope;
use App\Enums\ObjectiveStatus;
use App\Models\Activity;
use App\Models\BranchPlan;
use App\Models\BranchPlanObjective;
use App\Models\Event;
use App\Models\LeaderProfile;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Datos de prueba del formato MSC, con el plan de rama Ranger de un curso
 * completo y la acampada de inauguración ya montada: objetivos con su ámbito,
 * actividades con su franja y su número, y todo enlazado.
 *
 * Sirve para ver la ficha de salida y la hoja del trimestre rellenas sin
 * teclear nada:
 *
 *   php artisan db:seed --class=MscDemoSeeder
 *
 * Es idempotente: se puede volver a lanzar sin duplicar.
 */
class MscDemoSeeder extends Seeder
{
    private const SCHOOL_YEAR = '2025-2026';

    public function run(): void
    {
        $plan = BranchPlan::firstOrCreate(
            ['branch' => MemberRole::Ranger->value, 'school_year' => self::SCHOOL_YEAR],
            ['description' => 'Plan de rama de la unidad Ranger para el curso '.self::SCHOOL_YEAR.'.']
        );

        $coordinator = $this->coordinator();

        $outing = $this->outing($coordinator);
        $this->enrolMembers($outing);

        $this->firstTerm($plan, $outing);
        $this->thirdTerm($plan);

        $this->command?->info('Plan de rama Ranger '.self::SCHOOL_YEAR.' y acampada de inauguración listos.');
        $this->command?->info('Abre el evento "Acampada de Inauguración" y descarga la ficha de salida MSC.');
    }

    /**
     * Primer trimestre: los cuatro objetivos y las cinco actividades de la
     * acampada de inauguración, tal y como se rellenó el impreso en papel.
     */
    private function firstTerm(BranchPlan $plan, Event $outing): void
    {
        $rows = [
            [
                'scope' => MscScope::Responsabilidad,
                'line' => 'Destreza',
                'content' => 'Imaginación, ingenio y creatividad',
                'situation' => 'Repetimos siempre el mismo tipo de actividades y cuesta que propongan cosas nuevas.',
                'verb' => 'Desarrollar',
                'complement' => 'la creatividad e imaginación en nuevas actividades',
                'activity' => [
                    'title' => 'Plan de Rama',
                    'type' => ActivityType::Dinamica,
                    'number' => 1,
                    'day' => 'Sábado',
                    'slot' => 'manana',
                    'owner' => 'Patrullas',
                    'duration' => 120,
                    'development' => 'Por patrullas recibirán una línea a trabajar y tendrán que proponer dos actividades para el trimestre.',
                    'materials' => 'Cartulinas, rotuladores',
                ],
            ],
            [
                'scope' => MscScope::Responsabilidad,
                'line' => 'Carácter',
                'content' => 'Responsabilidad, compromiso, el compromiso personal',
                'situation' => 'La unidad se ha renovado con los pases de rama y aún no se sienten Rangers.',
                'verb' => 'Adquirir',
                'complement' => 'compromiso con la unidad ranger',
                'activity' => [
                    'title' => 'Trivial Scout',
                    'type' => ActivityType::Juego,
                    'number' => 2,
                    'day' => 'Sábado',
                    'slot' => 'tarde_1',
                    'owner' => 'Kraal',
                    'duration' => 120,
                    'development' => 'Por patrullas tirarán un dado y avanzarán en un tablero. Todas las preguntas estarán relacionadas con la rama Ranger.',
                    'materials' => 'Tablero, dados, tarjetas de preguntas',
                ],
            ],
            [
                'scope' => MscScope::Pais,
                'line' => 'Comunidad',
                'content' => 'Hermandad scout',
                'situation' => 'Vienen de tres patrullas distintas y muchos no se conocen entre ellos.',
                'verb' => 'Crear',
                'complement' => 'nuevos lazos de amistad entre los miembros de la unidad',
                'activity' => [
                    'title' => 'Dinámica de conocimiento: ¡Bingo!',
                    'type' => ActivityType::Dinamica,
                    'number' => 3,
                    'day' => 'Sábado',
                    'slot' => 'tarde_2',
                    'owner' => 'Tropa',
                    'duration' => 90,
                    'development' => 'Cada ranger introduce en el bombo papeles con características propias; cuando vayan saliendo tendrán que asignarlas a un compañero en su cartón.',
                    'materials' => 'Bombo, papeles, cartones, bolígrafos',
                ],
            ],
            [
                'scope' => MscScope::Fe,
                'line' => 'Sabiduría',
                'content' => 'Evangelio, vida de Jesús y otros personajes bíblicos',
                'situation' => 'Conocen las historias del Evangelio por encima, sin llevarlas a su día a día.',
                'verb' => 'Conocer',
                'complement' => 'parte de la vida de Jesús',
                'activity' => [
                    'title' => 'Dinámica de Fe',
                    'type' => ActivityType::Celebracion,
                    'number' => 4,
                    'day' => 'Sábado',
                    'slot' => 'noche',
                    'owner' => 'Kraal',
                    'duration' => 60,
                    'development' => 'Conoceremos la parábola del Buen Samaritano del Evangelio de San Lucas y la llevaremos a lo que nos pasa en la unidad.',
                    'materials' => 'Biblia, velas',
                ],
            ],
            [
                'scope' => MscScope::Pais,
                'line' => 'Comunidad',
                'content' => 'Nuestra rama y nuestro grupo scout',
                'situation' => 'Falta cerrar el verano y colocar a quien se incorpora desde lobatos.',
                'verb' => 'Realizar',
                'complement' => 'el consejo de unidad',
                'activity' => [
                    'title' => 'Consejo de unidad',
                    'type' => ActivityType::Reunion,
                    'number' => 5,
                    'day' => 'Domingo',
                    'slot' => 'manana',
                    'owner' => 'Kraal',
                    'duration' => 120,
                    'development' => 'Se evaluará a quienes no vinieron en verano. Pases de rama con el grupo.',
                    'materials' => 'Fichas de progresión, pañoletas',
                ],
            ],
        ];

        foreach ($rows as $position => $row) {
            $objective = $this->objective($plan, $row, term: 1, position: $position, status: ObjectiveStatus::EnCurso);
            $activity = $this->activity($row['activity'], '2025-09-27');

            $activity->objectives()->syncWithoutDetaching([$objective->id]);
            $outing->activities()->syncWithoutDetaching([$activity->id]);
        }
    }

    /**
     * Tercer trimestre: reuniones normales, ya evaluadas, para ver la hoja de
     * programación trimestral con su calendario y su evaluación.
     */
    private function thirdTerm(BranchPlan $plan): void
    {
        $rows = [
            [
                'scope' => MscScope::Responsabilidad,
                'line' => 'Habilidades sociales',
                'content' => 'Convivencia, cooperación, confianza, trabajo en grupo',
                'situation' => 'Necesitamos más confianza entre todos los miembros de la unidad.',
                'verb' => 'Mejorar',
                'complement' => 'la confianza entre los miembros de la unidad',
                'evaluation' => 'Creamos el saludo, pero no se llegó a usar en las reuniones.',
                'activity' => [
                    'title' => 'Creamos nuestro saludo secreto',
                    'type' => ActivityType::Dinamica,
                    'number' => 1,
                    'day' => 'Sábado',
                    'slot' => 'tarde_1',
                    'owner' => 'Patrullas',
                    'duration' => 40,
                    'development' => 'Se creará un saludo secreto de la tropa que solo conocerá quien pertenece a ella, y cada patrulla hará una ampliación que solo conozcan quienes la componen.',
                    'materials' => 'No se necesitan',
                    'date' => '2026-05-23',
                ],
            ],
            [
                'scope' => MscScope::Pais,
                'line' => 'Comunidad',
                'content' => 'Relación con la rama y el grupo scout',
                'situation' => 'Nos sentimos del grupo y conocemos otras ramas, pero no el potencial que tenemos como rama ranger dentro del grupo.',
                'verb' => 'Desarrollar',
                'complement' => 'nuestro potencial dentro del grupo scout',
                'evaluation' => null,
                'activity' => [
                    'title' => 'Manualidades Begleri',
                    'type' => ActivityType::Taller,
                    'number' => 2,
                    'day' => 'Sábado',
                    'slot' => 'tarde_1',
                    'owner' => 'Tropa',
                    'duration' => 90,
                    'development' => 'Taller de Begleri, un juguete de habilidad de dos bolas unidas por una cuerda corta con el que se hacen trucos con una sola mano.',
                    'materials' => 'Bolas, cuerdas',
                    'date' => '2026-05-09',
                ],
            ],
            [
                'scope' => MscScope::Fe,
                'line' => 'Fraternidad',
                'content' => 'Comunidad cristiana, convivencia, ayuda',
                'situation' => 'No conocemos lo grande que es nuestra diócesis.',
                'verb' => 'Conocer',
                'complement' => 'nuestra diócesis Asidonia-Jerez',
                'evaluation' => null,
                'activity' => [
                    'title' => 'Conocemos otras Tropas de nuestra diócesis',
                    'type' => ActivityType::Salida,
                    'number' => 3,
                    'day' => 'Sábado',
                    'slot' => 'manana',
                    'owner' => 'Kraal',
                    'duration' => 240,
                    'development' => 'Actividad con los Rangers de la delegación, impulsada por la tropa del grupo scout Los Descalzos.',
                    'materials' => 'No se necesitan',
                    'date' => '2026-05-30',
                ],
            ],
            [
                'scope' => MscScope::Fe,
                'line' => 'Oración',
                'content' => 'Fe en Dios, Palabra, Experiencia de Dios',
                'situation' => 'Podríamos mejorar nuestra relación con Dios haciéndola más cercana; no solemos rezar por nuestra cuenta.',
                'verb' => 'Aprender',
                'complement' => 'diferentes oraciones',
                'evaluation' => null,
                'activity' => [
                    'title' => 'Rezamos las oraciones de nuestros Santos',
                    'type' => ActivityType::Dinamica,
                    'number' => 4,
                    'day' => 'Sábado',
                    'slot' => 'tarde_2',
                    'owner' => 'Tropa',
                    'duration' => 40,
                    'development' => 'Cada ranger traerá la oración de su santo y la compartirá con el resto. Además investigarán si su santo es patrón de alguna causa o lugar.',
                    'materials' => 'Oraciones impresas',
                    'date' => '2026-06-06',
                ],
            ],
        ];

        foreach ($rows as $position => $row) {
            $objective = $this->objective($plan, $row, term: 3, position: $position, status: ObjectiveStatus::Logrado);
            $activity = $this->activity($row['activity'], $row['activity']['date']);

            $activity->objectives()->syncWithoutDetaching([$objective->id]);

            // Cada actividad del trimestre se hace en su reunión semanal.
            $meeting = $this->meeting($row['activity']['date'], $row['activity']['title']);
            $meeting->activities()->syncWithoutDetaching([$activity->id]);
        }
    }

    private function objective(BranchPlan $plan, array $row, int $term, int $position, ObjectiveStatus $status): BranchPlanObjective
    {
        return BranchPlanObjective::updateOrCreate(
            [
                'branch_plan_id' => $plan->id,
                'term' => $term,
                'content' => $row['content'],
            ],
            [
                'scope' => $row['scope']->value,
                'line' => $row['line'],
                'current_situation' => $row['situation'],
                'goal_verb' => $row['verb'],
                'goal_complement' => $row['complement'],
                'description' => $row['verb'].' '.$row['complement'],
                'evaluation' => $row['evaluation'] ?? null,
                'status' => $status->value,
                'position' => $position,
            ]
        );
    }

    private function activity(array $data, string $date): Activity
    {
        return Activity::updateOrCreate(
            ['title' => $data['title']],
            [
                'branch' => MemberRole::Ranger->value,
                'activity_type' => $data['type']->value,
                'owner' => $data['owner'],
                'scheduled_date' => $date,
                'place' => 'Colegio San José',
                'duration_minutes' => $data['duration'],
                'day_number' => $data['day'],
                'time_slot' => $data['slot'],
                'activity_number' => $data['number'],
                'objectives_text' => null,
                'development' => $data['development'],
                'materials_text' => $data['materials'],
            ]
        );
    }

    /** La acampada de inauguración del curso, de viernes a domingo. */
    private function outing(?User $coordinator): Event
    {
        return Event::updateOrCreate(
            ['title' => 'Acampada de Inauguración'],
            [
                'type' => EventType::Acampada->value,
                'start_at' => '2025-09-26 18:00:00',
                'end_at' => '2025-09-28 14:00:00',
                'location' => 'Cáritas el Portal',
                'location_city' => 'El Puerto de Santa María',
                'branches' => [MemberRole::Ranger->value],
                'coordinator_id' => $coordinator?->id,
                'has_eucharist' => true,
                'has_hike' => false,
                'theme_description' => 'Arranque de curso de la unidad Ranger.',
                'description' => 'Acampada de inauguración del curso con la unidad Ranger.',
            ]
        );
    }

    private function meeting(string $date, string $title): Event
    {
        return Event::updateOrCreate(
            ['title' => 'Reunión Ranger '.Carbon::parse($date)->format('d/m/Y')],
            [
                'type' => EventType::Reunion->value,
                'start_at' => $date.' 17:30:00',
                'end_at' => $date.' 19:30:00',
                'location' => 'Colegio San José',
                'branches' => [MemberRole::Ranger->value],
                'description' => $title,
            ]
        );
    }

    /**
     * Catorce rangers y cuatro responsables (dos con titulación y dos sin ella),
     * que son las cifras del impreso de la acampada.
     */
    private function enrolMembers(Event $outing): void
    {
        $rangers = collect(range(1, 14))->map(fn (int $i) => Member::firstOrCreate(
            ['first_name' => 'Ranger', 'last_name' => 'de prueba '.$i],
            ['role' => MemberRole::Ranger->value, 'birth_date' => now()->subYears(14)->toDateString(), 'active' => true]
        ));

        $leaders = collect([
            ['Javier', 'Benítez', LeaderQualification::Director],
            ['Marta', 'Ruiz', LeaderQualification::Monitor],
            ['Pablo', 'Gómez', LeaderQualification::InTraining],
            ['Lucía', 'Moreno', LeaderQualification::None],
        ])->map(function (array $row) {
            $member = Member::firstOrCreate(
                ['first_name' => $row[0], 'last_name' => $row[1]],
                ['role' => MemberRole::Responsable->value, 'birth_date' => now()->subYears(28)->toDateString(), 'active' => true]
            );

            LeaderProfile::updateOrCreate(
                ['member_id' => $member->id],
                [
                    'qualification' => $row[2]->value,
                    'sexual_offenses_certificate_date' => now()->subMonths(3)->toDateString(),
                    'sexual_offenses_certificate_expires_at' => now()->addYears(2)->toDateString(),
                    'branches' => [MemberRole::Ranger->value],
                ]
            );

            return $member;
        });

        foreach ($rangers->concat($leaders) as $member) {
            if ($outing->members()->where('members.id', $member->id)->exists()) {
                continue;
            }

            $outing->members()->attach($member->id, [
                'enrolled' => true,
                'public_token' => Str::random(40),
            ]);
        }
    }

    private function coordinator(): ?User
    {
        return User::query()->where('email', 'lobatos@grupo.test')->first()
            ?? User::query()->where('email', 'admin@grupo.test')->first()
            ?? User::query()->first();
    }
}
