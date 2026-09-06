<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Approved => 'Aprobada',
            self::Submitted => 'Entregada a MSC',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
