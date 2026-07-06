<?php

use App\Enums\MemberRole;
use App\Models\Event;
use App\Models\Member;
use Illuminate\Support\Str;

it('el evento muestra el semáforo en fail cuando faltan responsables', function () {
    $user = userWithRole('secretaria');
    $camp = Event::factory()->camp()->create(['branches' => [MemberRole::Lobato->value], 'start_at' => now()->addMonth()]);

    // 20 lobatos inscritos y ningún responsable -> ratio incumplida.
    Member::factory()->branch(MemberRole::Lobato)->count(20)->create()->each(function (Member $m) use ($camp) {
        $camp->members()->attach($m->id, ['enrolled' => true, 'public_token' => Str::random(40)]);
    });

    $response = $this->actingAs($user)->get(route('events.show', $camp));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('Events/Show')
        ->where('campRatio.status', 'fail')
    );
});
