<?php

use App\Enums\ChangeRequestStatus;
use App\Enums\FamilyRelationship;
use App\Enums\MemberRole;
use App\Models\Family;
use App\Models\HealthRecord;
use App\Models\Member;
use App\Models\MemberChangeRequest;

/** Cuenta "familia" ligada a un scout de la rama dada. */
function familiaConScout(MemberRole $branch = MemberRole::Lobato): array
{
    $user = userWithRole('familia');
    $family = Family::factory()->create();
    $child = Member::factory()->branch($branch)->create(['phone' => '600000000']);
    $family->members()->attach($child->id, ['relationship' => FamilyRelationship::Hermano->value]);
    $family->users()->attach($user->id);

    return [$user, $child];
}

it('una familia envía una revisión de datos desde el portal', function () {
    [$user, $child] = familiaConScout();

    $this->actingAs($user)->post("/portal/scouts/{$child->id}/revision", [
        'member' => ['phone' => '611223344'],
        'health' => ['allergies' => 'Frutos secos'],
        'note' => 'Cambió el teléfono',
    ])->assertRedirect();

    $req = MemberChangeRequest::first();
    expect($req)->not->toBeNull()
        ->and($req->status)->toBe(ChangeRequestStatus::Pending)
        ->and($req->payload['member']['phone'])->toBe('611223344')
        ->and($req->payload['health']['allergies'])->toBe('Frutos secos');

    // No se ha tocado la ficha todavía.
    expect($child->fresh()->phone)->toBe('600000000');
});

it('reenviar reemplaza la revisión pendiente en vez de crear otra', function () {
    [$user, $child] = familiaConScout();

    $this->actingAs($user)->post("/portal/scouts/{$child->id}/revision", ['member' => ['phone' => '611']]);
    $this->actingAs($user)->post("/portal/scouts/{$child->id}/revision", ['member' => ['phone' => '622']]);

    expect(MemberChangeRequest::count())->toBe(1)
        ->and(MemberChangeRequest::first()->payload['member']['phone'])->toBe('622');
});

it('una familia no puede enviar revisión de un scout ajeno', function () {
    [$user] = familiaConScout();
    $ajeno = Member::factory()->branch(MemberRole::Pionero)->create();

    $this->actingAs($user)->post("/portal/scouts/{$ajeno->id}/revision", ['member' => ['phone' => '600']])
        ->assertNotFound();
});

it('secretaría aprueba una revisión y se aplican los cambios a la ficha', function () {
    [, $child] = familiaConScout();
    HealthRecord::factory()->create(['member_id' => $child->id, 'allergies' => 'Ninguna']);
    $secretaria = userWithRole('secretaria');

    $req = MemberChangeRequest::create([
        'member_id' => $child->id,
        'status' => ChangeRequestStatus::Pending,
        'payload' => ['member' => ['phone' => '699887766'], 'health' => ['allergies' => 'Polen']],
    ]);

    $this->actingAs($secretaria)->post(route('member-change-requests.approve', $req))->assertRedirect();

    expect($req->fresh()->status)->toBe(ChangeRequestStatus::Approved)
        ->and($child->fresh()->phone)->toBe('699887766')
        ->and($child->fresh()->healthRecord->allergies)->toBe('Polen');
});

it('un responsable sin members.manage no puede aprobar', function () {
    [, $child] = familiaConScout();
    $responsable = userWithRole('responsable', [MemberRole::Lobato->value]);

    $req = MemberChangeRequest::create([
        'member_id' => $child->id,
        'status' => ChangeRequestStatus::Pending,
        'payload' => ['member' => ['phone' => '699']],
    ]);

    $this->actingAs($responsable)->post(route('member-change-requests.approve', $req))->assertForbidden();
    expect($req->fresh()->status)->toBe(ChangeRequestStatus::Pending);
});

it('rechazar deja la ficha intacta y guarda el motivo', function () {
    [, $child] = familiaConScout();
    $secretaria = userWithRole('secretaria');

    $req = MemberChangeRequest::create([
        'member_id' => $child->id,
        'status' => ChangeRequestStatus::Pending,
        'payload' => ['member' => ['phone' => '699887766']],
    ]);

    $this->actingAs($secretaria)->post(route('member-change-requests.reject', $req), [
        'review_note' => 'Ese teléfono no corresponde',
    ])->assertRedirect();

    expect($req->fresh()->status)->toBe(ChangeRequestStatus::Rejected)
        ->and($req->fresh()->review_note)->toBe('Ese teléfono no corresponde')
        ->and($child->fresh()->phone)->toBe('600000000');
});

it('el panel de secretaría muestra las revisiones pendientes', function () {
    [, $child] = familiaConScout();
    $secretaria = userWithRole('secretaria');
    MemberChangeRequest::create([
        'member_id' => $child->id,
        'status' => ChangeRequestStatus::Pending,
        'payload' => ['member' => ['phone' => '699']],
    ]);

    $this->actingAs($secretaria)->get(route('member-change-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Members/ChangeRequests/Index')->has('requests', 1));
});
