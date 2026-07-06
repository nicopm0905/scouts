<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\InventoryItem;
use App\Services\Inventory\InventoryAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryEventAvailabilityController extends Controller
{
    public function __construct(private readonly InventoryAvailabilityService $availability)
    {
    }

    /**
     * Disponibilidad de material requerido por las actividades programadas de un evento,
     * en las fechas de dicho evento (cruce inventario <-> actividades).
     */
    public function show(Event $event): JsonResponse
    {
        $this->authorize('viewAny', InventoryItem::class);

        return response()->json([
            'event_id' => $event->id,
            'materials' => $this->availability->checkEventMaterials($event),
        ]);
    }

    /**
     * Disponibilidad puntual de un ítem en un rango de fechas dado (para el formulario
     * de reserva: se consulta antes de confirmar la reserva).
     */
    public function forItem(Request $request, InventoryItem $item): JsonResponse
    {
        $this->authorize('view', $item);

        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        $start = $request->string('start')->toString();
        $end = $request->string('end')->toString() ?: $start;

        return response()->json([
            'inventory_item_id' => $item->id,
            'available_quantity' => $this->availability->availableQuantity($item, $start, $end),
        ]);
    }
}
