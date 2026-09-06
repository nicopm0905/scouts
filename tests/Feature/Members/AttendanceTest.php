<?php

use App\Enums\MemberRole;
use App\Models\Attendance;
use App\Models\Member;

it('un responsable ve la rejilla de asistencia de su rama', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    Member::factory()->count(2)->branch(MemberRole::Lobato)->create();
    Member::factory()->branch(MemberRole::Pionero)->create();

    $response = $this->actingAs($user)->get(route('attendance.index', ['branch' => 'lobato']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('Members/Attendance')->has('members', 2));
});

it('guarda la asistencia de la rama en un clic', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $members = Member::factory()->count(3)->branch(MemberRole::Lobato)->create();

    $attendance = $members->map(fn ($m, $i) => ['member_id' => $m->id, 'present' => $i !== 1])->values()->all();

    $response = $this->actingAs($user)->post(route('attendance.store'), [
        'branch' => MemberRole::Lobato->value,
        'date' => '2026-07-06',
        'attendance' => $attendance,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('attendances', [
        'member_id' => $members[0]->id, 'date' => '2026-07-06', 'present' => true,
    ]);
    $this->assertDatabaseHas('attendances', [
        'member_id' => $members[1]->id, 'date' => '2026-07-06', 'present' => false,
    ]);
});

it('guardar dos veces la misma fecha actualiza en vez de duplicar', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $member = Member::factory()->branch(MemberRole::Lobato)->create();

    $payload = [
        'branch' => MemberRole::Lobato->value,
        'date' => '2026-07-06',
        'attendance' => [['member_id' => $member->id, 'present' => true]],
    ];

    $this->actingAs($user)->post(route('attendance.store'), $payload);
    $payload['attendance'][0]['present'] = false;
    $this->actingAs($user)->post(route('attendance.store'), $payload);

    expect(Attendance::where('member_id', $member->id)->count())->toBe(1);
    expect(Attendance::where('member_id', $member->id)->first()->present)->toBeFalse();
});

it('un responsable no puede escribir asistencia de miembros de otra rama (IDOR)', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $otherBranchMember = Member::factory()->branch(MemberRole::Pionero)->create();

    $response = $this->actingAs($user)->post(route('attendance.store'), [
        'branch' => MemberRole::Lobato->value,
        'date' => '2026-07-06',
        'attendance' => [['member_id' => $otherBranchMember->id, 'present' => true]],
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('attendances', ['member_id' => $otherBranchMember->id]);
});

it('guarda y devuelve la incidencia (notes) de un miembro', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $member = Member::factory()->branch(MemberRole::Lobato)->create();

    $this->actingAs($user)->post(route('attendance.store'), [
        'branch' => MemberRole::Lobato->value,
        'date' => '2026-07-06',
        'attendance' => [['member_id' => $member->id, 'present' => true, 'notes' => 'Se fue antes con su madre']],
    ])->assertRedirect();

    $this->assertDatabaseHas('attendances', [
        'member_id' => $member->id,
        'present' => true,
        'notes' => 'Se fue antes con su madre',
    ]);

    // La rejilla devuelve la nota guardada.
    $this->actingAs($user)
        ->get(route('attendance.index', ['branch' => 'lobato', 'date' => '2026-07-06']))
        ->assertInertia(fn ($page) => $page->where('members.0.notes', 'Se fue antes con su madre'));
});

it('familia sin permiso de asistencia recibe 403', function () {
    $user = userWithRole('familia');

    $this->actingAs($user)->get(route('attendance.index'))->assertForbidden();
});

it('el resumen del trimestre cuenta las sesiones y la asistencia media de la rama', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $members = Member::factory()->count(4)->branch(MemberRole::Lobato)->create();

    // Sesión 1: 3 de 4 presentes. Sesión 2: 4 de 4. Media = (75 + 100) / 2 = 87,5 -> 88.
    foreach ($members as $i => $m) {
        Attendance::factory()->create(['member_id' => $m->id, 'event_id' => null, 'date' => now()->startOfQuarter()->addDay(), 'present' => $i !== 0]);
        Attendance::factory()->create(['member_id' => $m->id, 'event_id' => null, 'date' => now()->startOfQuarter()->addWeek(), 'present' => true]);
    }

    $this->actingAs($user)->get(route('attendance.index', ['branch' => 'lobato']))
        ->assertInertia(fn ($page) => $page
            ->where('stats.sessions', 2)
            ->where('stats.avg_present', 88));
});
