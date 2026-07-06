<?php

namespace App\Enums;

enum ChargeType: string
{
    case CuotaAnual = 'cuota_anual';
    case CuotaTrimestral = 'cuota_trimestral';
    case Salida = 'salida';
    case Campamento = 'campamento';
    case Material = 'material';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::CuotaAnual => 'Cuota anual',
            self::CuotaTrimestral => 'Cuota trimestral',
            self::Salida => 'Salida',
            self::Campamento => 'Campamento',
            self::Material => 'Material',
            self::Otro => 'Otro',
        };
    }

    /** ¿Es una cuota susceptible de descuento por hermanos? */
    public function isQuota(): bool
    {
        return in_array($this, [self::CuotaAnual, self::CuotaTrimestral], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
