<?php

namespace App\Enums;

enum InvoiceCategory: string
{
    case Material = 'material';
    case Alquiler = 'alquiler';
    case Transporte = 'transporte';
    case Comida = 'comida';
    case Seguros = 'seguros';
    case Subvencion = 'subvencion';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Material => 'Material',
            self::Alquiler => 'Alquiler',
            self::Transporte => 'Transporte',
            self::Comida => 'Comida',
            self::Seguros => 'Seguros',
            self::Subvencion => 'Subvención',
            self::Otro => 'Otro',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
