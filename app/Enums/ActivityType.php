<?php

namespace App\Enums;

/** Tipo de actividad en la columna "Tipo" de la programación trimestral. */
enum ActivityType: string
{
    case Actividad = 'actividad';
    case Dinamica = 'dinamica';
    case Taller = 'taller';
    case Juego = 'juego';
    case Salida = 'salida';
    case Velada = 'velada';
    case Servicio = 'servicio';
    case Celebracion = 'celebracion';
    case Reunion = 'reunion';
    case Proyecto = 'proyecto';

    public function label(): string
    {
        return match ($this) {
            self::Actividad => 'Actividad',
            self::Dinamica => 'Dinámica',
            self::Taller => 'Taller',
            self::Juego => 'Juego',
            self::Salida => 'Salida',
            self::Velada => 'Velada',
            self::Servicio => 'Servicio',
            self::Celebracion => 'Celebración',
            self::Reunion => 'Reunión',
            self::Proyecto => 'Proyecto',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(
            fn (self $t) => ['value' => $t->value, 'label' => $t->label()],
            self::cases()
        );
    }
}
