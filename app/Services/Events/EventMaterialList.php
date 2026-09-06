<?php

namespace App\Services\Events;

use App\Models\Event;
use App\Services\Inventory\InventoryAvailabilityService;

/**
 * Agrega el material de todas las actividades programadas de un evento en una
 * sola lista (para empaquetar / reservar), sumando cantidades y cruzando con
 * el inventario cuando el material está enlazado a un ítem.
 */
class EventMaterialList
{
    public function __construct(private readonly InventoryAvailabilityService $availability) {}

    /**
     * @return array{
     *     items: array<int, array{name:string, total_quantity:int, inventory_item_id:?int, available_quantity:?int, enough:?bool}>,
     *     free_text: array<int, array{activity:string, text:string}>,
     * }
     */
    public function for(Event $event): array
    {
        $event->loadMissing(['activities.materials.inventoryItem']);

        $start = $event->start_at ?? now();
        $end = $event->end_at ?? $start;

        $grouped = [];
        $freeText = [];

        foreach ($event->activities as $activity) {
            if (filled($activity->materials_text)) {
                $freeText[] = ['activity' => $activity->title, 'text' => $activity->materials_text];
            }

            foreach ($activity->materials as $material) {
                // Clave: por ítem de inventario si está enlazado, si no por nombre normalizado.
                $key = $material->inventory_item_id
                    ? 'item:'.$material->inventory_item_id
                    : 'name:'.mb_strtolower(trim($material->name));

                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'name' => $material->inventoryItem?->name ?? $material->name,
                        'total_quantity' => 0,
                        'inventory_item_id' => $material->inventory_item_id,
                        'available_quantity' => null,
                        'enough' => null,
                    ];
                }

                $grouped[$key]['total_quantity'] += (int) $material->quantity;
            }
        }

        foreach ($grouped as &$row) {
            if ($row['inventory_item_id']) {
                $item = $event->activities
                    ->flatMap->materials
                    ->firstWhere('inventory_item_id', $row['inventory_item_id'])
                    ?->inventoryItem;

                if ($item) {
                    $available = $this->availability->availableQuantity($item, $start, $end);
                    $row['available_quantity'] = $available;
                    $row['enough'] = $available >= $row['total_quantity'];
                }
            }
        }
        unset($row);

        $items = array_values($grouped);
        usort($items, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return ['items' => $items, 'free_text' => $freeText];
    }
}
