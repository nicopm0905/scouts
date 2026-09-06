<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Fecha sin hora almacenada como "Y-m-d".
 *
 * El cast nativo 'date' guarda "Y-m-d 00:00:00", lo que rompe las búsquedas
 * por igualdad (updateOrCreate, assertDatabaseHas) con fechas "Y-m-d".
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value ? Carbon::parse($value)->startOfDay() : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }
}
