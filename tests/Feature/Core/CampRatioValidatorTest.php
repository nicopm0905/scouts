<?php

use App\Enums\LeaderQualification;
use App\Enums\MemberRole;
use App\Models\Event;
use App\Models\LeaderProfile;
use App\Models\Member;
use App\Services\CampRatio\CampRatioValidator;
use Illuminate\Support\Str;

function enroll(Event $event, Member $member): void
{
    $event->members()->attach($member->id, [
        'enrolled' => true,
        'public_token' => Str::random(40),
    ]);
}

it('marca fail cuando faltan responsables para la ratio', function () {
    $camp = Event::factory()->camp()->create(['start_at' => now()->addMonth()]);

    // 30 lobatos (<12) -> ratio 1:10 -> requiere 3 responsables.
    Member::factory()->branch(MemberRole::Lobato)->count(30)->create()
        ->each(fn (Member $m) => enroll($camp, $m));

    // Solo 1 responsable director.
    $director = Member::factory()->leader()->create();
    LeaderProfile::factory()->director()->create(['member_id' => $director->id]);
    enroll($camp, $director);

    $result = app(CampRatioValidator::class)->validate($camp);

    expect($result->status)->toBe('fail')
        ->and($result->requiredLeaders)->toBe(3)
        ->and($result->leaders)->toBe(1)
        ->and($result->ratio)->toBe(10);
});

it('marca fail cuando no hay director', function () {
    $camp = Event::factory()->camp()->create(['start_at' => now()->addMonth()]);

    Member::factory()->branch(MemberRole::Pionero)->count(10)->create()
        ->each(fn (Member $m) => enroll($camp, $m));

    // 1 responsable monitor (suficiente ratio para 10 mayores de 12 -> 1:15) pero sin director.
    $monitor = Member::factory()->leader()->create();
    LeaderProfile::factory()->create(['member_id' => $monitor->id, 'qualification' => LeaderQualification::Monitor]);
    enroll($camp, $monitor);

    $result = app(CampRatioValidator::class)->validate($camp);

    $director = collect($result->checks)->firstWhere('key', 'director');
    expect($director['status'])->toBe('fail')
        ->and($result->status)->toBe('fail');
});

it('avisa cuando un responsable no tiene el certificado vigente', function () {
    $camp = Event::factory()->camp()->create(['start_at' => now()->addMonth()]);

    Member::factory()->branch(MemberRole::Ruta)->count(5)->create()
        ->each(fn (Member $m) => enroll($camp, $m));

    $director = Member::factory()->leader()->create();
    LeaderProfile::factory()->director()->create(['member_id' => $director->id]);
    enroll($camp, $director);

    $lapsed = Member::factory()->leader()->create();
    LeaderProfile::factory()->director()->expiredCertificate()->create(['member_id' => $lapsed->id]);
    enroll($camp, $lapsed);

    $result = app(CampRatioValidator::class)->validate($camp);

    $cert = collect($result->checks)->firstWhere('key', 'certificates');
    expect($cert['status'])->toBe('warning');
});

it('cumple (ok) con ratio, director y certificados vigentes', function () {
    $camp = Event::factory()->camp()->create(['start_at' => now()->addMonth()]);

    Member::factory()->branch(MemberRole::Pionero)->count(10)->create()
        ->each(fn (Member $m) => enroll($camp, $m)); // mayores de 12 -> 1:15 -> requiere 1

    $director = Member::factory()->leader()->create();
    LeaderProfile::factory()->director()->create(['member_id' => $director->id]);
    enroll($camp, $director);

    $result = app(CampRatioValidator::class)->validate($camp);

    expect($result->status)->toBe('ok')
        ->and($result->ratio)->toBe(15);
});
