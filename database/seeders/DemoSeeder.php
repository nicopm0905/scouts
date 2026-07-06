<?php

namespace Database\Seeders;

use App\Enums\ChargeStatus;
use App\Enums\ChargeType;
use App\Enums\ConsentType;
use App\Enums\EventType;
use App\Enums\FamilyRelationship;
use App\Enums\LeaderQualification;
use App\Enums\MemberRole;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Charge;
use App\Models\Consent;
use App\Models\Event;
use App\Models\Family;
use App\Models\HealthRecord;
use App\Models\InventoryItem;
use App\Models\LeaderProfile;
use App\Models\LeaderTraining;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Datos de demostración realistas. Incluye a propósito un campamento que NO cumple
 * la ratio legal para poder ver el CampRatioValidator en rojo.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->createUsers();
        $leaders = $this->createLeaders();
        $members = $this->createMembers();
        $this->createFamilies($members);
        $this->createCharges($members);
        $this->createCalendarEvents();
        $this->createNonCompliantCamp($members, $leaders);
        Activity::factory()->count(20)->create();
        InventoryItem::factory()->count(28)->create();
        InventoryItem::factory()->reviewDue()->count(2)->create();
    }

    private function createUsers(): void
    {
        $users = [
            [UserRole::Admin, 'Coordinación', 'admin@grupo.test', null],
            [UserRole::Secretaria, 'Secretaría', 'secretaria@grupo.test', null],
            [UserRole::Tesoreria, 'Tesorería', 'tesoreria@grupo.test', null],
            [UserRole::Responsable, 'Resp. Lobatos', 'lobatos@grupo.test', [MemberRole::Lobato->value]],
            [UserRole::Responsable, 'Resp. Pioneros', 'pioneros@grupo.test', [MemberRole::Pionero->value]],
        ];

        foreach ($users as [$role, $name, $email, $branches]) {
            $user = User::factory()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'branches' => $branches,
            ]);
            $user->assignRole($role->value);
        }
    }

    /** 8 responsables con distintas cualificaciones. */
    private function createLeaders(): array
    {
        $qualifications = [
            LeaderQualification::Director,
            LeaderQualification::Director,
            LeaderQualification::Monitor,
            LeaderQualification::Monitor,
            LeaderQualification::Monitor,
            LeaderQualification::InTraining,
            LeaderQualification::InTraining,
            LeaderQualification::None,
        ];

        $leaders = [];
        foreach ($qualifications as $i => $q) {
            $member = Member::factory()->leader()->create();
            $profile = LeaderProfile::factory()->create([
                'member_id' => $member->id,
                'qualification' => $q,
                'branches' => [fake()->randomElement(MemberRole::branches())->value],
            ]);

            // Uno con certificado caducado para las alertas.
            if ($i === 7) {
                $profile->update([
                    'sexual_offenses_certificate_date' => now()->subYears(4),
                    'sexual_offenses_certificate_expires_at' => now()->subMonths(2),
                ]);
            }

            LeaderTraining::factory()->count(fake()->numberBetween(1, 3))->create([
                'leader_profile_id' => $profile->id,
            ]);

            $leaders[] = $member;
        }

        return $leaders;
    }

    /** ~60 miembros repartidos por ramas. */
    private function createMembers(): array
    {
        $distribution = [
            MemberRole::Castor->value => 10,
            MemberRole::Lobato->value => 16,
            MemberRole::Ranger->value => 14,
            MemberRole::Pionero->value => 12,
            MemberRole::Ruta->value => 8,
        ];

        $members = [];
        foreach ($distribution as $role => $count) {
            $branch = MemberRole::from($role);
            for ($i = 0; $i < $count; $i++) {
                $member = Member::factory()->branch($branch)->create();
                HealthRecord::factory()->create(['member_id' => $member->id]);
                foreach (ConsentType::cases() as $type) {
                    Consent::factory()->ofType($type)->create(['member_id' => $member->id]);
                }
                $members[] = $member;
            }
        }

        return $members;
    }

    /** 3 familias con hermanos. */
    private function createFamilies(array $members): void
    {
        $pool = collect($members)->shuffle();

        for ($f = 0; $f < 3; $f++) {
            $family = Family::factory()->create();
            $siblings = $pool->splice(0, 2); // 2 hermanos
            foreach ($siblings as $sibling) {
                $family->members()->attach($sibling->id, ['relationship' => FamilyRelationship::Hermano->value]);
            }
            // Un tutor de contacto (miembro ficticio adulto).
            $tutor = Member::factory()->create(['role' => MemberRole::Ruta, 'active' => false]);
            $family->members()->attach($tutor->id, ['relationship' => FamilyRelationship::Madre->value]);
        }
    }

    /** Cobros mixtos: pagados y pendientes. */
    private function createCharges(array $members): void
    {
        $memberCollection = collect($members);

        // Cuota anual a todos.
        $annual = Charge::factory()->create([
            'title' => 'Cuota anual 2026-2027',
            'type' => ChargeType::CuotaAnual,
            'amount' => 60,
            'sibling_discount_applies' => true,
        ]);
        foreach ($memberCollection as $i => $member) {
            $paid = $i % 3 !== 0; // ~2/3 pagados
            $annual->members()->attach($member->id, [
                'amount' => 60,
                'status' => $paid ? ChargeStatus::Paid->value : ChargeStatus::Pending->value,
                'paid_at' => $paid ? now()->subDays(fake()->numberBetween(1, 40)) : null,
                'payment_method' => $paid ? fake()->randomElement(PaymentMethod::values()) : null,
            ]);
        }

        // Cobro de una salida a media rama.
        $outing = Charge::factory()->create([
            'title' => 'Salida de convivencia',
            'type' => ChargeType::Salida,
            'amount' => 15,
        ]);
        foreach ($memberCollection->random(20) as $member) {
            $outing->members()->attach($member->id, [
                'amount' => 15,
                'status' => fake()->randomElement([ChargeStatus::Paid->value, ChargeStatus::Pending->value]),
            ]);
        }
    }

    /** Eventos variados repartidos por el calendario (reuniones semanales, salidas, consejos). */
    private function createCalendarEvents(): void
    {
        $allBranches = MemberRole::values();

        // Reuniones semanales de grupo: sábados por la tarde, de -4 a +8 semanas.
        $saturday = now()->startOfWeek()->addDays(5)->setTime(17, 0);
        for ($w = -4; $w <= 8; $w++) {
            $start = (clone $saturday)->addWeeks($w);
            Event::create([
                'title' => 'Reunión de grupo',
                'type' => EventType::Reunion,
                'start_at' => $start,
                'end_at' => (clone $start)->addHours(2),
                'location' => 'Local del grupo',
                'description' => 'Reunión semanal de todas las ramas.',
                'branches' => $allBranches,
            ]);
        }

        // Un par de salidas y un consejo de grupo alrededor de hoy.
        Event::create([
            'title' => 'Salida de senderismo',
            'type' => EventType::Salida,
            'start_at' => now()->addDays(9)->setTime(9, 0),
            'end_at' => now()->addDays(9)->setTime(18, 30),
            'location' => 'Sierra Norte',
            'description' => 'Salida de día para rangers y pioneros.',
            'branches' => [MemberRole::Ranger->value, MemberRole::Pionero->value],
        ]);

        Event::create([
            'title' => 'Acampada de otoño',
            'type' => EventType::Acampada,
            'start_at' => now()->addDays(18)->setTime(16, 0),
            'end_at' => now()->addDays(20)->setTime(13, 0),
            'location' => 'Albergue El Robledal',
            'description' => 'Acampada de fin de semana de lobatos.',
            'branches' => [MemberRole::Lobato->value],
        ]);

        Event::create([
            'title' => 'Consejo de grupo',
            'type' => EventType::ConsejoGrupo,
            'start_at' => now()->addDays(3)->setTime(20, 0),
            'end_at' => now()->addDays(3)->setTime(21, 30),
            'location' => 'Local del grupo',
            'description' => 'Reunión mensual del equipo de responsables.',
            'branches' => [],
        ]);
    }

    /** Campamento con inscripciones que NO cumple ratio (para el validador). */
    private function createNonCompliantCamp(array $members, array $leaders): void
    {
        $camp = Event::factory()->camp()->create([
            'title' => 'Campamento de verano 2027',
            'start_at' => now()->addMonths(2),
            'end_at' => now()->addMonths(2)->addDays(10),
            'location' => 'Sierra de Cazorla',
            'branches' => MemberRole::values(),
        ]);

        // Inscribimos 40 participantes menores...
        $participants = collect($members)->filter(
            fn (Member $m) => $m->role !== MemberRole::Responsable && $m->active
        )->take(40);

        foreach ($participants as $member) {
            $camp->members()->attach($member->id, [
                'enrolled' => true,
                'public_token' => Str::random(40),
                'confirmed_at' => fake()->boolean(60) ? now() : null,
            ]);
        }

        // ...pero solo 2 responsables (ratio insuficiente: se necesitarían ~4).
        foreach (collect($leaders)->take(2) as $leader) {
            $camp->members()->attach($leader->id, [
                'enrolled' => true,
                'public_token' => Str::random(40),
                'confirmed_at' => now(),
            ]);
        }

        // Checklist legal del evento.
        foreach ([
            'Autorización Junta de Andalucía',
            'Póliza de seguro de la actividad',
            'Listado de asistentes',
            'Fichas médicas recopiladas',
        ] as $pos => $label) {
            $camp->checklistItems()->create(['label' => $label, 'done' => $pos < 2, 'position' => $pos]);
        }
    }
}
