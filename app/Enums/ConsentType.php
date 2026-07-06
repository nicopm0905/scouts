<?php

namespace App\Enums;

enum ConsentType: string
{
    case Rgpd = 'rgpd';
    case Imagen = 'imagen';
    case SalidasPeriodicas = 'salidas_periodicas';

    public function label(): string
    {
        return match ($this) {
            self::Rgpd => 'Protección de datos (RGPD)',
            self::Imagen => 'Derechos de imagen',
            self::SalidasPeriodicas => 'Salidas periódicas',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
