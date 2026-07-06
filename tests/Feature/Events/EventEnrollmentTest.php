<?php

use App\Enums\EventType;
use App\Enums\MemberRole;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\Member;
use App\Services\Drive\FakeDriveService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

beforeEach(function () {
    FakeDriveService::reset();
});

it('al crear una salida se generan automáticamente las inscripciones de la rama', function () {
    $user = userWithRole('secretaria');
    Member::factory()->branch(MemberRole::Lobato)->count(3)->create();
    Member::factory()->branch(MemberRole::Pionero)->create(); // no debe inscribirse

    $response = $this->actingAs($user)->post(route('events.store'), [
        'title' => 'Salida al campo',
        'type' => EventType::Salida->value,
        'start_at' => now()->addWeek()->toDateTimeString(),
        'end_at' => null,
        'location' => 'Sierra',
        'description' => null,
        'branches' => [MemberRole::Lobato->value],
    ]);

    $response->assertRedirect();
    $event = Event::where('title', 'Salida al campo')->firstOrFail();

    expect($event->enrollments()->count())->toBe(3);
});

it('el equipo puede marcar inscrito/no inscrito a un miembro', function () {
    $user = userWithRole('secretaria');
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Ruta->value]]);
    $member = Member::factory()->branch(MemberRole::Ruta)->create();

    $enrollment = EventMember::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'enrolled' => false,
        'public_token' => Str::random(40),
    ]);

    $this->actingAs($user)->patch(route('events.enrollments.update', [$event, $enrollment]), [
        'enrolled' => true,
    ])->assertRedirect();

    expect($enrollment->fresh()->enrolled)->toBeTrue();
});

it('la familia confirma la inscripción por el enlace público tokenizado', function () {
    $event = Event::factory()->camp()->create();
    $member = Member::factory()->branch(MemberRole::Ruta)->create();

    $enrollment = EventMember::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'enrolled' => false,
        'public_token' => Str::random(40),
    ]);

    // Página pública accesible sin autenticación.
    $this->get(route('public.enrollment.show', $enrollment->public_token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/Enrollment')->where('enrolled', false));

    $this->post(route('public.enrollment.confirm', $enrollment->public_token))->assertRedirect();

    expect($enrollment->fresh()->enrolled)->toBeTrue()
        ->and($enrollment->fresh()->confirmed_at)->not->toBeNull();
});

it('la familia sube la autorización firmada y se guarda vía DriveService', function () {
    $event = Event::factory()->camp()->create();
    $member = Member::factory()->branch(MemberRole::Ruta)->create();

    $enrollment = EventMember::create([
        'event_id' => $event->id,
        'member_id' => $member->id,
        'enrolled' => true,
        'public_token' => Str::random(40),
    ]);

    $file = UploadedFile::fake()->create('autorizacion.pdf', 100, 'application/pdf');

    $this->post(route('public.enrollment.upload', $enrollment->public_token), [
        'file' => $file,
    ])->assertRedirect();

    $enrollment->refresh();

    expect($enrollment->authorization_file_id)->not->toBeNull()
        ->and($enrollment->hasAuthorization())->toBeTrue()
        ->and(FakeDriveService::$files)->toHaveKey($enrollment->authorization_file_id);
});
