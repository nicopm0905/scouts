<?php

namespace App\Enums;

enum InvoiceDirection: string
{
    case Received = 'received';
    case Issued = 'issued';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recibida (gasto)',
            self::Issued => 'Emitida',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
