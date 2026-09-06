<?php

namespace App\Enums;

enum BudgetStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Active => 'Activo',
            self::Finished => 'Terminado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
