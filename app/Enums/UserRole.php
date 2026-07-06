<?php

namespace App\Enums;

/**
 * Roles de usuario del sistema (se sincronizan con spatie/laravel-permission).
 * Distinto de MemberRole, que es la rama educativa de un miembro.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Secretaria = 'secretaria';
    case Tesoreria = 'tesoreria';
    case Responsable = 'responsable';
    case Familia = 'familia';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Coordinación de grupo',
            self::Secretaria => 'Secretaría',
            self::Tesoreria => 'Tesorería',
            self::Responsable => 'Responsable',
            self::Familia => 'Familia',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
