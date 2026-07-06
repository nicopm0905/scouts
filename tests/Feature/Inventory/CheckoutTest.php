<?php

use App\Models\Checkout;
use App\Models\Event;
use App\Models\InventoryItem;
use App\Models\Member;

it('un responsable puede reservar material para un evento en 1-2 clics', function () {
    $user = userWithRole('responsable', ['lobato']);
    $item = InventoryItem::factory()->create(['quantity' => 5]);
    $event = Event::factory()->create([
        'start_at' => now()->addDays(5),
        'end_at' => now()->addDays(7),
    ]);

    $response = $this->actingAs($user)->post(route('inventory.checkouts.store', $item), [
        'quantity' => 2,
        'event_id' => $event->id,
        'member_id' => null,
        'checked_out_at' => now()->addDays(5)->toDateString(),
        'expected_return_at' => now()->addDays(7)->toDateString(),
    ]);

    $response->assertRedirect();

    $checkout = Checkout::firstWhere('inventory_item_id', $item->id);
    expect($checkout)->not->toBeNull()
        ->and($checkout->quantity)->toBe(2)
        ->and($checkout->event_id)->toBe($event->id);
});

it('marca un checkout como devuelto', function () {
    $user = userWithRole('responsable', ['lobato']);
    $item = InventoryItem::factory()->create(['quantity' => 5]);
    $checkout = Checkout::factory()->create(['inventory_item_id' => $item->id]);

    expect($checkout->returned_at)->toBeNull();

    $this->actingAs($user)->post(route('inventory.checkouts.return', $checkout))
        ->assertRedirect();

    expect($checkout->fresh()->returned_at)->not->toBeNull();
});

it('detecta un checkout fuera de plazo (overdue)', function () {
    $item = InventoryItem::factory()->create(['quantity' => 5]);
    $overdue = Checkout::factory()->overdue()->create(['inventory_item_id' => $item->id]);
    $onTime = Checkout::factory()->create([
        'inventory_item_id' => $item->id,
        'checked_out_at' => now(),
        'expected_return_at' => now()->addDays(5),
        'returned_at' => null,
    ]);

    expect($overdue->isOverdue())->toBeTrue()
        ->and($onTime->isOverdue())->toBeFalse();

    $admin = userWithRole('admin');
    $response = $this->actingAs($admin)->get(route('inventory.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('Inventory/Index')
        ->has('overdueCheckouts', 1)
        ->where('overdueCheckouts.0.id', $overdue->id)
    );
});

it('un responsable PUEDE reservar material pero recibe 403 al crear un ítem', function () {
    $user = userWithRole('responsable', ['lobato']);
    $item = InventoryItem::factory()->create(['quantity' => 5]);

    // Puede reservar (ability 'reserve').
    $this->actingAs($user)->post(route('inventory.checkouts.store', $item), [
        'quantity' => 1,
        'event_id' => null,
        'member_id' => Member::factory()->create(['role' => 'responsable'])->id,
        'checked_out_at' => now()->toDateString(),
        'expected_return_at' => now()->addDays(3)->toDateString(),
    ])->assertRedirect();

    // No puede crear/editar ítems (requiere inventory.manage).
    $this->actingAs($user)->post(route('inventory.store'), [
        'name' => 'Intento no autorizado',
        'category' => 'otro',
        'quantity' => 1,
        'condition' => 'bueno',
    ])->assertForbidden();

    $this->actingAs($user)->put(route('inventory.update', $item), [
        'name' => 'Cambio no autorizado',
        'category' => 'otro',
        'quantity' => 1,
        'condition' => 'bueno',
    ])->assertForbidden();
});
