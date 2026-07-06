<?php

use App\Enums\MemberRole;
use App\Models\Member;
use App\Models\Minute;
use App\Services\Drive\FakeDriveService;

beforeEach(function () {
    FakeDriveService::reset();
});

it('una secretaria puede crear un acta con asistentes y puntos, generando y subiendo el PDF', function () {
    $secretaria = userWithRole('secretaria');
    $attendees = Member::factory()->count(2)->create(['role' => MemberRole::Responsable]);

    $response = $this->actingAs($secretaria)->post(route('minutes.store'), [
        'title' => 'Reunión de consejo julio',
        'type' => 'actas_consejo',
        'held_on' => now()->format('Y-m-d'),
        'location' => 'Local scout',
        'attendee_ids' => $attendees->pluck('id')->all(),
        'items' => [
            ['topic' => 'Revisión presupuesto', 'discussion' => 'Se revisan cuentas', 'agreement' => 'Aprobado por unanimidad'],
            ['topic' => 'Campamento de verano', 'discussion' => null, 'agreement' => 'Se confirma fecha'],
        ],
    ]);

    $response->assertRedirect();

    $minute = Minute::firstWhere('title', 'Reunión de consejo julio');
    expect($minute)->not->toBeNull()
        ->and($minute->type)->toBe('actas_consejo')
        ->and($minute->attendees)->toHaveCount(2)
        ->and($minute->items)->toHaveCount(2)
        ->and($minute->drive_file_id)->not->toBeNull();

    expect(FakeDriveService::$files)->toHaveKey($minute->drive_file_id);
    expect(FakeDriveService::$files[$minute->drive_file_id]['file']->mimeType)->toBe('application/pdf');
});

it('permite editar un acta regenerando el PDF y actualizando asistentes/puntos', function () {
    $secretaria = userWithRole('secretaria');
    $minute = Minute::create([
        'title' => 'Acta original',
        'type' => 'actas_consejo',
        'held_on' => now()->subWeek(),
        'location' => 'Local scout',
    ]);
    $attendee = Member::factory()->create(['role' => MemberRole::Responsable]);

    $this->actingAs($secretaria)->put(route('minutes.update', $minute), [
        'title' => 'Acta original revisada',
        'type' => $minute->type,
        'held_on' => $minute->held_on->format('Y-m-d'),
        'location' => 'Sede central',
        'attendee_ids' => [$attendee->id],
        'items' => [
            ['topic' => 'Punto único', 'discussion' => null, 'agreement' => 'Acordado'],
        ],
    ])->assertRedirect();

    $minute->refresh();
    expect($minute->title)->toBe('Acta original revisada')
        ->and($minute->attendees)->toHaveCount(1)
        ->and($minute->items)->toHaveCount(1)
        ->and($minute->drive_file_id)->not->toBeNull();
});

it('un responsable sin permiso minutes.manage recibe 403 al crear un acta', function () {
    $responsable = userWithRole('responsable', ['lobato']);

    $response = $this->actingAs($responsable)->post(route('minutes.store'), [
        'title' => 'Acta no autorizada',
        'type' => 'actas_consejo',
        'held_on' => now()->format('Y-m-d'),
        'attendee_ids' => [],
        'items' => [],
    ]);

    $response->assertForbidden();
    expect(Minute::firstWhere('title', 'Acta no autorizada'))->toBeNull();
});
