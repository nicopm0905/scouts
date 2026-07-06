<?php

use App\Enums\MemberRole;
use App\Models\Activity;
use App\Models\InventoryItem;

it('crea una actividad con materiales', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $item = InventoryItem::factory()->create();

    $response = $this->actingAs($user)->post(route('activities.store'), [
        'title' => 'Gran juego de pistas',
        'branch' => MemberRole::Lobato->value,
        'duration_minutes' => 60,
        'objectives_text' => 'Trabajo en equipo',
        'development' => '## Desarrollo',
        'materials' => [
            ['name' => 'Cuerdas', 'quantity' => 5, 'inventory_item_id' => $item->id],
            ['name' => 'Pañuelos', 'quantity' => 10],
        ],
    ]);

    $response->assertRedirect();

    $activity = Activity::where('title', 'Gran juego de pistas')->firstOrFail();
    expect($activity->materials)->toHaveCount(2);
    expect($activity->materials->firstWhere('name', 'Cuerdas')->inventory_item_id)->toBe($item->id);
});

it('lista y filtra actividades por rama y material', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $activity = Activity::factory()->create(['branch' => MemberRole::Lobato->value]);
    $activity->materials()->create(['name' => 'Cuerdas', 'quantity' => 3]);
    Activity::factory()->create(['branch' => MemberRole::Pionero->value]);

    $response = $this->actingAs($user)->get(route('activities.index', ['material' => 'Cuerdas']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Activities/Index')
        ->has('activities', 1));
});

it('actualiza una actividad reemplazando sus materiales', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $activity = Activity::factory()->create();
    $activity->materials()->create(['name' => 'Viejo material', 'quantity' => 1]);

    $this->actingAs($user)->put(route('activities.update', $activity), [
        'title' => $activity->title,
        'branch' => $activity->branch,
        'duration_minutes' => $activity->duration_minutes,
        'materials' => [
            ['name' => 'Rotuladores', 'quantity' => 4],
        ],
    ])->assertRedirect();

    $activity->refresh();
    expect($activity->materials)->toHaveCount(1);
    expect($activity->materials->first()->name)->toBe('Rotuladores');
});

it('duplica una actividad junto con sus materiales', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $activity = Activity::factory()->create(['title' => 'Velada nocturna']);
    $activity->materials()->create(['name' => 'Linternas', 'quantity' => 8]);

    $this->actingAs($user)->post(route('activities.duplicate', $activity))->assertRedirect();

    $copy = Activity::where('title', 'Velada nocturna (copia)')->firstOrFail();
    expect($copy->id)->not->toBe($activity->id);
    expect($copy->materials)->toHaveCount(1);
    expect($copy->materials->first()->name)->toBe('Linternas');
});

it('elimina una actividad', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $activity = Activity::factory()->create();

    $this->actingAs($user)->delete(route('activities.destroy', $activity))
        ->assertRedirect(route('activities.index'));

    $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
});
