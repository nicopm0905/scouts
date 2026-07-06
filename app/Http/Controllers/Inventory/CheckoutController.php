<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreCheckoutRequest;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Services\Inventory\InventoryAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(private readonly InventoryAvailabilityService $availability)
    {
    }

    /** Registra una reserva/préstamo (evento o responsable) en 1-2 clics. */
    public function store(StoreCheckoutRequest $request, InventoryItem $item): RedirectResponse
    {
        $data = $request->validated();

        $available = $this->availability->availableQuantity(
            $item,
            $data['checked_out_at'],
            $data['expected_return_at'] ?? $data['checked_out_at'],
        );

        if ($data['quantity'] > $available) {
            return back()->withErrors([
                'quantity' => "Solo hay {$available} unidad(es) disponibles en esas fechas.",
            ])->withInput();
        }

        $item->checkouts()->create($data);

        return back()->with('success', 'Material reservado correctamente.');
    }

    /** Botón "marcar devuelto". */
    public function markReturned(Request $request, Checkout $checkout): RedirectResponse
    {
        $this->authorize('reserve', $checkout->inventoryItem);

        $checkout->update([
            'returned_at' => $request->date('returned_at') ?? now()->toDateString(),
        ]);

        return back()->with('success', 'Devolución registrada.');
    }
}
