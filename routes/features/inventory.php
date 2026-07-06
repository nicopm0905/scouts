<?php

use App\Http\Controllers\Inventory\CheckoutController;
use App\Http\Controllers\Inventory\InventoryEventAvailabilityController;
use App\Http\Controllers\Inventory\InventoryItemController;
use Illuminate\Support\Facades\Route;

/*
| Rutas del feature Inventario (ítems, reservas/préstamos, disponibilidad).
| Prefijo de URI en inglés (convención del proyecto); textos de UI en español
| vía lang/es/inventory.php.
*/
Route::middleware('auth')->group(function () {
    Route::resource('inventory', InventoryItemController::class)
        ->parameters(['inventory' => 'item'])
        ->names('inventory');

    Route::post('inventory/{item}/checkouts', [CheckoutController::class, 'store'])
        ->name('inventory.checkouts.store');

    Route::post('checkouts/{checkout}/return', [CheckoutController::class, 'markReturned'])
        ->name('inventory.checkouts.return');

    Route::get('inventory/{item}/availability', [InventoryEventAvailabilityController::class, 'forItem'])
        ->name('inventory.items.availability');

    Route::get('inventory/events/{event}/availability', [InventoryEventAvailabilityController::class, 'show'])
        ->name('inventory.events.availability');
});
