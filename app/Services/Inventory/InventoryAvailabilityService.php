<?php

namespace App\Services\Inventory;

use App\Models\Event;
use App\Models\InventoryItem;
use Illuminate\Support\Carbon;

/**
 * Cruza el inventario con las fechas de eventos/checkouts para saber si hay
 * unidades suficientes disponibles en un rango de fechas concreto.
 */
class InventoryAvailabilityService
{
    /**
     * Unidades disponibles de un ítem en el rango [$start, $end], teniendo en cuenta
     * los checkouts que se solapan con ese rango (no solo los que siguen "fuera").
     */
    public function availableQuantity(
        InventoryItem $item,
        Carbon|string $start,
        Carbon|string $end,
        ?int $excludeCheckoutId = null,
    ): int {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();

        $reserved = $item->checkouts()
            ->when($excludeCheckoutId, fn ($q) => $q->where('id', '!=', $excludeCheckoutId))
            ->get()
            ->filter(fn ($checkout) => $this->overlaps($checkout, $start, $end))
            ->sum('quantity');

        return max(0, $item->quantity - (int) $reserved);
    }

    /** Indica si un checkout (activo o ya devuelto) ocupó unidades durante el rango dado. */
    private function overlaps($checkout, Carbon $start, Carbon $end): bool
    {
        $checkoutStart = Carbon::parse($checkout->checked_out_at)->startOfDay();
        $checkoutEnd = $checkout->returned_at
            ? Carbon::parse($checkout->returned_at)->endOfDay()
            : Carbon::parse($checkout->expected_return_at ?? $checkout->checked_out_at)->endOfDay();

        // Si ya se devolvió antes de que empiece el rango solicitado, no hay solape.
        if ($checkout->returned_at && $checkoutEnd->lt($start)) {
            return false;
        }

        return $checkoutStart->lte($end) && $checkoutEnd->gte($start);
    }

    /**
     * Para un evento con actividades programadas, indica por cada material
     * requerido (activity_materials con inventory_item_id) si hay disponibilidad
     * suficiente en las fechas del evento.
     *
     * @return array<int, array{
     *     activity_material_id: int,
     *     activity_id: int,
     *     inventory_item_id: int,
     *     name: string,
     *     required_quantity: int,
     *     available_quantity: int,
     *     is_available: bool,
     * }>
     */
    public function checkEventMaterials(Event $event): array
    {
        $start = $event->start_at ?? now();
        $end = $event->end_at ?? $start;

        $materials = $event->activities()
            ->with(['materials.inventoryItem'])
            ->get()
            ->flatMap(fn ($activity) => $activity->materials)
            ->filter(fn ($material) => $material->inventory_item_id !== null);

        return $materials->map(function ($material) use ($start, $end) {
            $item = $material->inventoryItem;
            $available = $this->availableQuantity($item, $start, $end);

            return [
                'activity_material_id' => $material->id,
                'activity_id' => $material->activity_id,
                'inventory_item_id' => $item->id,
                'name' => $item->name,
                'required_quantity' => $material->quantity,
                'available_quantity' => $available,
                'is_available' => $available >= $material->quantity,
            ];
        })->values()->all();
    }
}
