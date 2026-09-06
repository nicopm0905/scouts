<?php

use App\Enums\EventType;
use App\Enums\MemberRole;
use App\Models\Activity;
use App\Models\ActivityMaterial;
use App\Models\Event;
use App\Models\InventoryItem;
use App\Services\Events\EventMaterialList;

it('agrega y suma el material de todas las actividades del evento', function () {
    $event = Event::factory()->create([
        'type' => EventType::Acampada,
        'start_at' => now()->addWeek(),
        'end_at' => now()->addWeek()->addDays(2),
    ]);

    $cuerda = InventoryItem::factory()->create(['name' => 'Cuerda 20m', 'quantity' => 10]);

    $a1 = Activity::factory()->create(['materials_text' => 'Trapos viejos']);
    $a2 = Activity::factory()->create();
    $event->activities()->attach([$a1->id, $a2->id]);

    // Misma cuerda en dos actividades: 4 + 3 = 7.
    ActivityMaterial::factory()->create(['activity_id' => $a1->id, 'name' => 'Cuerda 20m', 'quantity' => 4, 'inventory_item_id' => $cuerda->id]);
    ActivityMaterial::factory()->create(['activity_id' => $a2->id, 'name' => 'Cuerda 20m', 'quantity' => 3, 'inventory_item_id' => $cuerda->id]);
    // Material suelto sin inventario, repetido por nombre: 5 + 2 = 7.
    ActivityMaterial::factory()->create(['activity_id' => $a1->id, 'name' => 'Folios', 'quantity' => 5, 'inventory_item_id' => null]);
    ActivityMaterial::factory()->create(['activity_id' => $a2->id, 'name' => 'folios', 'quantity' => 2, 'inventory_item_id' => null]);

    $list = app(EventMaterialList::class)->for($event->fresh());

    $cuerdaRow = collect($list['items'])->firstWhere('inventory_item_id', $cuerda->id);
    $foliosRow = collect($list['items'])->firstWhere('name', 'Folios');

    expect($list['items'])->toHaveCount(2)
        ->and($cuerdaRow['total_quantity'])->toBe(7)
        ->and($cuerdaRow['available_quantity'])->toBe(10)
        ->and($cuerdaRow['enough'])->toBeTrue()
        ->and($foliosRow['total_quantity'])->toBe(7)
        ->and($foliosRow['inventory_item_id'])->toBeNull()
        ->and($list['free_text'])->toHaveCount(1)
        ->and($list['free_text'][0]['text'])->toBe('Trapos viejos');
});

it('marca el material de inventario que no llega para las fechas del evento', function () {
    $event = Event::factory()->create(['type' => EventType::Acampada, 'start_at' => now()->addWeek(), 'end_at' => now()->addWeek()->addDay()]);
    $item = InventoryItem::factory()->create(['name' => 'Tienda', 'quantity' => 2]);
    $act = Activity::factory()->create();
    $event->activities()->attach($act->id);
    ActivityMaterial::factory()->create(['activity_id' => $act->id, 'name' => 'Tienda', 'quantity' => 5, 'inventory_item_id' => $item->id]);

    $row = collect(app(EventMaterialList::class)->for($event->fresh())['items'])->firstWhere('inventory_item_id', $item->id);

    expect($row['total_quantity'])->toBe(5)
        ->and($row['available_quantity'])->toBe(2)
        ->and($row['enough'])->toBeFalse();
});

it('genera el PDF de la lista de material', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $event = Event::factory()->create(['type' => EventType::Acampada, 'branches' => [MemberRole::Lobato->value]]);
    $act = Activity::factory()->create();
    $event->activities()->attach($act->id);
    ActivityMaterial::factory()->create(['activity_id' => $act->id, 'quantity' => 3]);

    $this->actingAs($user)->get(route('events.pdf.materials', $event))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('un responsable con events.view puede ver la lista de material de cualquier evento', function () {
    $user = userWithRole('responsable', [MemberRole::Lobato->value]);
    $event = Event::factory()->create(['type' => EventType::Acampada, 'branches' => [MemberRole::Pionero->value]]);

    // `view` en EventPolicy solo exige events.view (no ámbito de rama), como el resto de PDFs.
    $this->actingAs($user)->get(route('events.pdf.materials', $event))->assertOk();
});
