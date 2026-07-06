<?php

namespace App\Enums;

enum ItemCondition: string
{
    case Bueno = 'bueno';
    case Regular = 'regular';
    case Reparar = 'reparar';
    case Baja = 'baja';

    public function label(): string
    {
        return match ($this) {
            self::Bueno => 'Bueno',
            self::Regular => 'Regular',
            self::Reparar => 'A reparar',
            self::Baja => 'De baja',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Bueno => 'green',
            self::Regular => 'yellow',
            self::Reparar => 'orange',
            self::Baja => 'red',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
