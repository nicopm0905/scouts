<?php

namespace App\Enums;

enum MemberRole: string
{
    case Castor = 'castor';
    case Lobato = 'lobato';
    case Ranger = 'ranger';
    case Pionero = 'pionero';
    case Ruta = 'ruta';
    case Responsable = 'responsable';

    /** Etiqueta legible en español. */
    public function label(): string
    {
        return match ($this) {
            self::Castor => 'Castor',
            self::Lobato => 'Lobato',
            self::Ranger => 'Ranger',
            self::Pionero => 'Pionero',
            self::Ruta => 'Ruta',
            self::Responsable => 'Responsable',
        };
    }

    /** Ramas educativas (excluye responsables). */
    public static function branches(): array
    {
        return [self::Castor, self::Lobato, self::Ranger, self::Pionero, self::Ruta];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
