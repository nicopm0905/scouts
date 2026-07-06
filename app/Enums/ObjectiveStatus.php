<?php

namespace App\Enums;

enum ObjectiveStatus: string
{
    case Pendiente = 'pendiente';
    case EnCurso = 'en_curso';
    case Logrado = 'logrado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnCurso => 'En curso',
            self::Logrado => 'Logrado',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::EnCurso => 'blue',
            self::Logrado => 'green',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
