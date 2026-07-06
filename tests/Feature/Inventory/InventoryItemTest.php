<?php

use App\Models\InventoryItem;

it('un usuario con inventory.manage (admin) puede crear, editar y eliminar un ítem', function () {
    $admin = userWithRole('admin');

    $response = $this->actingAs($admin)->post(route('inventory.store'), [
        'name' => 'Tienda canadiense',
        'category' => 'tiendas',
        'quantity' => 4,
        'condition' => 'bueno',
        'location' => 'Almacén',
        'next_review_at' => null,
        'notes' => null,
    ]);

    $response->assertRedirect(route('inventory.index'));

    $item = InventoryItem::firstWhere('name', 'Tienda canadiense');
    expect($item)->not->toBeNull()
        ->and($item->quantity)->toBe(4)
        ->and($item->category->value)->toBe('tiendas');

    $this->actingAs($admin)->put(route('inventory.update', $item), [
        'name' => 'Tienda canadiense grande',
        'category' => 'tiendas',
        'quantity' => 6,
        'condition' => 'regular',
        'location' => 'Contenedor',
        'next_review_at' => null,
        'notes' => null,
    ])->assertRedirect(route('inventory.index'));

    expect($item->fresh()->name)->toBe('Tienda canadiense grande')
        ->and($item->fresh()->quantity)->toBe(6)
        ->and($item->fresh()->condition->value)->toBe('regular');

    $this->actingAs($admin)->delete(route('inventory.destroy', $item))->assertRedirect();

    expect(InventoryItem::find($item->id))->toBeNull();
});

it('el índice muestra el material pendiente de revisión', function () {
    $admin = userWithRole('admin');

    InventoryItem::factory()->reviewDue()->create(['name' => 'Botiquín rama Lobato']);
    InventoryItem::factory()->create(['name' => 'Cuerda 20m', 'next_review_at' => null]);

    $response = $this->actingAs($admin)->get(route('inventory.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Inventory/Index')
        ->has('needingReview', 1)
        ->where('needingReview.0.name', 'Botiquín rama Lobato')
    );
});
