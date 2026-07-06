<?php

use App\Models\Activity;
use App\Models\ActivityMaterial;
use App\Models\Checkout;
use App\Models\Event;
use App\Models\InventoryItem;
use App\Services\Inventory\InventoryAvailabilityService;

it('calcula la disponibilidad correctamente con reservas solapadas', function () {
    $service = new InventoryAvailabilityService();
    $item = InventoryItem::factory()->create(['quantity' => 10]);

    // Checkout que ocupa 4 unidades del 10 al 15.
    Checkout::factory()->create([
        'inventory_item_id' => $item->id,
        'quantity' => 4,
        'checked_out_at' => now()->addDays(10),
        'expected_return_at' => now()->addDays(15),
        'returned_at' => null,
    ]);

    // Checkout que ocupa 3 unidades del 20 al 25 (no se solapa con el rango solicitado).
    Checkout::factory()->create([
        'inventory_item_id' => $item->id,
        'quantity' => 3,
        'checked_out_at' => now()->addDays(20),
        'expected_return_at' => now()->addDays(25),
        'returned_at' => null,
    ]);

    // Rango solicitado se solapa solo con el primer checkout.
    $available = $service->availableQuantity($item, now()->addDays(12), now()->addDays(13));
    expect($available)->toBe(6); // 10 - 4

    // Rango que no se solapa con ningún checkout.
    $availableFree = $service->availableQuantity($item, now()->addDays(1), now()->addDays(2));
    expect($availableFree)->toBe(10);

    // Rango que se solapa con ambos checkouts.
    $availableBoth = $service->availableQuantity($item, now()->addDays(14), now()->addDays(21));
    expect($availableBoth)->toBe(3); // 10 - 4 - 3
});

it('un checkout ya devuelto antes del rango solicitado no reduce disponibilidad', function () {
    $service = new InventoryAvailabilityService();
    $item = InventoryItem::factory()->create(['quantity' => 5]);

    Checkout::factory()->create([
        'inventory_item_id' => $item->id,
        'quantity' => 5,
        'checked_out_at' => now()->subDays(10),
        'expected_return_at' => now()->subDays(5),
        'returned_at' => now()->subDays(6),
    ]);

    $available = $service->availableQuantity($item, now(), now()->addDays(1));
    expect($available)->toBe(5);
});

it('indica si el material requerido por las actividades de un evento está disponible en sus fechas', function () {
    $service = new InventoryAvailabilityService();

    $item = InventoryItem::factory()->create(['quantity' => 3]);
    $event = Event::factory()->create([
        'start_at' => now()->addDays(30),
        'end_at' => now()->addDays(32),
    ]);
    $activity = Activity::factory()->create();
    $event->activities()->attach($activity);

    ActivityMaterial::factory()->create([
        'activity_id' => $activity->id,
        'inventory_item_id' => $item->id,
        'quantity' => 2,
    ]);

    // Sin checkouts previos: 2 de 3 disponibles -> suficiente.
    $result = $service->checkEventMaterials($event);
    expect($result)->toHaveCount(1)
        ->and($result[0]['required_quantity'])->toBe(2)
        ->and($result[0]['available_quantity'])->toBe(3)
        ->and($result[0]['is_available'])->toBeTrue();

    // Reservamos 2 unidades para otro evento que se solapa con las fechas -> solo queda 1.
    Checkout::factory()->create([
        'inventory_item_id' => $item->id,
        'quantity' => 2,
        'checked_out_at' => now()->addDays(29),
        'expected_return_at' => now()->addDays(33),
        'returned_at' => null,
    ]);

    $result = $service->checkEventMaterials($event->fresh());
    expect($result[0]['available_quantity'])->toBe(1)
        ->and($result[0]['is_available'])->toBeFalse();
});
