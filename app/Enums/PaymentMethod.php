<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Efectivo = 'efectivo';
    case Bizum = 'bizum';
    case Transferencia = 'transferencia';

    public function label(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Bizum => 'Bizum',
            self::Transferencia => 'Transferencia',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
