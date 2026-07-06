<?php

use App\Enums\MemberRole;
use App\Models\Event;
use App\Models\Member;
use Illuminate\Support\Str;

it('genera el PDF del listado de asistentes', function () {
    $user = userWithRole('secretaria');
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Pionero->value]]);

    $member = Member::factory()->branch(MemberRole::Pionero)->create();
    $event->members()->attach($member->id, ['enrolled' => true, 'public_token' => Str::random(40)]);

    $response = $this->actingAs($user)->get(route('events.pdf.attendees', $event));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('genera el PDF de la circular', function () {
    $user = userWithRole('secretaria');
    $event = Event::factory()->camp()->create(['branches' => [MemberRole::Pionero->value]]);

    $response = $this->actingAs($user)->get(route('events.pdf.circular', $event));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
