<?php

namespace App\Services\Plans;

use App\Models\Activity;

/** Clona una actividad de la biblioteca junto con sus materiales. */
class ActivityDuplicator
{
    public function duplicate(Activity $activity, int $userId): Activity
    {
        $copy = $activity->replicate(['created_at', 'updated_at']);
        $copy->title = $activity->title.' (copia)';
        $copy->created_by = $userId;
        $copy->save();

        foreach ($activity->materials as $material) {
            $copy->materials()->create([
                'name' => $material->name,
                'quantity' => $material->quantity,
                'inventory_item_id' => $material->inventory_item_id,
            ]);
        }

        return $copy;
    }
}
