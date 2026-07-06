<?php

namespace App\Enums;

enum InventoryCategory: string
{
    case Tiendas = 'tiendas';
    case Cocina = 'cocina';
    case CuerdasPionerismo = 'cuerdas_pionerismo';
    case Botiquin = 'botiquin';
    case Juegos = 'juegos';
    case Fungible = 'fungible';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Tiendas => 'Tiendas',
            self::Cocina => 'Cocina',
            self::CuerdasPionerismo => 'Cuerdas / pionerismo',
            self::Botiquin => 'Botiquín',
            self::Juegos => 'Juegos',
            self::Fungible => 'Fungible',
            self::Otro => 'Otro',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
